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
    /** @var array|null */
    protected $addinfo;
    /** @var */
    var $displaymode = 'attemptmode';

    const DISPLAYMODE_NORMAL  = 'attemptmode'; // During attempt
    const DISPLAYMODE_REVIEW  = 'reviewmode'; // When reviewing the attempt
    const DISPLAYMODE_PREVIEW = 'previewmode'; // Preview
    const DISPLAYMODE_EDIT    = 'managemode'; // Editng mode, on manage page

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
     * Returns the instantquiz attribute (set when editing the module form)
     *
     * @param string $key
     * @param mixed $defaultvalue
     * @return mixed
     */
    public function get_attribute($key, $defaultvalue = null) {
        if ($this->addinfo === null) {
            $this->addinfo = array();
            if (!empty($this->record->addinfo) && $addinfo = @json_decode($this->record->addinfo)) {
                $this->addinfo = convert_to_array($addinfo);
            }
        }
        if (array_key_exists($key, $this->addinfo)) {
            return $this->addinfo[$key];
        } else {
            return $defaultvalue;
        }
    }

    /**
     * Allows to add additional template settings to the module edit form
     *
     * Very important to return an array of added elements
     *
     * @param MoodleQuickForm $mform
     * @patam stdClass|modinfo $cm course module being updated, null if it is being created
     * @return array array of elements that were added to the form
     */
    public static function edit_form_elements(MoodleQuickForm $mform, $cm) {
        $elements = array();
        // Example:
        // $elements[] = $mform->addElement('text', 'elementname', get_string('elementname', 'instantquiztmpl_xxx'));
        $elements[] = $mform->addElement('select', 'addinfo_attemptslimit', get_string('attemptslimit', 'mod_instantquiz'),
                array(0 => get_string('unlimited')) + array_combine(range(1, INSTANTQUIZ_MAX_ATTEMPT_OPTION), range(1, INSTANTQUIZ_MAX_ATTEMPT_OPTION)));
        $mform->addHelpButton('addinfo_attemptslimit', 'attemptslimit', 'instantquiz');
        $mform->setType('addinfo_attemptslimit', PARAM_INT);

        $elements[] = $mform->addElement('duration', 'addinfo_attemptduration', get_string('attemptduration', 'mod_instantquiz'),
                array('optional' => true));
        $mform->addHelpButton('addinfo_attemptduration', 'attemptduration', 'instantquiz');
        $mform->setType('addinfo_attemptduration', PARAM_INT);

        //-------------------------------------------------------------------------------
        $elements[] = $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));

        $elements[] = $mform->addElement('date_time_selector', 'addinfo_timeopen',
                get_string('timeopen', 'instantquiz'),
                array('optional' => true));
        $mform->addHelpButton('addinfo_timeopen', 'timeopen', 'instantquiz');
        $mform->setType('addinfo_timeopen', PARAM_INT);
        $mform->setDefault('addinfo_timeopen', time());

        $elements[] = $mform->addElement('date_time_selector', 'addinfo_timeclose',
                get_string('timeclose', 'instantquiz'),
                array('optional' => true));
        $mform->addHelpButton('addinfo_timeclose', 'timeclose', 'instantquiz');
        $mform->setType('addinfo_timeclose', PARAM_INT);
        $mform->setDefault('addinfo_timeclose', time());

        //-------------------------------------------------------------------------------
        $elements[] = $mform->addElement('header', 'displayresulthdr', get_string('displayresult', 'instantquiz'));
        $elements[] = $mform->addElement('advcheckbox', 'addinfo_resultafteranswer', get_string('resultafteranswer', 'mod_instantquiz'));
        $mform->addHelpButton('addinfo_resultafteranswer', 'resultafteranswer', 'instantquiz');
        $mform->setType('addinfo_resultafteranswer', PARAM_INT);
        $mform->setDefault('addinfo_resultafteranswer', 1);

        $elements[] = $mform->addElement('date_time_selector', 'addinfo_resultmindate', get_string('resultmindate', 'instantquiz'),
                array('optional' => true));
        $mform->addHelpButton('addinfo_resultmindate', 'resultmindate', 'instantquiz');
        $mform->setType('addinfo_resultmindate', PARAM_INT);
        $mform->setDefault('addinfo_resultmindate', time());

        $elements[] = $mform->addElement('text', 'addinfo_resultminanswers', get_string('resultminanswers', 'instantquiz'));
        $mform->addHelpButton('addinfo_resultminanswers', 'resultminanswers', 'instantquiz');
        $mform->setType('addinfo_resultminanswers', PARAM_INT);
        $mform->setDefault('addinfo_resultminanswers', 0);

        return $elements;
    }

    /**
     * Pre-process data before setting to module edit form
     *
     * @param array $default_values passed by reference
     */
    public static function edit_form_data_preprocessing(&$default_values) {
        if (!empty($default_values['addinfo'])) {
            $addinfo = @json_decode($default_values['addinfo']);
            if (!empty($addinfo)) {
                $addinfo = convert_to_array($addinfo);
                foreach ($addinfo as $key => $value) {
                    $default_values['addinfo_'. $key] = $value;
                }
            }
        }
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

        $addinfo = array();
        foreach ($data as $key => $value) {
            if (preg_match('/^addinfo_(.+)$/', $key, $matches)) {
                $addinfo[$matches[1]] = $value;
            }
        }
        $data->addinfo = json_encode($addinfo);

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

        // Template can not be changed after instantquiz was created
        unset($data->template);

        $data->timemodified = time();

        $addinfo = $this->addinfo;
        foreach ($data as $key => $value) {
            if (preg_match('/^addinfo_(.+)$/', $key, $matches)) {
                $addinfo[$matches[1]] = $value;
            }
        }
        $data->addinfo = json_encode($addinfo);

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

    public function attempt_link($params = array()) {
        return new moodle_url('/mod/instantquiz/attempt.php', array('cmid' => $this->cm->id) + $params);
    }

    public function results_link($params = array()) {
        return new moodle_url('/mod/instantquiz/results.php', array('cmid' => $this->cm->id) + $params);
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
        $classname = $this->template. '_'. $entitytype;
        return $classname::create($this);
    }

    /**
     * Returns all entities of specified type used in instantquiz
     *
     * @return array of instantquiz_criterion
     */
    public function get_entities($entitytype) {
        $classname = $this->template. '_'. $entitytype;
        return $classname::get_all($this);
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
        $classname = $this->template. '_'. $entitytype;
        return $classname::get($this, $entityid);
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

    /**
     * Content to be displayed on the manage page (/mod/instantquiz/manage.php)
     *
     * @return renderable
     */
    public function manage_page() {
        $cmd = optional_param('cmd', null, PARAM_ALPHA);
        $entity = optional_param('entity', null, PARAM_ALPHA);
        $entityids = optional_param_array('entityid', array(), PARAM_INT);

        // Process actions on entities (add, delete, edit)
        if ($cmd === 'add' && !empty($entity) && $this->add_entity($entity)) {
            redirect($this->manage_link(array('cmd' => 'list', 'entity' => $entity)));
        } else if ($cmd === 'delete' && !empty($entity) && !empty($entityids)) {
            $this->delete_entities($entity, array_keys($entityids));
            redirect($this->manage_link(array('cmd' => 'list', 'entity' => $entity)));
        } else if ($cmd === 'edit' && !empty($entity)) {
            $entities = $this->get_entities($entity);
            if (!empty($entityids)) {
                // Edit only specified entities
                $entities = array_intersect_key($entities, $entityids);
            }
            if (!empty($entities)) {
                $formclass = $this->template. '_'. $entity. '_form';
                $form = new $formclass(null, $entities);
                if ($form->is_cancelled()) {
                    redirect($this->manage_link(array('cmd' => 'list', 'entity' => $entity)));
                } else if ($data = $form->get_data()) {
                    $this->update_entities($entity, $data);
                    redirect($this->manage_link(array('cmd' => 'list', 'entity' => $entity)));
                }
            }
        }

        $objects = array();

        // Print manage menu (tabs)
        $tabrows = array();
        foreach (array('criterion', 'question', 'feedback') as $key) {
            $linkedwhenselected = ($cmd !== 'list');
            $tabrows[] = new tabobject($key, $this->manage_link(array('cmd' => 'list', 'entity' => $key)), $key  /* TODO string */,
                    '', $linkedwhenselected);
        }
        $objects[] = new instantquiz_tabs(array($tabrows), $entity);

        // Print lists of object (if applicable)
        if ($cmd === 'list' && in_array($entity, array('question', 'feedback', 'criterion'))) {
            $this->displaymode = self::DISPLAYMODE_EDIT;
            $entitylistclassname = $this->template.'_entitylist';
            $objects[] = new $entitylistclassname($this, $entity);
        }

        // Print form if present
        if (!empty($form)) {
            $objects[] = $form;
        }
        return new instantquiz_collection($objects);
    }

    /**
     * Content to be displayed on the main page of the module (/mod/instantquiz/view.php)
     *
     * @return renderable
     */
    public function view_page() {
        global $USER;
        $elements = array();
        $classname = $this->template. '_attempt';
        if ($classname::can_start_attempt($this)) {
            $elements[] = new single_button($this->attempt_link(array('attemptid' => 'startnew')),
                    get_string('startattempt', 'mod_instantquiz'));
        }
        $attempts = $classname::get_all_user_attempts($this, $USER->id);
        foreach ($attempts as &$attempt) {
            $obj = (object)array('attemptnumber' => $attempt->attemptnumber,
                'timestarted' => userdate($attempt->timestarted));
            if ($attempt->can_continue_attempt()) {
                $label = get_string('continueattempt', 'mod_instantquiz', $obj);
                $elements[] = new single_button($this->attempt_link(array('attemptid' => $attempt->id)), $label);
            } else if ($attempt->can_view_attempt()) {
                $label = get_string('viewattempt', 'mod_instantquiz', $obj);
                $elements[] = new single_button($this->results_link(array('attemptid' => $attempt->id)), $label);
            }
        }
        return new instantquiz_collection($elements);
    }

    /**
     * Content to be displayed on the attempt page of the module (/mod/instantquiz/attempt.php)
     *
     * @return renderable
     */
    public function attempt_page() {
        global $USER;
        $classname = $this->template. '_attempt';
        $attemptid = required_param('attemptid', PARAM_ALPHANUM);
        $viewurl = new moodle_url('/mod/instantquiz/view.php', array('id' => $this->cm->id));
        if ((int)$attemptid) {
            // Validate that it belongs to this user and can be continued
            $attempt = $classname::get_user_attempt($this, $USER->id, (int)$attemptid);
            if (!$attempt) {
                print_error('attemptnotfound', 'mod_instantquiz', $viewurl);
            }
            if ($attempt->can_continue_attempt()) {
                return $attempt->continue_attempt();
            }
        } else if ($attemptid === 'startnew' && $classname::can_start_attempt($this)) {
            // Create new attempt
            return $classname::start_new_attempt($this);
        }
        redirect($viewurl);
    }

    /**
     * @return renderable
     */
    public function results_page() {
        $attemptid = optional_param('attemptid', null, PARAM_RAW);
        $userid = optional_param('userid', null, PARAM_INT);
        $classname = $this->template. '_attempt';
        if ((int)$attemptid && ($attempt = $classname::get_user_attempt($this, $userid, $attemptid))) {
            if ($attempt->can_view_attempt()) {
                $this->displaymode = self::DISPLAYMODE_REVIEW;
                return new instantquiz_collection(array($attempt,
                    new single_button(new moodle_url('/mod/instantquiz/view.php',
                            array('id' => $this->get_cm()->id)),
                            get_string('back'))));
            } else {
                print_error('Not allowd'); // TODO
            }
        } else if ($attemptid === 'all' && $userid) {
            $attempts = $classname::get_all_user_attempts($this, $userid);
        } else {
            $attempts = $classname::attempts_list($this);
        }
        $data = array();
        foreach ($attempts as $attempt) {
            if ($attempt->can_view_attempt()) {
                $this->displaymode = self::DISPLAYMODE_PREVIEW; // TODO ???
                $data[] = new html_table_row(array(
                    html_writer::link($this->results_link(array('attemptid' => $attempt->id)), 'USER '.$attempt->userid.' at '.
                            userdate($attempt->timefinished))
                ));
            }
        }
        if (!empty($data)) {
            $table = new instantquiz_table();
            $table->data = $data;
            return $table;
        } else {
            return new instantquiz_collection(array());
        }
    }
}
