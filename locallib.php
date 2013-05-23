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
 * Internal library of functions for module instantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions for one instance of instant quiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz {
    /** @var stdClass record from DB table instantquiz */
    protected $record;
    /** @var stdClass|cm_info information about course module */
    protected $cm;
    /** @var instantquiz_tmpl */
    protected $template;

    /**
     * Constructor
     *
     * @param stdClass|cm_info $cm information about course module
     */
    public function __construct($cm) {
        global $DB;
        $this->cm = $cm;
        $this->record = $DB->get_record('instantquiz', array('id' => $cm->instance), '*', MUST_EXIST);
    }

    /**
     * Magic method to get instantquiz properties
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->record->$name;
    }

    /**
     * Returns the module context
     *
     * @return context
     */
    public function get_context() {
        return context_module::instance($this->cm->id);
    }

    /**
     * Returns the template object
     *
     * @return instantquiz_tmpl
     */
    public function get_template() {
        global $CFG;
        if (!isset($this->template)) {
            require_once($CFG->dirroot.'/mod/instantquiz/templatebase.php');
            if (!empty($this->record->template) &&
                    class_exists($this->record->template) &&
                    is_subclass_of($this->record->template, 'instantquiz_tmpl')) {
                $classname = $this->record->template;
            } else {
                $classname = 'instantquiz_tmpl';
            }
            $this->template = new $classname($this);
        }
        return $this->template;
    }

    /**
     * Returns a link to the manage page with the given arguments
     *
     * @param array $params
     * @return moodle_url
     */
    public function manage_link($params = array()) {
        return new moodle_url('/mod/instantquiz/manage.php', array('id' => $this->cm->id) + $params);
    }

    /**
     * Returns a classname (and loads the appropriate php class) for specified entity (question, feedback, evaluation)
     *
     * @param string $entitytype
     * @return string|null
     */
    public function get_entity_class($entitytype) {
        global $CFG;
        if ($entitytype === 'question') {
            require_once($CFG->dirroot. '/mod/instantquiz/classes/question.class.php');
            return 'instantquiz_question';
        } else if ($entitytype === 'feedback') {
            require_once($CFG->dirroot. '/mod/instantquiz/classes/feedback.class.php');
            return 'instantquiz_feedback';
        } else if ($entitytype === 'evaluation') {
            require_once($CFG->dirroot. '/mod/instantquiz/classes/evaluation.class.php');
            return 'instantquiz_evaluation';
        } else {
            return null;
        }
    }

    /**
     * Creates an empty entity (question, evaluation, feedback)
     *
     * @return instantquiz_entity
     */
    public function add_entity($entitytype) {
        if ($classname = $this->get_entity_class($entitytype)) {
            return $classname::create($this);
        }
        return null;
    }

    /**
     * Returns all entities of specified type used in instantquiz
     *
     * @return array of instantquiz_evaluation
     */
    public function get_entities($entitytype) {
        if ($classname = $this->get_entity_class($entitytype)) {
            return $classname::get_all($this);
        }
        return array();
    }

    /**
     * Returns one instance of entity used in instantquiz
     *
     * @return array of instantquiz_evaluation
     */
    public function get_entity($entitytype, $entityid) {
        if ($classname = $this->get_entity_class($entitytype)) {
            return $classname::get($this, $entityid);
        }
        return null;
    }
}
