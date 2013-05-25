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
require_once($CFG->dirroot. '/mod/instantquiz/classes/entity.class.php');

/**
 * Contains information and useful functions to deal with one instantquiz feedback
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_feedback extends instantquiz_entity {
    var $feedback;
    var $feedbackformat;
    var $addinfo;

    /**
     * Returns the name of DB table (used in functions get_all() and update() )
     *
     * @return string
     */
    public static function get_table_name() {
        return 'instantquiz_feedback';
    }

    /**
     * Creates a feedback with default text
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return instantquiz_feedback
     */
    public static function create($instantquiz) {
        $defaultvalues = new stdClass();
        $all = self::get_all($instantquiz);
        $defaultvalues->sortorder = count($all);
        $defaultvalues->feedback = get_string('deffeedback', 'mod_instantquiz', $defaultvalues->sortorder + 1);
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
        foreach ($record as $key => $value) {
            if (property_exists($this, $key) && $key !== 'addinfo') {
                $this->$key = $value;
            }
        }
        $this->addinfo = array();
        if (!empty($record->addinfo) && $addinfo = @json_decode($record->addinfo)) {
            $this->addinfo = convert_to_array($addinfo);
        }
    }

    /**
     * Updates or creates entry in DB
     */
    public function update() {
        global $DB;
        $record = array(
            'sortorder' => $this->sortorder,
            'feedback' => $this->feedback,
            'feedbackformat' => $this->feedbackformat,
            'addinfo' => json_encode($this->addinfo)
        );
        if ($this->id) {
            $record['id'] = $this->id;
            $DB->update_record($this->get_table_name(), $record);
        } else {
            $record['instantquizid'] = $this->instantquiz->id;
            $this->id = $DB->insert_record($this->get_table_name(), $record);
        }
    }

    /**
     * Returns truncated and simply formatted feedback text to display on the manage page
     *
     * @return string
     */
    public function get_preview() {
        $preview = format_text($this->feedback, $this->feedbackformat,
            array('context' => $this->instantquiz->get_context()));
        if (!empty($this->addinfo['formula'])) {
            $preview .= '<div><b>'. $this->addinfo['formula']. '</b></div>';
        }
        return $preview;
    }
}