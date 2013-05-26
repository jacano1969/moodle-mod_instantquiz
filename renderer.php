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
     * Renderer for instantquiz_collection
     *
     * @param instantquiz_collection $collection
     * @return string
     */
    protected function render_instantquiz_collection($collection) {
        return $this->recursive_render($collection->object);
    }

    /**
     * Helper method for render_instantquiz_collection() that renders the object recursively
     *
     * @param mixed $obj
     * @return string
     */
    public function recursive_render($obj) {
        if ($obj instanceof renderable) {
            return $this->render($obj);
        } else if ($obj instanceof moodleform) {
            ob_start();
            $obj->display();
            $output = ob_get_contents();
            ob_clean();
            return $output;
        } else if ($obj instanceof html_table) {
            return html_writer::table($obj);
        } else if (is_array($obj) || is_object($obj)) {
            $output = '';
            foreach ($obj as $value) {
                $output .= $this->recursive_render($value);
            }
            return $output;
        } else {
            // TODO hack, do it nicer
            return $this->notification($obj);
        }
    }

    /**
     * Renders the instantquiz feedback to show to the user
     *
     * @param instantquiz_feedback $feedback
     * @return string
     */
    protected function render_instantquiz_feedback($feedback) {
        return $feedback->get_formatted_feedback();
    }

    /**
     * Renderer for instantquiz_tabs object.
     * It will be gone in 2.5 because there is already core renderable object for tabs
     *
     * @param instantquiz_tabs $tabs
     * @return string
     */
    protected function render_instantquiz_tabs($tabs) {
        return print_tabs($tabs->tabrows, $tabs->selected, $tabs->inactive, $tabs->activated, true);
    }
}
