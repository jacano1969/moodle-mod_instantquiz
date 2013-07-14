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
class instantquiz_question_form extends moodleform implements renderable {
    var $entities;
    var $editoroptions;
    var $instantquiz;
    var $entitiescount;

    /**
     * Form definition
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $this->entities = $this->_customdata;
        $mform->addElement('hidden', 'cmd', 'edit');
        $mform->setType('cmd', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'entity', 'question');
        $mform->setType('entity', PARAM_ALPHANUMEXT);
        $firstentity = reset($this->entities);
        $this->instantquiz = $firstentity->instantquiz;
        $mform->addElement('hidden', 'cmid', $this->instantquiz->get_cm()->id);
        $mform->setType('cmid', PARAM_INT);

        $context = $this->instantquiz->get_context();
        $this->editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes,
            'trusttext' => false, 'noclean' => true, 'context' => $context);

        $data = array(
            'question_editor' => array(),
            'options' => array(),
            'sortorder' => array(),
            'addinfo' => array()
        );

        $cnt = 0;
        foreach ($this->entities as &$entity) {
            $mform->addElement('hidden', 'entityid['.$entity->id.']', 1);
            $mform->setType('entityid['.$entity->id.']', PARAM_INT);

            $this->add_entity_elements($entity, $cnt++);

            $tmpdata = file_prepare_standard_editor($entity, 'question', $this->editoroptions, $context, 'mod_instantquiz', 'question', $entity->id);
            $data['question_editor'][$entity->id] = $tmpdata->question_editor;
            $data['options'][$entity->id] = $entity->options;
            $data['sortorder'][$entity->id] = $entity->sortorder;
            $data['addinfo'][$entity->id] = $entity->addinfo + array('minoptions' => 1, 'maxoptions' => 1);
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
            $mform->addElement('header', 'header-'.$entity->id, get_string('defquestion', 'mod_instantquiz', $cnt + 1));
        }
        $suffix = '['.$entity->id.']';
        $mform->addElement('hidden', 'sortorder'. $suffix);
        $mform->setType('sortorder'. $suffix, PARAM_INT);
        $mform->addElement('editor','question_editor'. $suffix, get_string('question_preview', 'mod_instantquiz'), null, $this->editoroptions);

        $elementobjs = array();
        $elementobjs[] = $mform->createElement('text', 'value', '');
        $elementobjs[] = $mform->createElement('hidden', 'idx');
        $criteria = $this->instantquiz->get_entities('criterion');
        foreach ($criteria as $criterion) {
            $elementobjs[] = $mform->createElement('static', '','', $criterion->criterion);
            $elementobjs[] = $mform->createElement('text', 'points]['.$criterion->id, '', array('size' => 3));
        }
        // $elementobjs[] = $mform->createElement('submit', 'delete', get_string('delete'));
        // TODO delete does not work yet

        $group = $mform->createElement('group', 'options'. $suffix,
                    get_string('questionoption', 'mod_instantquiz'), $elementobjs);
        $repeats = $this->repeat_elements(array($group), count($entity->options), array(),
                'repeathiddenname-'. $entity->id, 'addoption-'.$entity->id, 1,
                get_string('questionaddoption', 'mod_instantquiz'), true);
        for ($i = 0; $i < $repeats; $i++) {
            $mform->setType('options'. $suffix. '['.$i.'][idx]', PARAM_INT);
            $mform->setType('options'. $suffix. '['.$i.'][value]', PARAM_TEXT);
            foreach ($criteria as $criterion) {
                $mform->setType('options'. $suffix. '['.$i.'][points]['.$criterion->id.']', PARAM_FLOAT);
            }
        }

        // Add options limits (minoptions, maxoptions).
        if ($repeats > 1) {
            $elementobjs = array();
            $str = get_string('optionslimit', 'mod_instantquiz').'{$a->minoptions}'.'{$a->maxoptions}';
            $minoptionsset = $maxoptionsset = false;
            $chunks = preg_split('/(\{\$a->m\w\woptions\})/', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            foreach ($chunks as $chunk) {
                if ($chunk === '{$a->minoptions}') {
                    if (!$minoptionsset) {
                        $elementobjs[] = $mform->createElement('select', 'addinfo'. $suffix. '[minoptions]', '', range(0, $repeats - 1));
                        $minoptionsset = true;
                    }
                } else if ($chunk === '{$a->maxoptions}') {
                    if (!$maxoptionsset) {
                        $elementobjs[] = $mform->createElement('select', 'addinfo'. $suffix. '[maxoptions]', '',
                            array_combine(range(1, $repeats), range(1, $repeats)));
                        $maxoptionsset = true;
                    }
                } else {
                    $elementobjs[] = $mform->createElement('static', '','', $chunk);
                }
            }
            $mform->addElement('group', '', '', $elementobjs, '', false);
            $mform->setType('addinfo'. $suffix. '[minoptions]', PARAM_INT);
            $mform->setType('addinfo'. $suffix. '[maxoptions]', PARAM_INT);
        }

        $commentoptions = array();
        $commentoptions[0] = get_string('commentnone', 'mod_instantquiz');
        $commentoptions[instantquiz_question::COMMENT_ALLOWED | instantquiz_question::COMMENT_IN_SUMMARY |
                instantquiz_question::COMMENT_NAME_IN_SUMMARY] =
                get_string('commentpublic', 'mod_instantquiz');
        $commentoptions[instantquiz_question::COMMENT_ALLOWED | instantquiz_question::COMMENT_IN_SUMMARY] =
                get_string('commentanonymous', 'mod_instantquiz');
        $commentoptions[instantquiz_question::COMMENT_ALLOWED] =
                get_string('commentprivate', 'mod_instantquiz');
        $elementobjs = array();
        $elementobjs[] = $mform->createElement('select', 'addinfo'. $suffix. '[comment]', '', $commentoptions);
        $elementobjs[] = $mform->createElement('select', 'addinfo'. $suffix. '[commentrequired]', '',
                array(0 => get_string('commentoptional', 'mod_instantquiz'),
                    1 => get_string('commentrequired', 'mod_instantquiz')));
        $mform->addElement('group', '', get_string('comment', 'mod_instantquiz'), $elementobjs, ' ', false);
        $mform->disabledIf('addinfo'. $suffix. '[commentrequired]', 'addinfo'. $suffix. '[comment]', 'eq', 0);
        $mform->setType('addinfo'. $suffix. '[comment]', PARAM_INT);
        $mform->setType('addinfo'. $suffix. '[commentrequired]', PARAM_INT);
    }

    /*public function no_submit_button_pressed() {
        if (parent::no_submit_button_pressed()) return true;
        if (!empty($_REQUEST['options']) && is_array($_REQUEST['options'])) {
            foreach ($_REQUEST['options'] as $options) {
                foreach ($options as $option) {
                    if (!empty($option['delete'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    }*/

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
        // Question text can not be empty
        foreach ($data['question_editor'] as $id => $question) {
            if (!strlen(trim(strip_tags($question['text'], '<img>')))) {
                $errors['question_editor['.$id.']'] = get_string('required');
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
        $data = parent::get_data();
        if ($data !== null) {
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
            if (!empty($data->options)) {
                foreach ($data->options as $id => $options) {
                    // Remove options with empty value
                    foreach ($options as $key => $option) {
                        if (!strlen(trim($option['value']))) {
                            unset($data->options[$id][$key]);
                        }
                    }
                    $data->options[$id] = array_values($data->options[$id]);
                }
            }
        }
        return $data;
    }
}