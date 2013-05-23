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
 * Class instantquiz_templatebase
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Parent class for instantquiz template subplugins
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_tmpl {
    /** @var instantquiz */
    protected $instantquiz;

    public function __construct(instantquiz $instantquiz) {
        $this->instantquiz = $instantquiz;
    }

    public static function get_template_name() {
        $classname = get_called_class();
        if ($classname === 'instantquiz_tmpl') {
            // this is this class, return the nice string
            return 'No template';
        } else {
            // the class did not override this method (shame on you!), return class name
            debugging('Class '.$classname.' does not override get_template_name()');
            return $classname;
        }
    }

    public static function edit_form($form) {
        // $mform = $form->_form;
    }
/*
    public function edit_form_validation() {

    }

    public function on_create_module() {

    }

    public function on_edit_module() {

    }

    public function on_add_evaluation() {

    }

    public function on_add_feedback() {

    }

    public function on_add_question() {

    }

    public function on_edit_question() {

    }

    public function on_edit_feedback() {

    }

    public function on_edit_evaluation() {

    }

    public function on_delete_question() {

    }

    public function on_delete_evaluation() {

    }

    public function on_delete_feedback() {

    }
 */
}