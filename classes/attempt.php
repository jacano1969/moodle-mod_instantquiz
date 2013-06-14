<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * class instantquiz_attempt
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/mod/instantquiz/classes/entity.php');

/**
 * Contains information and useful functions to deal with one instantquiz attempt
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_attempt extends instantquiz_entity {
    //var $criterion;
    //var $addinfo;
    var $userid;
    var $timestarted;
    var $timefinished;
    var $attemptnumber;
    var $overriden;
    var $answers;
    var $points;
    var $feedbacks;
    var $addinfo;

    /**
     * Returns the name of DB table (used in functions get_all() and update() )
     *
     * @return string
     */
    protected static function get_table_name() {
        return 'instantquiz_attempt';
    }

    /**
     * Creates a new attempt for the current user
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return instantquiz_attempt
     */
    public static function create($instantquiz) {
        if (!self::can_start_attempt($instantquiz)) {
            return null;
        }
        global $USER, $DB;
        $maxattempt = $DB->get_field_sql("SELECT max(attemptnumber)
            FROM {instantquiz_attempt}
            WHERE instantquizid = ?
            AND userid = ?", array($instantquiz->id, $USER->id));
        if ($maxattempt) {
            $DB->execute("UPDATE {instantquiz_attempt} SET overriden = ?
                WHERE instantquizid = ?
            AND userid = ?", array(1, $instantquiz->id, $USER->id));
        }
        $defaultvalues = new stdClass();
        $defaultvalues->userid = $USER->id;
        $defaultvalues->attemptnumber = ((int)$maxattempt) + 1;
        $defaultvalues->timestarted = time();
        $defaultvalues->overriden = 0;
        $entity = new static($instantquiz, $defaultvalues);
        $entity->update();
        return $entity;
    }

    /**
     * Constructor from DB record
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param stdClass $record
     */
    protected function __construct($instantquiz, $record) {
        $this->instantquiz = $instantquiz;
        $serializedfields = array('addinfo', 'answers', 'points', 'feedbacks');
        foreach ($record as $key => $value) {
            if (property_exists($this, $key) && !in_array($key, $serializedfields)) {
                $this->$key = $value;
            }
        }
        foreach ($serializedfields as $key) {
            $this->$key = array();
            if (isset($record->$key) && ($value = @json_decode($record->$key))) {
                $this->$key = convert_to_array($value);
            }
        }
    }

    /**
     * Updates or creates entry in DB
     */
    public function update() {
        global $DB;
        $record = array(
            'timestarted' => $this->timestarted,
            'timefinished' => $this->timefinished,
            'attemptnumber' => $this->attemptnumber,
            'overriden' => $this->overriden,
            'answers' => json_encode($this->answers),
            'points' => json_encode($this->points),
            'feedbacks' => json_encode($this->feedbacks),
            'addinfo' => json_encode($this->addinfo),
        );
        if ($this->id) {
            $record['id'] = $this->id;
            $DB->update_record($this->get_table_name(), $record);
        } else {
            $record['userid'] = $this->userid;
            $record['instantquizid'] = $this->instantquiz->id;
            $this->id = $DB->insert_record($this->get_table_name(), $record);
        }
    }

    /**
     * Returns truncated and simply formatted name to display on the manage page
     *
     * @return string
     */
    public function get_preview() {
        // not applicable for attempts
        return null;
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $userid
     * @param int $attemptid
     * @return instantquiz_attempt
     */
    public static function get_user_attempt($instantquiz, $userid, $attemptid = 0) {
        global $DB;
        if ($attemptid) {
            if ($userid) {
                $record = $DB->get_record('instantquiz_attempt', array('userid' => $userid, 'id' => $attemptid));
            } else {
                $record = $DB->get_record('instantquiz_attempt', array('id' => $attemptid));
            }
        } else {
            if ($records = $DB->get_records('instantquiz_attempt', array('userid' => $userid), 'attemptnumber desc', '*', 0, 1)) {
                $record = reset($records);
            }
        }
        if (!empty($record)) {
            return new static($instantquiz, $record);
        }
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $userid
     * @return instantquiz_attempt
     */
    public static function get_all_user_attempts($instantquiz, $userid) {
        global $DB;
        $rv = array();
        if ($records = $DB->get_records('instantquiz_attempt', array('userid' => $userid), 'attemptnumber desc')) {
            foreach ($records as $record) {
                $rv[] = new static($instantquiz, $record);
            }
        }
        return $rv;
    }

    /**
     * Checks if user can continue current attempt (i.e. attempt is not finished and not expired)
     *
     * @return bool
     */
    public function can_continue_attempt() {
        global $USER;
        return $USER->id == $this->userid && !$this->timefinished;
    }

    public function can_view_attempt() {
        return true;
    }

    public static function can_start_attempt($instantquiz) {
        return true;
    }

    /**
     * Returns the answer entered in this attempt for a particular question
     *
     * @param int $questionid
     * @return mixed
     */
    public function get_answer($questionid) {
        if (isset($this->answers[$questionid])) {
            return $this->answers[$questionid];
        }
        return null;
    }

    /**
     * Evaluates this attempt and updates the record in DB
     */
    public function evaluate() {
        $criteria = $this->instantquiz->get_entities('criterion');

        // Summarize number of points for each question
        $questions = $this->instantquiz->get_entities('question');
        $points = array_fill_keys(array_keys($criteria), 0);
        foreach ($questions as $id => $question) {
            $questionpoints = $question->earned_points($this->get_answer($id));
            foreach ($points as $critid => $pts) {
                if (!empty($questionpoints[$critid])) {
                    $points[$critid] += $questionpoints[$critid];
                }
            }
        }

        // Evaluate formula for each feedback and find list of feedbacks for this instantquiz
        $feedbacks = $this->instantquiz->get_entities('feedback');
        $evaluatedfeedbacks = array();
        foreach ($feedbacks as $fid => &$feedback) {
            if ($feedback->is_applicable($points)) {
                $evaluatedfeedbacks[] = $fid;
            }
        }

        // update DB if anything changed
        $changed = false;
        if (!$this->timefinished) {
            $this->timefinished = time();
            $changed = true;
        }
        if (join(',', $evaluatedfeedbacks) !== join(',', $this->feedbacks)) {
            $this->feedbacks = $evaluatedfeedbacks;
            $changed = true;
        }
        if ($changed) {
            $this->update();
        }
    }

    /**
     * @return renderable
     */
    public function review_attempt() {
        $rv = array();
        if ($this->can_view_attempt()) {
            foreach ($this->instantquiz->get_entities('question') as $question) {
                $rv[] = $question->review($this);
            }
            $rv[] = $this->show_feedback();
        }
        return new instantquiz_collection($rv);
    }

    /**
     *
     * @return renderable
     */
    public function show_feedback() {
        $elements = array();
        if (!empty($this->feedbacks)) {
            $feedbacks = $this->instantquiz->get_entities('feedback');
            foreach ($this->feedbacks as $feedbackid) {
                if (isset($feedbacks[$feedbackid])) {
                    $elements[] = $feedbacks[$feedbackid];
                }
            }
        }
        if (empty($elements)) {
            $classname = $this->instantquiz->get_entity_class('feedback');
            $elements[] = $classname::get_default_feedback($this->instantquiz);
        }
        $elements[] = new single_button(new moodle_url('/mod/instantquiz/view.php', array('id' => $this->instantquiz->get_cm()->id)),
                get_string('back'));
        return new instantquiz_collection($elements);
    }

    /**
     *
     * @return renderable
     */
    public function continue_attempt() {
        if (!$this->can_continue_attempt()) {
            return new instantquiz_collection(array());
        }
        $formclassname = $this->instantquiz->get_entity_edit_form_class('attempt');
        $form = new $formclassname(null, $this);
        if ($form->is_cancelled()) {
            redirect(new moodle_url('/mod/instantquiz/view.php', array('id' => $this->instantquiz->get_cm()->id)));
        }
        if ($data = $form->get_data()) {
            if (empty($data->answers)) {
                $data->answers = array();
            }
            $this->answers = $data->answers;
            $this->update();
            $this->evaluate();
            if ($this->timefinished) {
                // YAY! User finished the attempt, show feedback
                return $this->show_feedback();
            }
        }
        return new instantquiz_collection($form);
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return renderable
     */
    public static function start_new_attempt($instantquiz) {
        if ($attempt = static::create($instantquiz)) {
            return $attempt->continue_attempt();
        } else {
            print_error('Not allowed'); // TODO
        }
    }

    /**
     * @return array
     */
    public static function attempts_list($instantquiz) {
        global $DB;
        $rv = array();
        if ($records = $DB->get_records_sql('SELECT * FROM {instantquiz_attempt}
            WHERE instantquizid = ?
            AND timefinished is not null
            AND overriden = 0
            ORDER BY timefinished desc', array($instantquiz->id))) {
            foreach ($records as $record) {
                $rv[] = new static($instantquiz, $record);
            }
        }
        return $rv;
    }
}
