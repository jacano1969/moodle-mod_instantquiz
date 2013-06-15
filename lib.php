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

/** example constant */
//define('instantquiz_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function instantquiz_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        //case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        default:                              return null;
    }
}

/**
 * Saves a new instance of the instantquiz into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $data An object from the form in mod_form.php
 * @param mod_instantquiz_mod_form $mform
 * @return int The id of the newly inserted instantquiz record
 */
function instantquiz_add_instance(stdClass $data, mod_instantquiz_mod_form $mform = null) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/instantquiz/locallib.php');
    $classname = $data->template. '_instantquiz';
    return $classname::create($data, $mform);
}

/**
 * Updates an instance of the instantquiz in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $data An object from the form in mod_form.php
 * @param mod_instantquiz_mod_form $mform
 * @return boolean Success/Fail
 */
function instantquiz_update_instance(stdClass $data, mod_instantquiz_mod_form $mform = null) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/instantquiz/locallib.php');
    $data->id = $data->instance;
    $instantquiz = instantquiz_get_instantquiz(null, $data->id);
    return $instantquiz->update($data, $mform);
}

/**
 * Removes an instance of the instantquiz from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function instantquiz_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/instantquiz/locallib.php');
    if ($instantquiz = instantquiz_get_instantquiz(null, $id)) {
        return $instantquiz->delete();
    }
    return false;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function instantquiz_user_outline($course, $user, $mod, $instantquiz) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = ''; // TODO
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $instantquiz the module instance record
 * @return void, is supposed to echp directly
 */
function instantquiz_user_complete($course, $user, $mod, $instantquiz) {
    // TODO
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in instantquiz activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function instantquiz_print_recent_activity($course, $viewfullnames, $timestart) {
    //TODO
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link instantquiz_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function instantquiz_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
    //TODO
}

/**
 * Prints single activity item prepared by {@see instantquiz_get_recent_mod_activity()}

 * @return void
 */
function instantquiz_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    // TODO
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function instantquiz_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function instantquiz_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function instantquiz_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for instantquiz file areas
 *
 * @package mod_instantquiz
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function instantquiz_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the instantquiz file areas
 *
 * @package mod_instantquiz
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the ninstantquiz's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function instantquiz_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding instantquiz nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the instantquiz module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function instantquiz_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the instantquiz settings
 *
 * This function is called when the context for the page is a instantquiz module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $instantquiznode {@link navigation_node}
 */
function instantquiz_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $instantquiznode=null) {
    global $PAGE;
    if (!($cm = $PAGE->cm)) {
        return;
    }

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $instantquiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('moodle/course:manageactivities', $cm->context)) {
        $link = new moodle_url('/mod/instantquiz/manage.php', array('cmid' => $cm->id));
        $node = navigation_node::create(get_string('manage', 'mod_instantquiz'), $link,
                navigation_node::TYPE_SETTING);
        $instantquiznode->add_node($node, $beforekey);
    }
}
