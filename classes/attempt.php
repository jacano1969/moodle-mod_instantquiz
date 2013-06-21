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

/**
 * Contains information and useful functions to deal with one instantquiz attempt
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_attempt extends instantquiz_entity {
    var $userid;
    var $timestarted;
    var $timefinished;
    var $attemptnumber; // TODO deprecate
    /** @var int 0 or 1, default 0: says that there is another attempt that was completed after this one was completed (or after this one was started if it has not been completed yet) */
    var $overriden;
    var $answers;
    var $points;
    var $feedbacks;
    var $addinfo;

    protected static $alluserattempts = array();

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
        static::can_start_attempt($instantquiz);
        global $USER;
        $defaultvalues = new stdClass();
        $defaultvalues->userid = $USER->id;
        $defaultvalues->attemptnumber = 0; // TODO deprecate this
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
            'attemptnumber' => $this->attemptnumber, // TODO deprecate this
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
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $userid
     * @param int $attemptid if 0 will return last finished user attempt
     * @return instantquiz_attempt
     */
    public static function get_user_attempt($instantquiz, $userid, $attemptid = 0) {
        global $DB;
        $params = array('instantquizid' => $instantquiz->id);
        if ($attemptid) {
            $params['id'] = $attemptid;
        }
        if ($userid) {
            $params['userid'] = $userid;
        }
        if ($attemptid) {
            $record = $DB->get_record(static::get_table_name(), $params);
        } else if ($userid) {
            $record = $DB->get_record_sql('SELECT * FROM {'.static::get_table_name().'}
                WHERE userid = :userid
                AND instantquizid = :instantquizid AND timefinished IS NOT NULL AND overriden = 0',
                $params);
        }
        if (!empty($record)) {
            return new static($instantquiz, $record);
        }
        return null;
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $userid
     * @return instantquiz_attempt
     */
    public static function get_all_user_attempts($instantquiz, $userid) {
        global $DB;
        if (!isset(self::$alluserattempts[$userid])) {
            self::$alluserattempts[$userid] = array();
            if ($records = $DB->get_records('instantquiz_attempt',
                    array('userid' => $userid, 'instantquizid' => $instantquiz->id), 'overriden, timestarted desc')) {
                foreach ($records as $record) {
                    self::$alluserattempts[$userid][$record->id] = new static($instantquiz, $record);
                }
            }
        }
        return self::$alluserattempts[$userid];
    }

    /**
     * Checks if user can continue current attempt (i.e. attempt is not finished and not expired)
     *
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    public function can_continue_attempt($returnonly = true) {
        global $USER;
        if ($this->overriden || $USER->id != $this->userid || $this->timefinished) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('something', 'mod_instantquiz'); // TODO
            }
        }
        $instantquiz = $this->instantquiz;
        $timeopen = $instantquiz->get_attribute('timeopen');
        $timeclose = $instantquiz->get_attribute('timeclose');
        $attemptslimit = $instantquiz->get_attribute('attemptslimit');
        $attemptduration = $instantquiz->get_attribute('attemptduration');
        $now = time();
        if ($timeopen && $timeopen > $now) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('notavailable', 'error', $instantquiz->view_link());
            }
        }
        if ($timeclose && $timeclose < $now) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('notavailable', 'error', $instantquiz->view_link());
            }
        }
        if ($attemptduration && $now - $this->timestarted > $attemptduration) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('attempttimedout', 'mod_instantquiz', $instantquiz->view_link());
            }
        }
        if ($attemptslimit && static::count_user_completed_attempts($instantquiz, $USER->id) >= $attemptslimit) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('attemptslimitreached', 'mod_instantquiz', $instantquiz->view_link(), $attemptslimit);
            }
        }
        return true;
    }

    /**
     *
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    public function can_view_attempt($returnonly = true) {
        return true;
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    public static function can_start_attempt(instantquiz_instantquiz $instantquiz, $returnonly = true) {
        global $USER;
        $timeopen = $instantquiz->get_attribute('timeopen');
        $timeclose = $instantquiz->get_attribute('timeclose');
        $attemptslimit = $instantquiz->get_attribute('attemptslimit');
        $now = time();
        if ($timeopen && $timeopen > $now) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('notavailable', 'error', $instantquiz->view_link());
            }
        }
        if ($timeclose && $timeclose < $now) {
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('notavailable', 'error', $instantquiz->view_link());
            }
        }
        if (($attemptslimit && static::count_user_completed_attempts($instantquiz, $USER->id) >= $attemptslimit) ||
                ($attemptslimit == 1 && static::get_all_user_attempts($instantquiz, $USER->id))) {
            // user has already submitted the maximum number of attempts or
            // attemptslimit is 1 and user does not have any completed attempts but he has an unfinished attempt
            // (to have the correct processing of maximum attempt duration time)
            if ($returnonly) {
                return false;
            } else {
                throw new moodle_exception('attemptslimitreached', 'mod_instantquiz', $instantquiz->view_link(), $attemptslimit);
            }
        }
        return true;
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $userid
     * @return int
     */
    public static function count_user_completed_attempts(instantquiz_instantquiz $instantquiz, $userid) {
        $attempts = static::get_all_user_attempts($instantquiz, $userid);
        $cnt = 0;
        foreach ($attempts as &$attempt) {
            if ($attempt->timefinished) {
                $cnt ++;
            }
        }
        return $cnt;
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
        global $DB;
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
        // get the current attempt state
        $currentattempt = static::get_user_attempt($this->instantquiz, $this->userid);
        if (!$this->timefinished) {
            // user finished this attempt, it becomes the current
            // any other finished attempt in DB must be marked as overriden
            $DB->execute("UPDATE {".static::get_table_name()."} SET overriden = 1
                WHERE instantquizid = ? AND userid = ? AND overriden <> 1",
                    array($this->instantquiz->id, $this->userid));
            $this->timefinished = time();
            $this->overriden = 0;
            $changed = true;
        }
        if (join(',', $evaluatedfeedbacks) !== join(',', $this->feedbacks)) {
            $this->feedbacks = $evaluatedfeedbacks;
            $changed = true;
        }
        if ($changed) {
            $this->update();
            $this->instantquiz->summary->entity_updated('attempt', $currentattempt, $this);
        }
    }

    /**
     * Returns all feedbacks
     *
     * @return array array of instantquiz_feedback objects
     */
    public function get_feedbacks() {
        $feedbacks = array();
        $classname = $this->instantquiz->template. '_feedback';
        if (!empty($this->feedbacks)) {
            foreach ($this->feedbacks as $feedbackid) {
                if ($f = $classname::get($this->instantquiz, $feedbackid)) {
                    $feedbacks[] = $f;
                }
            }
        }
        if (empty($feedbacks)) {
            $feedbacks[] = $classname::get_default_feedback($this->instantquiz);
        }
        return $feedbacks;
    }

    /**
     *
     * @return renderable
     */
    public function continue_attempt() {
        // this will throw an exception if
        $this->can_continue_attempt();
        $this->instantquiz->displaymode = instantquiz_instantquiz::DISPLAYMODE_NORMAL;
        $formclassname = $this->instantquiz->template. '_attempt_form';
        $form = new $formclassname(null, $this);
        if ($form->is_cancelled()) {
            redirect(new moodle_url($this->instantquiz->view_link()));
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
                $this->instantquiz->displaymode = instantquiz_instantquiz::DISPLAYMODE_NORMAL;
                return $this;
            }
        }
        return $form;
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return renderable
     */
    public static function start_new_attempt($instantquiz) {
        $attempt = static::create($instantquiz);
        return $attempt->continue_attempt();
    }

    /**
     * Retrieves all entities from database
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return array of instantquiz_entity
     */
    public static function get_all($instantquiz) {
        global $DB;
        $rv = array();
        if ($records = $DB->get_records_sql('SELECT * FROM {'.static::get_table_name().'}
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

    /**
     * Returns list of all current attemps of all users
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return array
     */
    public static function attempts_list($instantquiz) {
        // TODO rename to get_all()
        return static::get_all($instantquiz);
    }

    /**
     * Returns the count of all current attempts of all users
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return int
     */
    public static function count_completed_attempts($instantquiz) {
        global $DB;
        return $DB->get_field('SELECT COUNT(*) FROM {instantquiz_attempt}
            WHERE instantquizid = ?
            AND timefinished is not null
            AND overriden = 0', array($instantquiz->id));
    }
}
