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
 * class instantquiz_feedback
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions to deal with one instantquiz feedback
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_feedback {
    var $id;
    var $sortorder;
    var $feedback;
    var $feedback_format;
    var $addinfo;

    /** @var instantquiz reference to the instantquiz containing this evaluation criterion */
    var $instantquiz;

    /**
     * Retrieves all feedbacks from database
     *
     * @param instantquiz $instantquiz
     * @return array of instantquiz_feedback
     */
    public static function get_all($instantquiz) {
        global $DB;
        $records = $DB->get_records('instantquiz_feedback',
                array('instantquizid' => $instantquiz->id),
                'sortorder, id', '*');
        $rv = array();
        foreach ($records as $record) {
            $rv[] = new instantquiz_feedback($record, $instantquiz);
        }
        return $rv;
    }

    /**
     * Creates a feedback with default text
     *
     * @param instantquiz $instantquiz
     * @return instantquiz_feedback
     */
    public static function create_empty($instantquiz) {
        $record = new stdClass();
        $all = self::get_all($instantquiz);
        $record->sortorder = 0;
        foreach ($all as $ev) {
            $record->sortorder = $ev->sortorder + 1;
        }
        $record->feedback = get_string('deffeedback', 'mod_instantquiz', $record->sortorder + 1);
        $feedback = new instantquiz_feedback($record, $instantquiz);
        $feedback->update();
        return $feedback;
    }

    /**
     * Constructor
     *
     * @param stdClass $record
     * @param instantquiz $instantquiz
     */
    protected function __construct($record, $instantquiz) {
        $this->instantquiz = $instantquiz;
        foreach ($record as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Updates or creates entry in DB
     */
    public function update() {
        global $DB;
        $record = array(
            'instantquizid' => $this->instantquiz->id,
            'sortorder' => $this->sortorder,
            'feedback' => $this->feedback,
            'feedback_format' => $this->feedback_format,
            'addinfo' => $this->addinfo
        );
        if ($this->id) {
            $record['id'] = $this->id;
            $DB->update_record('instantquiz_feedback', $record);
        } else {
            $this->id = $DB->insert_record('instantquiz_feedback', $record);
        }
    }

    /**
     * Returns truncated and simply formatted feedback text to display on
     * the manage page
     *
     * @return string
     */
    public function get_preview() {
        return format_text($this->feedback, $this->feedback_format,
            array('context' => $this->instantquiz->get_context()));
    }
}