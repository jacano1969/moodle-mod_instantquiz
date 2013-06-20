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
 * class instantquiz_entity
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions to deal with one instantquiz entity (criterion, question, feedback)
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class instantquiz_entity implements renderable {
    /** @var entityid */
    var $id;
    /** @var entity sort order */
    var $sortorder;
    /** @var instantquiz reference to the instantquiz containing this entity */
    var $instantquiz;

    /**
     * Constructor from DB record
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param stdClass $record
     */
    abstract protected function __construct($instantquiz, $record);

    /**
     * Returns the name of DB table (used in functions get_all(), delete() and update() )
     *
     * @return string
     */
    protected static function get_table_name() {
        return null;
    }

    /**
     * Creates and saves a new instance, fills the properties with default values
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return instantquiz_entity
     */
    public static function create($instantquiz) {
        $entity = new static($instantquiz, new stdClass());
        $entity->update();
        return $entity;
    }

    /**
     * Updates or creates entry in DB
     */
    abstract public function update();

    /**
     * Deletes a record and all related records from DB
     */
    public function delete() {
        global $DB;
        $DB->delete_records($this->get_table_name(), array('id' => $this->id));
    }

    /**
     * Retrieves all entities from database
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return array of instantquiz_entity
     */
    public static final function get_all($instantquiz) {
        global $DB;
        $questions = array();
        if ($records = $DB->get_records(static::get_table_name(), array('instantquizid' => $instantquiz->id))) {
            foreach ($records as $record) {
                $questions[$record->id] = new static($instantquiz, $record);
            }
        }
        return $questions;
    }

    /**
     * Returns an entity with specified id only if it belongs to the specified quiz
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $id
     * @return instantquiz_entity
     */
    public static final function get($instantquiz, $id) {
        $all = static::get_all($instantquiz);
        if (isset($all[$id])) {
            return $all[$id];
        }
        return false;
    }
}