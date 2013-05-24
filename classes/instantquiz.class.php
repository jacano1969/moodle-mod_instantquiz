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
 * class instantquiz_instantquiz
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
class instantquiz_instantquiz {
    /** @var stdClass record from DB table instantquiz */
    protected $record;
    /** @var stdClass|cm_info information about course module */
    protected $cm;

    /**
     * Constructor
     *
     * @param stdClass|cm_info $cm information about course module
     * @param stdClass $record record from DB table {instantquiz]
     */
    public function __construct($cm, $record = null) {
        global $DB;
        $this->cm = $cm;
        if (empty($record)) {
            $record = $DB->get_record('instantquiz', array('id' => $cm->instance), '*', MUST_EXIST);
        }
        $this->record = $record;
    }

    /**
     * Allows to add additional template settings to the module edit form
     *
     * Very important to return an array of added elements
     *
     * @param mod_instantquiz_mod_form $mform
     * @return array array of elements that were added to the form
     */
    public static function edit_form_elements($mform) {
        $elements = array();
        // Example:
        // $elements[] = $mform->addElement('text', 'elementname', get_string('elementname', 'instantquiztmpl_xxx')),
        return $elements;
    }

    /**
     * Saves a new instance of the instantquiz into the database
     *
     * Given an object containing all the necessary data,
     * (defined by the form in mod_form.php) this function
     * will create a new instance and return the id number
     * of the new instance.
     *
     * @param stdClass $data An object from the form in mod_form.php
     * @param mod_instantquiz_mod_form $mform
     * @return int The id of the newly inserted instantquiz record
     */
    public static function create(stdClass $data, mod_instantquiz_mod_form $mform = null) {
        global $DB;

        $data->timecreated = time();
        $data->timemodified = time();

        // TODO

        return $DB->insert_record('instantquiz', $data);
    }

    /**
     * Updates an instance of the instantquiz in the database
     *
     * Given an object containing all the necessary data,
     * (defined by the form in mod_form.php) this function
     * will update an existing instance with new data.
     *
     * @param stdClass $data An object from the form in mod_form.php
     * @param mod_instantquiz_mod_form $mform
     * @return boolean Success/Fail
     */
    public function update(stdClass $data, mod_instantquiz_mod_form $mform = null, $previoustemplate = false) {
        global $DB;

        // Check if the template is changed
        if ($data->template !== $this->record->template) {
            $DB->update_record('instantquiz', array('id' => $this->record->id, 'template' => $data->template));
            $newobject = instantquiz_get_instantquiz($this->cm);
            $newobject->update($data, $mform, $this->record->template);
            // When overwriting include here deleting of template-specific data
            return true;
        }

        $data->timemodified = time();

        // TODO

        return $DB->update_record('instantquiz', $data);
    }

    /**
     * Removes an instance of the instantquiz from the database
     *
     * Given an ID of an instance of this module,
     * this function will permanently delete the instance
     * and any data that depends on it.
     *
     * @param int $id Id of the module instance
     * @return boolean Success/Failure
     */
    public function delete() {
        global $DB;

        // Delete any dependent records here
        $DB->delete_records('instantquiz_answer', array('instantquizid' => $this->record->id));
        $DB->delete_records('instantquiz_feedback', array('instantquizid' => $this->record->id));
        $DB->delete_records('instantquiz_criterion', array('instantquizid' => $this->record->id));
        $DB->delete_records('instantquiz_question', array('instantquizid' => $this->record->id));

        $DB->delete_records('instantquiz', array('id' => $this->record->id));

        return true;
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
     * Returns the course module object used in constructor
     *
     * @return stdClass|cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Returns a link to the manage page with the given arguments
     *
     * @param array $params
     * @return moodle_url
     */
    public function manage_link($params = array()) {
        return new moodle_url('/mod/instantquiz/manage.php', array('cmid' => $this->cm->id) + $params);
    }

    /**
     * Given the name of the template tries to locate a main instantquiz class for it
     *
     * @param string $template
     * @return string name of the class
     */
    public static final function get_instantquiz_class($template) {
        return self::locate_template_class($template, 'instantquiz');
    }

    /**
     * Loads file and returns the appropriate classname
     *
     * Tries to find the class with name [template]_[classsuffix] in file
     * [templateplugindir]/classes/[classsuffix].class.php
     * If it fails, tries to locate the class instantquiz_[classsuffix]
     * in file /mod/instantquiz/template/classes/[classsuffix].class.php
     * If neither is found returns null
     *
     * @param string $template full frankenstyle name of the instantquiztmpl plugin
     * @param string $classsuffix
     * @return string|null
     */
    protected static final function locate_template_class($template, $classsuffix) {
        global $CFG;
        static $locatedclasses = array();
        $defclassname = 'instantquiz_'. $classsuffix;
        if (strlen($template)) {
            $classname = $template. '_'. $classsuffix;
        } else {
            $classname = $defclassname;
        }
        if (array_key_exists($classname, $locatedclasses)) {
            return $locatedclasses[$classname];
        }

        if (class_exists($classname)) {
            return $locatedclasses[$classname] = $classname;
        }

        if (strlen($template)) {
            $filepath = $CFG->dirroot.'/mod/instantquiz/template/'.
                    preg_replace('/^instantquiztmpl_/', '', $template).
                    '/classes/'.$classsuffix.'.class.php';
            if (file_exists($filepath)) {
                require_once($filepath);
                if (class_exists($classname)) {
                    return $locatedclasses[$classname] = $classname;
                }
            }
        }

        $deffilepath = $CFG->dirroot. '/mod/instantquiz/classes/'.$classsuffix.'.class.php';
        if (file_exists($deffilepath)) {
            require_once($deffilepath);
            if (class_exists($defclassname)) {
                return $locatedclasses[$classname] = $defclassname;
            }
        }
        return $locatedclasses[$classname] = null;
    }

    /**
     * Returns a classname (and loads the appropriate php file) for specified entity (question, feedback, criterion)
     *
     * @param string $entitytype
     * @return string|null
     */
    public function get_entity_class($entitytype) {
        return $this->locate_template_class($this->record->template, $entitytype);
    }

    /**
     * Returns a classname (and loads the appropriate php file) for specified entity edit form (question, feedback, criterion)
     *
     * @param string $entitytype
     * @return string|null
     */
    public function get_entity_edit_form_class($entitytype) {
        return $this->locate_template_class($this->record->template, $entitytype. '_form');
    }

    /**
     * Updates multiple entities with the results of the corresponding form
     *
     * @param string $entitytype
     * @param stdClass $data the data from moodleform::get_data(), see get_entity_edit_form_class()
     *    for a class name responsible for the entity edit form
     */
    public function update_entities($entitytype, $data) {
        $entities = $this->get_entities($entitytype);
        foreach ($entities as $id => &$entity) {
            if (!empty($data->entityid[$id])) {
                // this entity could have beed modified
                foreach ($data as $key => $subdata) {
                    if (is_array($subdata) && isset($subdata[$id]) && property_exists($entity, $key)) {
                        $entity->$key = $subdata[$id];
                    }
                }
                $entity->update();
            }
        }
    }

    /**
     * Creates an empty entity (question, criterion, feedback)
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
     * @return array of instantquiz_criterion
     */
    public function get_entities($entitytype) {
        if ($classname = $this->get_entity_class($entitytype)) {
            return $classname::get_all($this);
        }
        return array();
    }

    /**
     * Delete entities
     *
     * @param string $entitytype
     * @param array $entityids
     */
    public function delete_entities($entitytype, $entityids) {
        $all = $this->get_entities($entitytype);
        foreach ($all as $id => &$entity) {
            if (in_array($id, $entityids)) {
                $entity->delete();
            }
        }
    }

    /**
     * Returns one instance of entity used in instantquiz
     *
     * @return array of instantquiz_criterion
     */
    public function get_entity($entitytype, $entityid) {
        if ($classname = $this->get_entity_class($entitytype)) {
            return $classname::get($this, $entityid);
        }
        return null;
    }

    /**
     * Returns the renderer instance
     *
     * This function checks if file renderer.php is present in plugin directory
     * and if it does it assumes that it contains a proper renderer class.
     *
     * Template plugins can overwrite this method but it is usually not needed.
     *
     * @return plugin_renderer_base
     */
    public function get_renderer() {
        global $PAGE, $CFG;
        if (strlen($this->record->template)) {
            $filepath = $CFG->dirroot.'/mod/instantquiz/template/'.
                    preg_replace('/^instantquiztmpl_/', '', $this->record->template).
                    '/renderer.php';
            if (file_exists($filepath)) {
                return $PAGE->get_renderer($this->record->template);
            }
        }
        return $PAGE->get_renderer('mod_instantquiz');
    }
}
