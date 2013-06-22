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
 * class instantquiz_criterion
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions to deal with one instantquiz evaluation criterion
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_criterion extends instantquiz_entity {
    var $criterion;
    var $addinfo;

    /**
     * Returns the name of DB table (used in functions get_all(), delete() and update() )
     *
     * @return string
     */
    protected static function get_table_name() {
        return 'instantquiz_criterion';
    }

    /**
     * Creates an evaluation criterion with default name
     *
     * @param instantquiz_instantquiz $instantquiz
     * @return instantquiz_criterion
     */
    public static function create($instantquiz) {
        $defaultvalues = new stdClass();
        $all = self::get_all($instantquiz);
        $defaultvalues->sortorder = count($all);
        $defaultvalues->criterion = get_string('defcriterionname', 'mod_instantquiz', $defaultvalues->sortorder + 1);
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
        if (isset($record->addinfo) && ($addinfo = @json_decode($record->addinfo))) {
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
            'criterion' => $this->criterion,
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
     * Given the number of points earned for each question by each criterion
     * returns the total number of points for this criterion int the attempt
     *
     * This function is called from {@link instantquiz_attempt::update_and_evaluate()}
     *
     * @param instantquiz_attempt $attempt current attempt (it is not saved yet)
     * @return int
     */
    public function get_total_points($attempt) {
        $pointsbyquestion = $attempt->points['q'];
        $rv = 0;
        foreach ($pointsbyquestion as $qid => $points) {
            if (!empty($points[$this->id])) {
                $rv += $points[$this->id];
            }
        }
        return $rv;
    }
}
