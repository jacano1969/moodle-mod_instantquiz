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
 * Students performs an attempt to answer the instantquiz questions
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('cmid', PARAM_INT); // course_module ID

$instantquiz = instantquiz_get_instantquiz($id);
$cm = $instantquiz->get_cm();

$PAGE->set_url($instantquiz->attempt_link());
require_login($cm->course, true, $cm);
$context = $instantquiz->get_context();
require_capability('mod/instantquiz:view', $context);

$PAGE->set_pagelayout('incourse'); // or admin?
$PAGE->set_title(format_string($instantquiz->name)); // TODO 2.5 replace with $cm->get_formatted_name()
$PAGE->set_heading(format_string($PAGE->course->fullname, true, array('context' => $context)));
if ($instantquiz->template) {
    $PAGE->add_body_class(preg_replace('/_/', '-', $instantquiz->template));
}
$renderer = $instantquiz->get_renderer();
$output = $instantquiz->attempt_page();

echo $renderer->header();

echo $renderer->render($output);

echo $renderer->footer();
