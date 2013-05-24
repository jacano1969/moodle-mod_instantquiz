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

/**
 * Default renderer for mod_isntantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_instantquiz_renderer extends plugin_renderer_base {

    /**
     * Renders HTML for manage page contents
     *
     * @param instantquiz $instantquiz
     * @param string $cmd 'cmd' argument in /mod/instantquiz/manage.php page
     * @param string $entitytype 'entity' argument in /mod/instantquiz/manage.php page
     * @param string $entityids 'entityid' argument in /mod/instantquiz/manage.php page (note that keys
     *     of this array are actual entity ids, the values are not important)
     * @return string
     */
    public function manage_instantquiz($instantquiz, $cmd = null, $entitytype = null, $entityids = array()) {
        $output = $this->manage_menu($instantquiz);
        if ($cmd === 'list') {
            if ($entitytype === 'question') {
                $output .= $this->list_questions($instantquiz);
            } else if ($entitytype === 'evaluation') {
                $output .= $this->list_evaluations($instantquiz);
            } else if ($entitytype === 'feedback') {
                $output .= $this->list_feedbacks($instantquiz);
            }
        }
        return $output;
    }

    /**
     * Renders HTML for manage page menu
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return string
     */
    public function manage_menu($instantquiz) {
        $cmd = optional_param('cmd', null, PARAM_ALPHA);
        $entity = optional_param('entity', null, PARAM_ALPHA);
        $tabrows = array();
        foreach (array('evaluation', 'question', 'feedback') as $key) {
            $linkedwhenselected = ($cmd !== 'list');
            $tabrows[] = new tabobject($key, $instantquiz->manage_link(array('cmd' => 'list', 'entity' => $key)), $key  /* TODO string */,
                    '', $linkedwhenselected);
        }
        return print_tabs(array($tabrows), $entity, NULL, NULL, true);
    }

    /**
     * Renders html for evaluations list on manage page
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return string
     */
    public function list_evaluations($instantquiz) {
        $all = $instantquiz->get_entities('evaluation');
        $output = '';
        $cnt = 0;
        if (count($all)) {
            $table = new html_table();
            $table->head = array('#',
                get_string('evaluation_name', 'mod_instantquiz'),
                get_string('evaluation_addinfo', 'mod_instantquiz'),
                get_string('edit'),
                get_string('delete'));
            $table->data = array();
            foreach ($all as $e) {
                $table->data[] = array(++$cnt, $e->get_preview(), $e->get_addinfo_preview(),
                    html_writer::link($instantquiz->manage_link(array('cmd' => 'edit', 'entity' => 'evaluation', 'entityid['.$e->id.']' => 1)), get_string('edit')),
                    html_writer::link($instantquiz->manage_link(array('cmd' => 'delete', 'entity' => 'evaluation', 'entityid['.$e->id.']' => 1)), get_string('delete')));
            }
            $output .= html_writer::table($table);
            $output .= $this->single_button($instantquiz->manage_link(array('cmd' => 'edit', 'entity' => 'evaluation')),
                    get_string('edit'));
        }
        $output .= $this->single_button($instantquiz->manage_link(array('cmd' => 'add', 'entity' => 'evaluation')),
                get_string('addevaluation', 'mod_instantquiz'));
        return $output;
    }

    /**
     * Renders html for feedbacks list on manage page
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return string
     */
    public function list_feedbacks($instantquiz) {
        $all = $instantquiz->get_entities('feedback');
        $output = '';
        $cnt = 0;
        if (count($all)) {
            $table = new html_table();
            $table->head = array('#',
                get_string('feedback_preview', 'mod_instantquiz'),
                get_string('feedback_addinfo', 'mod_instantquiz'),
                get_string('edit'),
                get_string('delete'));
            $table->data = array();
            foreach ($all as $f) {
                $table->data[] = array(++$cnt, $f->get_preview(), $f->get_addinfo_preview(),
                    html_writer::link($instantquiz->manage_link(array('cmd' => 'edit', 'entity' => 'feedback', 'entityid['.$f->id.']' => 1)), get_string('edit')),
                    html_writer::link($instantquiz->manage_link(array('cmd' => 'delete', 'entity' => 'feedback', 'entityid['.$f->id.']' => 1)), get_string('delete')));
            }
            $output .= html_writer::table($table);
            $output .= $this->single_button($instantquiz->manage_link(array('cmd' => 'edit', 'entity' => 'feedback')),
                    get_string('edit'));
        }
        $output .= $this->single_button($instantquiz->manage_link(array('cmd' => 'add', 'entity' => 'feedback')),
                get_string('addfeedback', 'mod_instantquiz'));
        return $output;
    }

    /**
     * Renders html for questions list on manage page
     *
     * @param instantquiz $instantquiz
     * @return string
     */
    public function list_questions($instantquiz) {
        $all = $instantquiz->get_entities('question');
        $output = '';
        $cnt = 0;
        if (count($all)) {
            $table = new html_table();
            $table->head = array('#',
                get_string('question_preview', 'mod_instantquiz'),
                get_string('question_addinfo', 'mod_instantquiz'),
                get_string('edit'),
                get_string('delete'));
            $table->data = array();
            foreach ($all as $q) {
                $table->data[] = array(++$cnt, $q->get_preview(), $q->get_addinfo_preview(),
                    html_writer::link($instantquiz->manage_link(array('cmd' => 'edit', 'entity' => 'question', 'entityid['.$q->id.']' => 1)), get_string('edit')),
                    html_writer::link($instantquiz->manage_link(array('cmd' => 'delete', 'entity' => 'question', 'entityid['.$q->id.']' => 1)), get_string('delete')));
            }
            $output .= html_writer::table($table);
            $output .= $this->single_button($instantquiz->manage_link(array('cmd' => 'edit', 'entity' => 'question')),
                    get_string('edit'));
        }
        $output .= $this->single_button($instantquiz->manage_link(array('cmd' => 'add', 'entity' => 'question')),
                get_string('addquestion', 'mod_instantquiz'));
        return $output;
    }
}