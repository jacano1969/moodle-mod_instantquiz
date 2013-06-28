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
 * class instantquiz_feedback
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions to deal with one instantquiz feedback
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_feedback extends instantquiz_entity implements renderable {
    var $feedback;
    var $feedbackformat;
    var $addinfo;

    /**
     * Returns the name of DB table (used in functions get_all() and update() )
     *
     * @return string
     */
    public static function get_table_name() {
        return 'instantquiz_feedback';
    }

    /**
     * Creates a feedback with default text
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return instantquiz_feedback
     */
    public static function create($instantquiz) {
        $defaultvalues = new stdClass();
        $all = self::get_all($instantquiz);
        $defaultvalues->sortorder = count($all);
        $defaultvalues->feedback = get_string('deffeedback', 'mod_instantquiz', $defaultvalues->sortorder + 1);
        $entity = new static($instantquiz, $defaultvalues);
        $entity->update();
        $instantquiz->summary->entity_updated($entity);
        return $entity;
    }

    /**
     * Constructor from DB record
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param stdClass $record
     */
    protected function __construct($instantquiz, $record) {
        $this->instantquiz = $instantquiz;
        foreach ($record as $key => $value) {
            if (property_exists($this, $key) && $key !== 'addinfo') {
                $this->$key = $value;
            }
        }
        $this->addinfo = array();
        if (!empty($record->addinfo) && $addinfo = @json_decode($record->addinfo)) {
            $this->addinfo = convert_to_array($addinfo);
        }
    }

    /**
     * Updates or creates entry in DB
     */
    public function update() {
        global $DB;
        $record = array(
            'sortorder' => $this->sortorder,
            'feedback' => $this->feedback,
            'feedbackformat' => $this->feedbackformat,
            'addinfo' => json_encode($this->addinfo)
        );
        if ($this->id) {
            $record['id'] = $this->id;
            $DB->update_record($this->get_table_name(), $record);
        } else {
            $record['instantquizid'] = $this->instantquiz->id;
            $this->id = $DB->insert_record($this->get_table_name(), $record);
        }
    }

    /**
     * Evaluates if the points earned in the current attempt match this feedback display condition (formula)
     *
     * This function is called from {@link instantquiz_attempt::update_and_evaluate()}
     *
     * @param instantquiz_attempt $attempt current attempt (it is not saved yet)
     * @return bool
     */
    public function is_applicable($attempt) {
        $points = $attempt->points['c'];
        if (!isset($this->addinfo['formula']) || !strlen(trim($this->addinfo['formula']))) {
            // Empty formula means it is always applicable
            return true;
        }
        $formula = $this->addinfo['formula'];
        $criteria = $this->instantquiz->get_entities('criterion');
        foreach ($criteria as $critid => $criterion) {
            $xcriterion = preg_quote('${'.$criterion->criterion.'}', '/');
            $formula = preg_replace("/$xcriterion/i", $points[$critid], $formula);
        }
        // replace misspelled criteria with 0 points
        $formula = preg_replace("/\$\{.*?\}/i", 0, $formula);
        // TODO check that $formula contains only allowed tokens. eval() is dangerous!
        return eval('return '.$formula.';');
    }

    /**
     * Static default feedback displayed when none of defined feedbacks are applicable (usually 'Thank you')
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return instantquiz_feedback
     */
    public static function get_default_feedback($instantquiz) {
        return new static($instantquiz,
                (object)array('feedback' => 'Thank you',
                    'feedbackformat' => FORMAT_HTML));
    }

    /**
     * Returns the text of the feedback formatted for display
     *
     * @param array $params additional params to pass to format_text()
     * @return string
     */
    public function get_formatted_feedback($params = array(), $truncate = false) {
        // TODO truncate
        $text = format_text($this->feedback, $this->feedbackformat,
            array('context' => $this->instantquiz->get_context()) + $params);
        if ($truncate) {
            $text = strip_tags($text);
            if (strlen($text) > 200) {
                $text = substr($text, 0, 200). '...';
            }
        }
        return $text;
    }
}