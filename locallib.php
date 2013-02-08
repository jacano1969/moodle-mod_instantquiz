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
 * Internal library of functions for module instantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions for one instance of instant quiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz {
    /** @var stdClass record from DB table instantquiz */
    protected $record;
    /** @var stdClass|cm_info information about course module */
    protected $cm;

    /**
     * Constructor
     *
     * @param stdClass|cm_info $cm information about course module
     */
    public function __construct($cm) {
        global $DB;
        $this->cm = $cm;
        $this->record = $DB->get_record('instantquiz', array('id' => $cm->instance), '*', MUST_EXIST);
    }

    /**
     * Magic method to get instantquiz properties
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->record->$name;
    }

    /**
     * Returns the module context
     *
     * @return context
     */
    public function get_context() {
        return context_module::instance($this->cm->id);
    }

    /**
     * Returns a link to the manage page with the given arguments
     *
     * @param array $params
     * @return moodle_url
     */
    public function manage_link($params = array()) {
        return new moodle_url('/mod/instantquiz/manage.php', array('id' => $this->cm->id) + $params);
    }

    /**
     * Returns all evaluations used in instantquiz
     *
     * @return array of instantquiz_evaluation
     */
    public function get_evaluations() {
        global $CFG;
        require_once($CFG->dirroot. '/mod/instantquiz/classes/evaluation.class.php');
        return instantquiz_evaluation::get_all($this);
    }

    /**
     * Creates an evaluation criterion
     *
     * @return instantquiz_evaluation
     */
    public function add_evaluation() {
        global $CFG;
        require_once($CFG->dirroot. '/mod/instantquiz/classes/evaluation.class.php');
        return instantquiz_evaluation::create_empty($this);
    }

    /**
     * Returns all feedbacks used in instantquiz
     *
     * @return array of instantquiz_evaluation
     */
    public function get_feedbacks() {
        global $CFG;
        require_once($CFG->dirroot. '/mod/instantquiz/classes/feedback.class.php');
        return instantquiz_feedback::get_all($this);
    }

    /**
     * Creates a feedback
     *
     * @return instantquiz_feedback
     */
    public function add_feedback() {
        global $CFG;
        require_once($CFG->dirroot. '/mod/instantquiz/classes/feedback.class.php');
        return instantquiz_feedback::create_empty($this);
    }
}
