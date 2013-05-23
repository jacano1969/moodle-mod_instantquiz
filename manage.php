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
 * Allows teacher to edit evaluations criteria for given instantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // course_module ID
$cmd = optional_param('cmd', null, PARAM_ALPHA);
$entity = optional_param('entity', null, PARAM_ALPHA);
$entityid = optional_param('entityid', null, PARAM_INT);

$cm = get_coursemodule_from_id('instantquiz', $id, 0, false, MUST_EXIST);
//print_r($cm);
$instantquiz = new instantquiz($cm);
$PAGE->set_url($instantquiz->manage_link());
require_login($cm->course, true, $cm);
$context = $instantquiz->get_context();
require_capability('moodle/course:manageactivities', $context);

if ($cmd === 'add' && !empty($entity) && $instantquiz->add_entity($entity)) {
    redirect($instantquiz->manage_link(array('cmd' => 'list', 'entity' => $entity)));
} else if ($cmd === 'edit' && !empty($entity)) {
    //redirect($instantquiz->manage_link(array('cmd' => 'list', 'entity' => $entity)));
}

$PAGE->set_pagelayout('incourse'); // or admin?
$PAGE->set_title(format_string($instantquiz->name)); // TODO 2.5 replace with $cm->get_formatted_name()
$PAGE->set_heading(format_string($COURSE->fullname, true, array('context' => $context)));
$renderer = $PAGE->get_renderer('mod_instantquiz');

echo $OUTPUT->header();

echo $renderer->manage_menu($instantquiz, $entity);

if ($cmd === 'list' && !empty($entity)) {
    echo $renderer->list_entities($instantquiz, $entity);
}

echo $OUTPUT->footer();
