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
 * List of all instances of mod_instantquiz in the course
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/instantquiz/locallib.php');

$id = required_param('id', PARAM_INT);   // course

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

add_to_log($course->id, 'instantquiz', 'view all', 'index.php?id='.$course->id, '');

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/instantquiz/index.php', array('id' => $id));
$strplural = get_string('modulenameplural', 'mod_instantquiz');
$PAGE->navbar->add($strplural);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

$modinfo = get_fast_modinfo($course);
if (empty($modinfo->instances['instantquiz'])) {
    notice(get_string('noinstantquizzes', 'instantquiz'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $strsectionname  = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array($strsectionname, get_string('name'));
    $table->align = array('center', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left');
}

$cms = $modinfo->instances['instantquiz'];
$currentsection = '';
foreach ($cms as $cm) {
    $class = $cm->visible ? null : array('class' => 'dimmed'); // hidden modules are dimmed
    $link = html_writer::link(
        new moodle_url('/mod/instantquiz/view.php', array('id' => $cm->id)),
        format_string($cm->name, true),
        $class);

    $tabledata = array();
    if ($usesections) {
        if ($cm->sectionnum !== $currentsection) {
            $tabledata[] = get_section_name($course, $cm->sectionnum);
            $currentsection = $cm->sectionnum;
        } else {
            $tabledata[] = '';
        }
    }

    $tabledata[] = $link;
    $table->data[] = $tabledata;
}

echo $OUTPUT->heading($strplural, 2);
echo html_writer::table($table);
echo $OUTPUT->footer();
