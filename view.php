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
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$iquizid  = optional_param('i', 0, PARAM_INT);  // instantquiz instance ID - it should be named as the first character of the module

if (!$id && !$iquizid) {
    error('You must specify a course_module ID or an instance ID');
}
$instantquiz = instantquiz_get_instantquiz($id, $iquizid);

$cm = $instantquiz->get_cm();
require_login($cm->course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/instantquiz:view', $context);

$course = $PAGE->course;
add_to_log($course->id, 'instantquiz', 'view', "view.php?id={$cm->id}", $instantquiz->name, $cm->id);

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/instantquiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($instantquiz->name));
$PAGE->set_heading(format_string($course->fullname));

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
if ($instantquiz->template) {
    $PAGE->add_body_class(preg_replace('/_/', '-', $instantquiz->template));
}

// Output starts here
$renderer = $instantquiz->get_renderer();
echo $renderer->header();

if ($instantquiz->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $renderer->box(format_module_intro('instantquiz', $instantquiz, $cm->id), 'generalbox mod_introbox', 'instantquizintro');
}

echo $renderer->render($instantquiz->view_page());

// Finish the page
echo $renderer->footer();
