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
 * class instantquiz_feedback_form
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing one feedback
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_feedback_form extends moodleform implements renderable {
    var $entities;
    var $editoroptions;
    var $instantquiz;

    /**
     * Form definition
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $this->entities = $this->_customdata;
        $mform->addElement('hidden', 'cmd', 'edit');
        $mform->addElement('hidden', 'entity', 'feedback');
        $firstentity = reset($this->entities);
        $this->instantquiz = $firstentity->instantquiz;
        $mform->addElement('hidden', 'cmid', $this->instantquiz->get_cm()->id);

        $context = $this->instantquiz->get_context();
        $this->editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes,
            'trusttext' => false, 'noclean' => true, 'context' => $context);

        $data = array(
            'feedback_editor' => array(),
            'addinfo' => array(),
            'sortorder' => array()
        );

        $cnt = 0;
        foreach ($this->entities as &$entity) {
            $mform->addElement('hidden', 'entityid['.$entity->id.']', 1);

            $this->add_entity_elements($entity, $cnt++);

            $tmpdata = file_prepare_standard_editor($entity, 'feedback', $this->editoroptions, $context, 'mod_instantquiz', 'feedback', $entity->id);
            $data['feedback_editor'][$entity->id] = $tmpdata->feedback_editor;
            $data['addinfo'][$entity->id] = $entity->addinfo;
            $data['sortorder'][$entity->id] = $entity->sortorder;
        }

        $this->add_action_buttons(true, get_string('savechanges'));
        $this->set_data($data);
    }

    /**
     * Adds form elements for one entity (question)
     *
     * @param instantquiz_entity $entity
     * @param int $cnt number of this entity in the form (starting from 0)
     */
    protected function add_entity_elements($entity, $cnt) {
        $mform = $this->_form;
        if (count($this->entities) > 1) {
            $mform->addElement('header', 'header-'.$entity->id, get_string('deffeedback', 'mod_instantquiz', $cnt + 1));
        }
        $suffix = '['.$entity->id.']';
        $mform->addElement('hidden', 'sortorder'. $suffix);
        $mform->addElement('editor','feedback_editor'. $suffix, get_string('feedback_preview', 'mod_instantquiz'), null, $this->editoroptions);
        static $criterialegend = false;
        if ($criterialegend === false) {
            $ccount = count($this->instantquiz->get_entities('criterion'));
            if ($ccount) {
                $criterialegend = get_string('feedbackformulalegend', 'mod_instantquiz');
            }
        }
        if (!empty($criterialegend)) {
            $mform->addElement('text', 'addinfo'.$suffix.'[formula]', 'Formula', array('size' => 60));
            $mform->addElement('static', '', '', $criterialegend);
        } else {
            $mform->addElement('hidden', 'addinfo'.$suffix.'[formula]');
        }
    }

    /**
     * Form validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Feedback text can not be empty
        foreach ($data['feedback_editor'] as $id => $feedback) {
            if (!strlen(trim(strip_tags($feedback['text'], '<img>')))) {
                $errors['feedback_editor['.$id.']'] = get_string('required');
            }
        }
        return $errors;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        global $CFG;
        $data = parent::get_data();
        if ($data !== null) {
            $context = $this->instantquiz->get_context();
            foreach ($data->feedback_editor as $id => $feedback_editor) {
                // file_postupdate_standard_editor() can not work with arrays
                $tmpdata = (object)array('feedback_editor' => $feedback_editor);
                $tmpdata = file_postupdate_standard_editor($tmpdata, 'feedback', $this->editoroptions, $context, 'mod_instantquiz', 'feedback', $id);
                foreach ($tmpdata as $key => $value) {
                    if (!isset($data->$key)) {
                        $data->$key = array();
                    }
                    $el = &$data->$key;
                    $el[$id] = $value;
                }
            }
        }
        return $data;
    }
}