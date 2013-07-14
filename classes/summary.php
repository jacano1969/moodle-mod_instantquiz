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
        $feedbacks = $this->instantquiz->get_entities('feedback');
        $questions = $this->instantquiz->get_entities('question');
        $criteria = $this->instantquiz->get_entities('criterion');

        $summary['totalcount'] = count($attempts);
        $summary['feedbacks'] = array();
        $summary['answers'] = array();
        $summary['points'] = array('q' => array(), 'c' => array());
        foreach ($feedbacks as &$feedback) {
            $summary['feedbacks'][$feedback->id] = 0;
        }
        foreach ($criteria as &$criterion) {
            $summary['points']['c'][$criterion->id] = 0;
            $summary['maxpoints']['c'][$criterion->id] = 0;
        }
        foreach ($questions as &$question) {
            $summary['points']['q'][$question->id] = array();
            $maxpoints = $question->max_possible_points();
            foreach ($criteria as &$criterion) {
                $summary['points']['q'][$question->id][$criterion->id] = 0;
                $summary['maxpoints']['q'][$question->id][$criterion->id] = 0;
                if (!empty($maxpoints[$criterion->id])) {
                    $summary['maxpoints']['q'][$question->id][$criterion->id] = $maxpoints[$criterion->id];
                    $summary['maxpoints']['c'][$criterion->id] += $maxpoints[$criterion->id];
                }
            }
            foreach ($attempts as &$attempt) {
                $answers = $question->get_answers_for_summary($attempt);
                if (!empty($answers)) {
                    if (!isset($summary['answers'][$question->id])) {
                        $summary['answers'][$question->id] = array();
                    }
                    $summary['answers'][$question->id][] = $answers;
                }
            }
        }
        foreach ($attempts as &$attempt) {
            foreach ($attempt->feedbacks as $fid) {
                $summary['feedbacks'][$fid] = empty($summary['feedbacks'][$fid]) ? 1 : ($summary['feedbacks'][$fid]+1);
            }
            foreach ($attempt->points['c'] as $cid => $points) {
                if (!isset($summary['points']['c'][$cid])) { $summary['points']['c'][$cid] = 0; }
                $summary['points']['c'][$cid] += $points;
            }
            foreach ($attempt->points['q'] as $qid => $critpoints) {
                foreach ($critpoints as $cid => $points) {
                    if (!isset($summary['points']['q'][$qid])) { $summary['points']['q'][$qid] = array(); }
                    if (!isset($summary['points']['q'][$qid][$cid])) { $summary['points']['q'][$qid][$cid] = 0; }
                    $summary['points']['q'][$qid][$cid] += $points;
                }
            }
        }
        return $summary;
    }

    /**
     * Notifies that the entity is about to be updated
     *
     * This function is called prior to the actual 'update' in DB
     * so it can query the current state if needed
     *
     * @param mixed $entity
     * @param mixed $oldvalue may be an array of old values or an old entity, or null if the entity was just created
     */
    public function entity_updated($entity, $oldvalue = null) {
        /*
        if ($oldvalue === null) {
            echo "created entity ".get_class($entity)." - ". $entity->id. '<br>';
        } else {
            echo "updated entity ".get_class($entity)." - ". $entity->id."; changed fields = ".join(', ', array_keys(convert_to_array($oldvalue))).'<br>';
        }
         */
        if ($this->summarycached === null) {
            // The summary is already reset, no need to check anything
            return;
        }
        // TODO some updates do not change stats or can be recalculated
        $this->reset();
    }

    /**
     * Returns summary by criterion
     *
     * @param instantquiz_criterion $criterion
     * @return stdClass;
     */
    public function by_criterion($criterion) {
        // TODO check for empty ?
        return (object)array(
            'totalcount' => $this->totalcount,
            'points' => $this->points['c'][$criterion->id],
            'maxpoints' => $this->maxpoints['c'][$criterion->id],
        );
    }

    /**
     * Returns summary by feedback
     *
     * @param instantquiz_feedback $feedback
     * @return stdClass;
     */
    public function by_feedback($feedback) {
        // TODO check for empty ?
        return (object)array(
            'totalcount' => $this->totalcount,
            'feedbacks' => $this->feedbacks[$feedback->id],
        );
    }

    /**
     * Returns summary by question
     *
     * @param instantquiz_question $question
     * @return stdClass;
     */
    public function by_question($question) {
        // TODO check for empty ?
        return (object)array(
            'totalcount' => $this->totalcount,
            'points' => $this->points['q'][$question->id],
            'maxpoints' => $this->maxpoints['q'][$question->id],
            'answers' => $this->answers[$question->id],
        );
    }
}