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
     * @patam stdClass|modinfo $cm course module being updated, null if it is being created
     * @return array array of elements that were added to the form
     */
    public static function edit_form_elements($mform, $cm) {
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

        // Template can not be changed after instantquiz was created
        unset($data->template);

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
        if ($cmd === 'list') {
            if ($entity === 'question') {
                $objects[] = $this->list_questions();
            } else if ($entity === 'criterion') {
                $objects[] = $this->list_criterions();
            } else if ($entity === 'feedback') {
                $objects[] = $this->list_feedbacks();
            }
        }

        // Print form if present
        if (!empty($form)) {
            $objects[] = $form;
        }
        return new instantquiz_collection($objects);
    }

    /**
     * Renders html for criteria list on manage page
     *
     * @return renderable
     */
    protected function list_criterions() {
        $all = $this->get_entities('criterion');
        $objects = array();
        $cnt = 0;
        if (count($all)) {
            $table = new instantquiz_table();
            $table->head = array('#',
                get_string('criterion_name', 'mod_instantquiz'),
                get_string('edit'),
                get_string('delete'));
            $table->data = array();
            foreach ($all as $e) {
                $table->data[] = array(++$cnt, $e->get_preview(),
                    html_writer::link($this->manage_link(array('cmd' => 'edit', 'entity' => 'criterion', 'entityid['.$e->id.']' => 1)), get_string('edit')),
                    html_writer::link($this->manage_link(array('cmd' => 'delete', 'entity' => 'criterion', 'entityid['.$e->id.']' => 1)), get_string('delete')));
            }
            $objects[] = $table;
            $objects[] = new single_button($this->manage_link(array('cmd' => 'edit', 'entity' => 'criterion')),
                    get_string('edit'));
        }
        $objects[] = new single_button($this->manage_link(array('cmd' => 'add', 'entity' => 'criterion')),
                get_string('addcriterion', 'mod_instantquiz'));
        return new instantquiz_collection($objects);
    }

    /**
     * Renders html for feedbacks list on manage page
     *
     * @return renderable
     */
    protected function list_feedbacks() {
        $all = $this->get_entities('feedback');
        $objects = array();
        $cnt = 0;
        if (count($all)) {
            $table = new instantquiz_table();
            $table->head = array('#',
                get_string('feedback_preview', 'mod_instantquiz'),
                get_string('edit'),
                get_string('delete'));
            $table->data = array();
            foreach ($all as $f) {
                $table->data[] = array(++$cnt, $f->get_preview(),
                    html_writer::link($this->manage_link(array('cmd' => 'edit', 'entity' => 'feedback', 'entityid['.$f->id.']' => 1)), get_string('edit')),
                    html_writer::link($this->manage_link(array('cmd' => 'delete', 'entity' => 'feedback', 'entityid['.$f->id.']' => 1)), get_string('delete')));
            }
            $objects[] = $table;
            $objects[] = new single_button($this->manage_link(array('cmd' => 'edit', 'entity' => 'feedback')),
                    get_string('edit'));
        }
        $objects[] = new single_button($this->manage_link(array('cmd' => 'add', 'entity' => 'feedback')),
                get_string('addfeedback', 'mod_instantquiz'));
        return new instantquiz_collection($objects);
    }

    /**
     * Renders html for questions list on manage page
     *
     * @return renderable
     */
    protected function list_questions() {
        $all = $this->get_entities('question');
        $objects = array();
        $cnt = 0;
        if (count($all)) {
            $table = new instantquiz_table();
            $table->head = array('#',
                get_string('question_preview', 'mod_instantquiz'),
                get_string('edit'),
                get_string('delete'));
            $table->data = array();
            foreach ($all as $q) {
                $table->data[] = array(++$cnt, $q->get_preview(),
                    html_writer::link($this->manage_link(array('cmd' => 'edit', 'entity' => 'question', 'entityid['.$q->id.']' => 1)), get_string('edit')),
                    html_writer::link($this->manage_link(array('cmd' => 'delete', 'entity' => 'question', 'entityid['.$q->id.']' => 1)), get_string('delete')));
            }
            $objects[] = $table;
            $objects[] = new single_button($this->manage_link(array('cmd' => 'edit', 'entity' => 'question')),
                    get_string('edit'));
        }
        $objects[] = new single_button($this->manage_link(array('cmd' => 'add', 'entity' => 'question')),
                get_string('addquestion', 'mod_instantquiz'));
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
                return $attempt->review_attempt();
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
