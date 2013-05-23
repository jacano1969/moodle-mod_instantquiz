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
 * mod_instantquiz renderer
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/instantquiz/locallib.php');

class mod_instantquiz_renderer extends plugin_renderer_base {
    
    /**
     * Renders HTML for manage page menu
     *
     * @param instantquiz $instantquiz
     * @return string
     */
    public function manage_menu(instantquiz $instantquiz, $selected) {
        $tabrows = array();
        foreach (array('evaluations', 'questions', 'feedbacks') as $key) {
            $tabrows[] = new tabobject($key, $instantquiz->manage_link(array('list' => $key)), $key  /* TODO string */);
        }
        return print_tabs(array($tabrows), $selected, NULL, NULL, true);
    }

    /**
     * Renders html for evaluations list on manage page
     *
     * @param instantquiz $instantquiz
     * @return string
     */
    public function list_evaluations(instantquiz $instantquiz) {
        $all = $instantquiz->get_evaluations();
        $output = '';
        $cnt = 0;
        if (count($all)) {
            $table = new html_table();
            $table->head = array('#',
                get_string('evaluation_name', 'mod_instantquiz'),
                get_string('evaluation_addinfo', 'mod_instantquiz'));
            $table->data = array();
            foreach ($all as $ev) {
                $table->data[] = array(++$cnt, $ev->name, $ev->addinfo);
            }
            $output .= html_writer::table($table);
        }
        $link = html_writer::link($instantquiz->manage_link(array('add' => 'evaluation')),
                get_string('addevaluation', 'mod_instantquiz'));
        $output .= html_writer::tag('div', $link);
        return $output;
    }

    /**
     * Renders html for feedbacks list on manage page
     * 
     * @param instantquiz $instantquiz
     * @return string
     */
    public function list_feedbacks(instantquiz $instantquiz) {
        $all = $instantquiz->get_feedbacks();
        $output = '';
        $cnt = 0;
        if (count($all)) {
            $table = new html_table();
            $table->head = array('#',
                get_string('feedback_preview', 'mod_instantquiz'),
                get_string('feedback_addinfo', 'mod_instantquiz'));
            $table->data = array();
            foreach ($all as $f) {
                $table->data[] = array(++$cnt, $f->get_preview(), $f->addinfo);
            }
            $output .= html_writer::table($table);
        }
        $link = html_writer::link($instantquiz->manage_link(array('add' => 'feedback')),
                get_string('addfeedback', 'mod_instantquiz'));
        $output .= html_writer::tag('div', $link);
        return $output;
    }
}