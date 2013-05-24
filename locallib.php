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
 * Library of interface functions and constants for module instantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function instantquiz_get_instantquiz($cm, $instantquizid = null) {
    global $DB, $CFG;
    if (!$instantquizid) {
        // we need to get instantquiz id from course module
        if (!is_object($cm)) {
            $cm = get_coursemodule_from_id('instantquiz', $cm, 0, false, MUST_EXIST);
        }
        $instantquizid = $cm->instance;
    }
    if (!$cm) {
        $record = $DB->get_record('instantquiz', array('id' => $instantquizid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('instantquiz', $record->id, $record->course, false, MUST_EXIST);
    }

    require_once($CFG->dirroot.'/mod/instantquiz/classes/instantquiz.class.php');
    $instantquiz = new instantquiz_instantquiz($cm);
    return $instantquiz;
}

function instantquiz_get_all($course) {
    $modinfo = get_fast_modinfo($course);
}