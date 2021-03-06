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
 * class instantquiztmpl_basic_instantquiz
 *
 * @package    instantquiztmpl_basic
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions for one instance of instant quiz
 *
 * @package    instantquiztmpl_basic
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiztmpl_basic_instantquiz extends instantquiz_instantquiz {

    /**
     * Returns true if user is able to view statistics
     *
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    protected function can_view_summary($returnonly = true) {
        $context = $this->get_context();
        if ($returnonly) {
            if (!has_capability('instantquiztmpl/basic:viewsummary', $context)) {
                return false;
            }
        } else {
            require_capability('instantquiztmpl/basic:viewsummary', $context);
        }
        return parent::can_view_summary($returnonly);
    }
}
