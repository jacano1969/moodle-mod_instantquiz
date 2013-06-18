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

define('INSTANTQUIZ_MAX_ATTEMPT_OPTION', 10);

/**
 * Class autoloading
 *
 * @param string $classname
 */
function instantquiz_classloader($classname) {
    global $CFG;
    if ($classname === 'moodleform') {
        require_once($CFG->libdir. '/formslib.php');
    } else if (preg_match('/^instantquiz_(.+)$/', $classname, $matches)) {
        require_once($CFG->dirroot.'/mod/instantquiz/classes/'. $matches[1]. '.php');
    } else if (preg_match('/^instantquiztmpl_([^_]+)_(.*)$/', $classname, $matches)) {
        $filename = $CFG->dirroot.'/mod/instantquiz/template/'. $matches[1]. '/classes/'. $matches[2]. '.php';
        if (file_exists($filename)) {
            require_once($filename);
        } else {
            class_alias('instantquiz_'. $matches[2], $classname);
        }
    }
}

spl_autoload_register('instantquiz_classloader');

/**
 * Given either course module or instantquizid returns an instance of proper instantquiz object
 *
 * @param int|stdClass|cm_info $cm
 * @param int $instantquizid
 * @return instantquiz_instantquiz
 */
function instantquiz_get_instantquiz($cm, $instantquizid = null) {
    global $DB;
    if (!$instantquizid) {
        // we need to get instantquiz id from course module
        if (!is_object($cm)) {
            $cm = get_coursemodule_from_id('instantquiz', $cm, 0, false, MUST_EXIST);
        }
        $instantquizid = $cm->instance;
    }
    $record = $DB->get_record('instantquiz', array('id' => $instantquizid), '*', MUST_EXIST);
    if (!$cm) {
        $cm = get_coursemodule_from_instance('instantquiz', $record->id, $record->course, false, MUST_EXIST);
    }

    $classname = $record->template. '_instantquiz';
    $instantquiz = new $classname($cm, $record);
    return $instantquiz;
}

/**
 * Returns the list of template plugins and their names (for mod_form.php)
 *
 * @return array
 */
function instantquiz_get_templates() {
    $subplugins = get_plugin_list('instantquiztmpl');
    $rv = array();
    foreach ($subplugins as $pluginname => $dir) {
        $fullname = 'instantquiztmpl_'. $pluginname;
        $rv[$fullname] = get_string('pluginname', $fullname);
    }
    return $rv;
}

class instantquiz_collection implements renderable {
    var $object;
    public function __construct($object) {
        $this->object = $object;
    }
}

class instantquiz_tabs implements renderable {
    // SIMILAR OBJECT ALREADY EXISTS IN 2.5
    var $tabrows;
    var $selected;
    var $inactive;
    var $activated;

    /**
    * Constructor (copy of print_tabs() arguments)
    *
    * @global object
    * @param array $tabrows An array of rows where each row is an array of tab objects
    * @param string $selected  The id of the selected tab (whatever row it's on)
    * @param array  $inactive  An array of ids of inactive tabs that are not selectable.
    * @param array  $activated An array of ids of other tabs that are currently activated
    * @param bool $return If true output is returned rather then echo'd
    **/
    public function __construct($tabrows, $selected=NULL, $inactive=NULL, $activated=NULL) {
        $this->tabrows = $tabrows;
        $this->selected = $selected;
        $this->inactive = $inactive;
        $this->activated = $activated;
    }
}

class instantquiz_table extends html_table implements renderable {
}