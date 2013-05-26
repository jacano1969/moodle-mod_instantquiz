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
        $output = '';
        if ($collection->object instanceof renderable) {
            echo get_class($collection->object)."<br>";
            $output .= $this->render($collection->object);
        } else if (is_array($collection->object)) {
            foreach ($collection->object as $value) {
                if ($value instanceof renderable) {
                    $output .= $this->render($value);
                }
            }
        }
        return $output;
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

    /**
     * Note, this is not a proper renderer because moodleform does not implement renderable
     *
     * @param moodleform $form
     * @return string
     */
    public function render_moodleform($form) {
        ob_start();
        $form->display();
        $output = ob_get_contents();
        ob_clean();
        return $output;
    }

    /**
     * Renderer for instantquiz_criterion_form
     *
     * @param instantquiz_criterion_form $form
     * @return string
     */
    protected function render_instantquiz_criterion_form($form) {
        return $this->render_moodleform($form);
    }

    /**
     * Renderer for instantquiz_attempt_form
     *
     * @param instantquiz_attempt_form $form
     * @return string
     */
    protected function render_instantquiz_attempt_form($form) {
        return $this->render_moodleform($form);
    }

    /**
     * Renderer for instantquiz_feedback_form
     *
     * @param instantquiz_feedback_form $form
     * @return string
     */
    protected function render_instantquiz_feedback_form($form) {
        return $this->render_moodleform($form);
    }

    /**
     * Renderer for instantquiz_question_form
     *
     * @param instantquiz_question_form $form
     * @return string
     */
    protected function render_instantquiz_question_form($form) {
        return $this->render_moodleform($form);
    }

    /**
     * Renderer for instantquiz_table (makes an html_table renderable)
     *
     * @param instantquiz_table $table
     * @return string
     */
    protected function render_instantquiz_table($table) {
        return html_writer::table($table);
    }
}
