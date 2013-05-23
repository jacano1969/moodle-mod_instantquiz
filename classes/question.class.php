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
 * class instantquiz_question
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/mod/instantquiz/classes/entity.class.php');

/**
 * Contains information and useful functions to deal with one instantquiz question
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_question extends instantquiz_entity {
    public $question;
    public $questionformat;
    public $options;
    public $addinfo;

    /**
     * Returns the name of DB table (used in functions get_all() and update() )
     *
     * @return string
     */
    protected static function get_table_name() {
        return 'instantquiz_question';
    }

    /**
     * Constructor from DB record
     *
     * @param instantquiz $instantquiz
     * @param stdClass $record
     */
    protected function __construct($instantquiz, $record) {
        $record = (array)$record;
        $record = $record + array('id' => null, 'question' => '', 'questionformat' => FORMAT_MOODLE, 'sortorder' => 0);
        $this->instantquiz = $instantquiz;
        $this->id = $record['id'];
        $this->question = $record['question'];
        $this->questionformat = $record['questionformat'];
        $this->sortorder = $record['sortorder'];
        $this->options = array();
        if (isset($record['options'])) {
            if (($options = @json_decode($record['options'], true)) && is_array($options)) {
                foreach ($options as $option) {
                    $this->options[] = new instantquiz_question_option($option);
                }
            }
        }
        $this->addinfo = new stdClass();
        if (isset($record['addinfo'])) {
            $this->addinfo = @json_decode($record['addinfo']);
        }
    }

    /**
     * Creates a question with default text
     *
     * @param instantquiz $instantquiz
     * @return instantquiz_question
     */
    public static function create($instantquiz) {
        $defaultvalues = new stdClass();
        $all = self::get_all($instantquiz);
        $defaultvalues->sortorder = count($all);
        $defaultvalues->question = get_string('defquestion', 'mod_instantquiz', $defaultvalues->sortorder + 1);
        $defaultvalues->questionformat = FORMAT_MOODLE;
        $entity = new static($instantquiz, $defaultvalues);
        $entity->update();
        return $entity;
    }

    /**
     * Updates or creates entry in DB
     */
    public function update() {
        global $DB;
        $record = array(
            'instantquizid' => $this->instantquiz->id,
            'question' => $this->question,
            'questionformat' => $this->questionformat,
            'sortorder' => $this->sortorder,
            'options' => json_encode($this->options),
            'addinfo' => json_encode($this->addinfo)
        );
        if (!empty($this->id)) {
            $record['id'] = $this->id;
            $DB->update_record($this->get_table_name(), $record);
        } else {
            $this->id = $DB->insert_record($this->get_table_name(), $record);
        }
    }

    /**
     * Adds an option if it does not exist yet
     *
     * @param string $value
     * @return instantquiz_question_option existing or just added option object
     */
    public function add_option($value) {
        if (($option = $this->get_option($value)) === false) {
             $option = new instantquiz_question_option();
             $option->value = $value;
             $this->options[] = $option;
        }
        return $option;
    }

    /**
     * Checks if option exists, if yes returns the object
     *
     * @param string $value
     * @return false|instantquiz_question_option option object or false if does not exist
     */
    public function get_option($value) {
        foreach ($this->options as &$option) {
            if ($option->value === $value) {
                return $option;
            }
        }
        return false;
    }

    public function set_option_evaluation($value, $evid, $points) {
        $this->add_option($value)->set_evaluation($evid, $points);
    }

    /**
     * Returns truncated and simply formatted question text to display on the manage page
     *
     * @return string
     */
    public function get_preview() {
        return format_text($this->question, $this->questionformat,
            array('context' => $this->instantquiz->get_context()));
    }

    /**
     * Returns truncated and simply formatted additional info text to display on the manage page
     *
     * @return string
     */
    public function get_addinfo_preview() {
        return print_r($this->addinfo, true);
    }
}

class instantquiz_question_option extends stdClass {

    public function __construct($option = array()) {
        if (isset($option->value)) {
            $this->value = $option->value;
        }
        $this->points = array();
        if (isset($option->points) && is_array($option->points)) {
            foreach ($option->points as $evid => $points) {
                $this->set_evaluation($evid, $points);
            }
        }
    }

    public function set_evaluation($evid, $points) {
        if (!$points) {
            unset($this->points[$evid]);
        } else {
            $needresorting = isset($this->points[$evid]);
            $this->points[$evid] = $points;
            if ($needresorting) {
                ksort($this->points, SORT_NUMERIC);
            }
        }
    }
}
