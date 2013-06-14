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
 * class instantquiz_criterion_form
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir. '/formslib.php');

/**
 * Form for editing one question
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_criterion_form extends moodleform implements renderable {
    var $entities;
    var $instantquiz;

    /**
     * Form definition
     */
    protected function definition() {
        $mform = $this->_form;
        $this->entities = $this->_customdata;
        $mform->addElement('hidden', 'cmd', 'edit');
        $mform->addElement('hidden', 'entity', 'criterion');
        $firstentity = reset($this->entities);
        $this->instantquiz = $firstentity->instantquiz;
        $mform->addElement('hidden', 'cmid', $this->instantquiz->get_cm()->id);

        $data = array(
            'criterion' => array(),
        );

        foreach ($this->entities as &$entity) {
            $suffix = '['.$entity->id.']';
            $mform->addElement('hidden', 'entityid'. $suffix, 1);

            $mform->addElement('text','criterion'. $suffix, get_string('criterion_name', 'mod_instantquiz'));
            $mform->addRule('criterion'. $suffix, get_string('required'), 'required', null, 'client');
            $data['criterion'][$entity->id] = $entity->criterion;
        }

        $this->add_action_buttons(true, get_string('savechanges'));
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
        return parent::validation($data, $files);
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
        }
        return $data;
    }
}