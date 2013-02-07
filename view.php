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
 * Prints a particular instance of instantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$iquizid  = optional_param('i', 0, PARAM_INT);  // instantquiz instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('instantquiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $instantquiz  = $DB->get_record('instantquiz', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($iquizid) {
    $instantquiz  = $DB->get_record('instantquiz', array('id' => $iquizid), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $instantquiz->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('instantquiz', $instantquiz->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/instantquiz:view', $context);

add_to_log($course->id, 'instantquiz', 'view', "view.php?id={$cm->id}", $instantquiz->name, $cm->id);

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Print the page header

$PAGE->set_url('/mod/instantquiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($instantquiz->name));
$PAGE->set_heading(format_string($course->fullname));

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('instantquiz-'.$somevar);

// Output starts here
echo $OUTPUT->header();

if ($instantquiz->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('instantquiz', $instantquiz, $cm->id), 'generalbox mod_introbox', 'instantquizintro');
}

// Replace the following lines with you own code
echo $OUTPUT->heading('Yay! It works!');

// Finish the page
echo $OUTPUT->footer();
