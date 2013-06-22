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
 * class instantquiztmpl_basic_attempt
 *
 * @package    instantquiztmpl_basic
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Students performs an attempt to answer the instantquiz questions
 *
 * @package    instantquiztmpl_basic
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiztmpl_basic_attempt extends instantquiz_attempt {

    /**
     * Checks if user can continue current attempt
     *
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    public function can_continue_attempt($returnonly = true) {
        $context = $this->instantquiz->get_context();
        if (!parent::can_continue_attempt($returnonly)) {
            return false;
        }
        if ($returnonly) {
            return has_capability('instantquiztmpl/basic:attempt', $context);
        } else {
            require_capability('instantquiztmpl/basic:attempt', $context);
        }
    }

    /**
     *
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    public function can_view_attempt($returnonly = true) {
        global $USER;
        if (!parent::can_view_attempt($returnonly)) {
            return false;
        }
        $context = $this->instantquiz->get_context();
        if (($USER->id == $this->userid && !$this->overriden && has_capability('instantquiztmpl/basic:viewownattempt', $context))
                    || has_capability('instantquiztmpl/basic:viewanyattempt', $context)) {
            return true;
        }
        if ($returnonly) {
            return false;
        } else if ($USER->id == $this->userid && !$this->overriden) {
            throw new required_capability_exception($context, 'instantquiztmpl/basic:viewownattempt');
        } else {
            throw new required_capability_exception($context, 'instantquiztmpl/basic:viewanyattempt');
        }
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param bool $returnonly if false, will throw an exception instead of returning false.
     * @return bool
     */
    public static function can_start_attempt(instantquiz_instantquiz $instantquiz, $returnonly = true) {
        if (!parent::can_start_attempt($instantquiz, $returnonly)) {
            return false;
        }
        $context = $instantquiz->get_context();
        if ($returnonly) {
            return has_capability('instantquiztmpl/basic:attempt', $context);
        } else {
            require_capability('instantquiztmpl/basic:attempt', $context);
        }
    }

    /**
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param int $userid
     * @return instantquiz_attempt
     */
    public static function get_user_attempts_history($instantquiz, $userid) {
        global $USER;
        $context = $instantquiz->get_context();
        if (($USER->id == $userid && has_capability('instantquiztmpl/basic:viewownattempt', $context))
                || has_capability('instantquiztmpl/basic:viewanyattempt', $context)) {
            return parent::get_user_attempts_history($instantquiz, $userid);
        }
        return array();
    }

    /**
     * @return array
     */
    public static function get_all_attempts($instantquiz) {
        global $USER;
        $context = $instantquiz->get_context();
        if (has_capability('instantquiztmpl/basic:viewanyattempt', $context)) {
            return static::get_all($instantquiz);
        } else if (has_capability('instantquiztmpl/basic:viewownattempt', $context) &&
                ($attempt = parent::get_user_attempt($instantquiz, $USER->id))) {
            return array($attempt);
        }
        return array();
    }
}