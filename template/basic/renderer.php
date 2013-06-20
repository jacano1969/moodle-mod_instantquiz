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
 * Renderer
 *
 * @package    instantquiztmpl_basic
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for instantquiztmpl_basic
 *
 * @package    instantquiztmpl_basic
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiztmpl_basic_renderer extends plugin_renderer_base {
    /** @var mod_instantquiz_renderer instance of mod_instantquiz_renderer */
    var $instantquizrenderer;

    /**
     * Constructor method, calls the parent constructor and initialises
     * an instance of mod_instantquiz_renderer
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->instantquizrenderer = $page->get_renderer('mod_instantquiz', null, $target);
    }

    /**
     * Renders the provided widget and returns the HTML to display it.
     *
     * This extends the usual workflow of 'render()' method - we first search for
     * methods defined in this renderer (for the class name or parent class name)
     * and if not found, pass the object to the mod_instantquiz_renderer which
     * will itself pass it to core_renderer if the rendering method is not found.
     *
     * @param renderable $widget
     * @return string
     */
    public function render(renderable $widget) {
        if (method_exists($this, 'render_'. get_class($widget))) {
            return $this->{'render_'. get_class($widget)}($widget);
        }
        foreach (class_parents($widget) as $parentclass) {
            if (method_exists($this, 'render_'. $parentclass)) {
                return $this->{'render_'. $parentclass}($widget);
            }
        }
        return $this->instantquizrenderer->render($widget);
    }

    /**
     * EXAMPLE of overwriting a renderer from mod_instantquiz
     *
     * Note that regardless of whether this plugin overwrites class
     * instantquiz_attempt or not, we still can name the method
     * render_instantquiz_attempt() instead of render_instantquiztmpl_basic_attempt()
     *
     * @param instantquiz_attempt
     * @return string
     */
    /*public function render_instantquiz_attempt(instantquiz_attempt $entity) {
        return $this->instantquizrenderer->render($entity);
    }*/
}