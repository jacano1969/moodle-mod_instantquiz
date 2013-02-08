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
 * class instantquiz_evaluation
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions to deal with one instantquiz evaluation criterion
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_evaluation {
    var $id;
    var $sortorder;
    var $name;
    var $addinfo;

    /** @var instantquiz reference to the instantquiz containing this evaluation criterion */
    var $instantquiz;

    /**
     * Retrieves all evaluation criteria from database
     *
     * @param instantquiz $instantquiz
     * @return array of instantquiz_evaluation
     */
    public static function get_all($instantquiz) {
        global $DB;
        $records = $DB->get_records('instantquiz_evaluation',
                array('instantquizid' => $instantquiz->id),
                'sortorder, id', '*');
        $rv = array();
        foreach ($records as $record) {
            $rv[] = new instantquiz_evaluation($record, $instantquiz);
        }
        return $rv;
    }

    /**
     * Creates an evaluation criterion with default name
     *
     * @param instantquiz $instantquiz
     * @return instantquiz_evaluation
     */
    public static function create_empty($instantquiz) {
        $record = new stdClass();
        $all = self::get_all($instantquiz);
        $record->sortorder = 0;
        foreach ($all as $ev) {
            $record->sortorder = $ev->sortorder + 1;
        }
        $record->name = get_string('defevaluationname', 'mod_instantquiz', $record->sortorder + 1);
        $evaluation = new instantquiz_evaluation($record, $instantquiz);
        $evaluation->update();
        return $evaluation;
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
            'name' => $this->name,
            'addinfo' => $this->addinfo
        );
        if ($this->id) {
            $record['id'] = $this->id;
            $DB->update_record('instantquiz_evaluation', $record);
        } else {
            $this->id = $DB->insert_record('instantquiz_evaluation', $record);
        }
    }
}
