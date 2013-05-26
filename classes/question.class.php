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
    protected $lastmaxoptionidx;

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
     * @param instantquiz_instantquiz $instantquiz
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
            $this->options = convert_to_array(@json_decode($record['options']));
        }
        $this->lastmaxoptionidx = $this->get_max_options_idx();
        $this->addinfo = array();
        if (isset($record['addinfo'])) {
            $this->addinfo = convert_to_array(@json_decode($record['addinfo']));
        }
    }

    /**
     * Creates a question with default text
     *
     * @param instantquiz_instantquiz $instantquiz
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
     * Calculates the number of points earned for particular answer
     *
     * @param mixed $answer answer stored for this question
     * @return array array of points earned, indexed by criterion id
     */
    public function earned_points($answer) {
        if ($answer === null) {
            return array();
        }
        if (!is_array($answer)) {
            $answer = array($answer => 1);
        }
        $points = array();
        foreach ($this->options as $option) {
            if (array_key_exists($option['idx'], $answer)) {
                // get the points for an answer
                foreach ($option['points'] as $critid => $pts) {
                    if (!isset($points[$critid])) {
                        $points[$critid] = 0;
                    }
                    $points[$critid] += $pts;
                }
            }
        }
        return $points;
    }

    /**
     * Updates or creates entry in DB
     */
    public function update() {
        global $DB;
        // Set 'idx' for new options
        if (!empty($this->options) && is_array($this->options)) {
            foreach ($this->options as $i => $option) {
                if (empty($option['idx'])) {
                    $this->options[$i]['idx'] = ++$this->lastmaxoptionidx;
                }
            }
        } else {
            $this->options = array();
        }
        $record = array(
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
            $record['instantquizid'] = $this->instantquiz->id;
            $this->id = $DB->insert_record($this->get_table_name(), $record);
        }
    }

    /**
     * Calculates the maximum 'idx' property of all options
     *
     * @return int
     */
    protected function get_max_options_idx() {
        $maxidx = 0;
        if (!empty($this->options) && is_array($this->options)) {
            foreach ($this->options as $option) {
                if (!empty($option['idx']) && $option['idx'] > $maxidx) {
                    $maxidx = $option['idx'];
                }
            }
        }
        return $maxidx;
    }

    /**
     * Returns truncated and simply formatted question text to display on the manage page
     *
     * @return string
     */
    public function get_preview() {
        $preview = format_text($this->question, $this->questionformat,
            array('context' => $this->instantquiz->get_context()));
        if (!empty($this->options)) {
            $lines = array();
            foreach ($this->options as $option) {
                $lines[] = html_writer::tag('li', $option['value']);
            }
            $preview .= html_writer::tag('ul', join('', $lines));
        }
        return $preview;
    }
}
