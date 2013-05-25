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
require_once($CFG->dirroot. '/mod/instantquiz/classes/entity.class.php');

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
    var $attemptnumber; // TODO rename to sortorder?
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
     * Returns truncated and simply formatted criterion name to display on the manage page
     *
     * @return string
     */
    public function get_preview() {
        return null;
    }

    public static function get_user_attempt($instantquiz, $userid, $attemptid = 0) {
        global $DB;
        if ($attemptid) {
            $record = $DB->get_record('instantquiz_attempt', array('userid' => $userid, 'id' => $attemptid));
        } else {
            if ($records = $DB->get_records('instantquiz_attempt', array('userid' => $userid), 'attemptnumber desc', '*', 0, 1)) {
                $record = reset($records);
            }
        }
        if (!empty($record)) {
            return new static($instantquiz, $record);
        }
    }

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

    public function can_continue_attempt() {
        return !$this->timefinished;
    }

    public function evaluate() {
        $this->timefinished = time();
    }

    /**
     *
     * @return renderable
     */
    public function show_feedback() {
        return new instantquiz_collection('Thank you!'); // TODO
    }

    /**
     *
     * @return renderable
     */
    public function continue_attempt() {
        if ($this->timefinished) {
            return $this->show_feedback();
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
            $this->evaluate();
            $this->update();
            if ($this->timefinished) {
                // YAY! User finished the attempt, show feedback
                return $this->show_feedback();
            }
        }
        return new instantquiz_collection($form);
    }
}
