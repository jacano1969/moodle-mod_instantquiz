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
 * The main instantquiz configuration form
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/instantquiz/locallib.php');

/**
 * Module instance settings form
 */
class mod_instantquiz_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $PAGE->requires->js_init_call('M.mod_instantquiz.init_templatechooser',
                array(array('formid' => $mform->getAttribute('id'))));

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('instantquizname', 'instantquiz'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'instantquizname', 'instantquiz');

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        //-------------------------------------------------------------------------------

        $mform->addElement('header', 'instantquiz', get_string('modulename', 'mod_instantquiz'));
        $mform->addElement('select', 'template', get_string('subplugintype_instantquiztmpl', 'mod_instantquiz'), instantquiz_get_templates());
        // button to update format-specific options on format change (will be hidden by JavaScript)
        $mform->registerNoSubmitButton('updatetemplate');
        $mform->addElement('submit', 'updatetemplate', get_string('update'), array('class' => 'hiddenifjs'));
        $mform->addElement('hidden', 'addtemplateoptionshere');

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    /**
     * Adds/modifies form elements after data was set
     */
    public function definition_after_data() {
        global $CFG;
        parent::definition_after_data();
        $mform = $this->_form;
        $templatevalue = $mform->getElementValue('template');
        if (is_array($templatevalue) && !empty($templatevalue)) {
            require_once($CFG->dirroot.'/mod/instantquiz/classes/instantquiz.class.php');
            $classname = instantquiz_instantquiz::get_instantquiz_class($templatevalue[0]);

            $elements = $classname::edit_form_elements($mform);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($mform->removeElement($elements[$i]->getName(), false),
                        'addtemplateoptionshere');
            }
        }
    }
}
