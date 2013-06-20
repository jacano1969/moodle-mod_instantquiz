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
 * class instantquiz_entitylist
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains a list of instantquiz entities for rendering purposes (criterion, question, feedback, attempt)
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instantquiz_entitylist implements renderable {
    /** @var instantquiz_instantquiz instantquiz */
    var $instantquiz;
    /** @var string entity type (criterion, question, feedback, attempt) */
    var $entitytype;
    /** @var array array of entities */
    var $entities;

    /**
     * Constructor
     *
     * @param instantquiz_instantquiz $instantquiz
     * @param string $entitytype
     * @param array $entities
     */
    public function __construct($instantquiz, $entitytype, $entities = null) {
        $this->instantquiz = $instantquiz;
        $this->entitytype = $entitytype;
        if ($entities === null) {
            $this->entities = $instantquiz->get_entities($entitytype);
        } else {
            $this->entities = $entities;
        }
    }
}