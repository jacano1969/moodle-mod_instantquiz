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
 * class instantquiz_question_form
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing one question
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_attempt_form extends moodleform implements renderable {
    var $attempt;
    var $instantquiz;
    var $editoroptions;

    /**
     * Form definition
     */
    protected function definition() {
        //global $CFG;

        $mform = $this->_form;
        $this->attempt = $this->_customdata;
        $mform->addElement('hidden', 'cmd', 'continueattempt');
        $mform->setType('cmd', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'entity', 'attempt');
        $mform->setType('entity', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'attemptid', $this->attempt->id);
        $mform->setType('attemptid', PARAM_INT);

        $this->instantquiz = $this->attempt->instantquiz;
        $mform->addElement('hidden', 'cmid', $this->instantquiz->get_cm()->id);
        $mform->setType('cmid', PARAM_INT);

        //$context = $this->instantquiz->get_context();
        //$this->editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes,
        //    'trusttext' => false, 'noclean' => true, 'context' => $context);

        $renderer = $this->instantquiz->get_renderer();
        foreach ($this->instantquiz->get_entities('question') as $question) {
            // Placing the question text in a group allows to assign id to the form element.
            $elements = array();
            $elements[] = $mform->createElement('static', '', null, $renderer->render($question));
            $mform->addElement('group', 'question['.$question->id.']', null, $elements);

            if (!empty($question->options)) {
                if (isset($question->addinfo['minoptions']) && isset($question->addinfo['maxoptions']) &&
                        $question->addinfo['maxoptions'] > $question->addinfo['minoptions']) {
                    // Display as checkboxes
                    $elementobjs = array();
                    foreach ($question->options as $option) {
                        $elementobjs[] = $mform->createElement('checkbox', 'options]['.$option['idx'], '',
                                $option['value'], (int)$option['idx']);
                    }
                    $mform->addElement('group', 'answers['.$question->id.']', '', $elementobjs, '<br/>');
                } else {
                    // Display as radio elements
                    $elementobjs = array();
                    foreach ($question->options as $option) {
                        $elementobjs[] = $mform->createElement('radio', 'option', '', $option['value'], (int)$option['idx']);
                    }
                    $mform->addElement('group', 'answers['.$question->id.']', '', $elementobjs);
                }
            }

            if (!empty($question->addinfo['comment'])) {
                $mform->addElement('textarea', 'answers['.$question->id.'][comment]', '');
            }
        }

        $this->add_action_buttons(true, get_string('savechanges')); // TODO "proceed"
        $data = array('answers' => $this->attempt->answers);
        $this->set_data($data);
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
        foreach ($this->instantquiz->get_entities('question') as $question) {
            $question->is_answered($data, null, $errors);
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
        $data = parent::get_data();
/*        if ($data !== null) {
            $context = $this->instantquiz->get_context();
            foreach ($data->question_editor as $id => $question_editor) {
                // file_postupdate_standard_editor() can not work with arrays
                $tmpdata = (object)array('question_editor' => $question_editor);
                $tmpdata = file_postupdate_standard_editor($tmpdata, 'question', $this->editoroptions, $context, 'mod_instantquiz', 'question', $id);
                foreach ($tmpdata as $key => $value) {
                    if (!isset($data->$key)) {
                        $data->$key = array();
                    }
                    $el = &$data->$key;
                    $el[$id] = $value;
                }
            }
            foreach ($data->options as $id => $options) {
                // Remove options with empty value
                foreach ($options as $key => $option) {
                    if (!strlen(trim($option['value']))) {
                        unset($data->options[$id][$key]);
                    }
                }
                $data->options[$id] = array_values($data->options[$id]);
            }
        }*/
        return $data;
    }
}