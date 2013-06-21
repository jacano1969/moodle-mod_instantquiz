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
 * class instantquiz_question
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains information and useful functions to deal with one instantquiz question
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_summary implements renderable, IteratorAggregate {
    var $instantquiz;
    protected $summarycached;

    public function __construct(instantquiz_instantquiz $instantquiz, $storedvalue) {
        $this->instantquiz = $instantquiz;
        $this->summarycached = null;
        if (!empty($storedvalue) && $summary = @json_decode($storedvalue)) {
            $this->summarycached = convert_to_array($summary);
        }
    }

    public function __get($name) {
        $summary = $this->get_summary();
        return $summary[$name];
    }

    public function __isset($name) {
        $summary = $this->get_summary();
        return isset($summary[$name]);
    }

    public function getIterator() {
        $summary = $this->get_summary();
        return new ArrayIterator($summary);
    }

    protected function get_summary() {
        if ($this->summarycached !== null) {
            return $this->summarycached;
        }
        $this->summarycached = $this->calculate();
        $this->instantquiz->update_summary(json_encode($this->summarycached));
        return $this->summarycached;
    }

    public function reset() {
        if ($this->summarycached !== null) {
            $this->summarycached = null;
            $this->instantquiz->update_summary(null);
        }
    }

    protected function calculate() {
        $summary = array();
        $attempts = $this->instantquiz->get_entities('attempt');
        $summary['totalcount'] = count($attempts);
        return $summary;
    }

    public function entity_updated($entitytype, $oldvalue, $newvalue) {
        // TODO some updates do not change stats or can be recalculated
        $this->reset();
    }
}