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
     * Renders the provided widget and returns the HTML to display it.
     *
     * @param renderable $widget
     * @return string
     */
    public function render(renderable $widget) {
        try {
            return parent::render($widget);
        } catch (coding_exception $e) {
            foreach (class_parents($widget) as $parentclass) {
                if (method_exists($this, 'render_'. $parentclass)) {
                    return $this->{'render_'. $parentclass}($widget);
                }
            }
            throw $e;
        }
    }

    /**
     * Renderer for instantquiz_collection
     *
     * @param instantquiz_collection $collection
     * @return string
     */
    protected function render_instantquiz_collection($collection) {
        $output = '';
        if ($collection->object instanceof renderable) {
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
     * Wraps content in div with CSS classes for each entity class/parent name and displaymode
     * Also adds edit/delete controls in DISPLAYMODE_EDIT
     *
     * @param instantquiz_entity $entity
     * @param string $content
     * @return string
     */
    public function render_instantquiz_entity(instantquiz_entity $entity, $content) {
        $cssclasses = array_values(class_parents($entity));
        array_unshift($cssclasses, get_class($entity));
        if (preg_match('/^instantquiz_(.*)$/', get_class($entity), $matches)) {
            // if class was not overridden in the template, still add class name as it would be called in the template.
            array_unshift($cssclasses, $entity->instantquiz->template. '_'. $matches[1]);
        }
        $cssclasses[] = $entity->instantquiz->displaymode;
        if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_EDIT) {
            $controls = '';
            if (preg_match('/^.*_(.*?)$/', get_class($entity), $matches)) {
                $entityname = $matches[1];
                $controls .= html_writer::link($entity->instantquiz->manage_link(array('cmd' => 'edit',
                    'entity' => $entityname, 'entityid['.$entity->id.']' => 1)), get_string('edit'));
                $controls .= html_writer::link($entity->instantquiz->manage_link(array('cmd' => 'delete',
                    'entity' => $entityname, 'entityid['.$entity->id.']' => 1)), get_string('delete'));
            }
            $content = html_writer::tag('div', $controls, array('class' => 'controls')).
                    html_writer::tag('div', $content, array('class' => 'content'));
        }
        return html_writer::tag('div', $content,
                array('class' => join(' ', $cssclasses)));
    }

    /**
     * Renders the instantquiz feedback to show to the user
     *
     * This function initially is 'public' so it can be called from template renderers
     *
     * @param instantquiz_feedback $entity
     * @return string
     */
    public function render_instantquiz_feedback(instantquiz_feedback $entity) {
        $rv = '';
        if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_NORMAL ||
                $entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_REVIEW) {
            $rv = $entity->get_formatted_feedback();
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_EDIT ||
                $entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_PREVIEW) {
            $rv = $entity->get_formatted_feedback(array(), true);
            if (!empty($entity->addinfo['formula'])) {
                $rv .= '<div><b>'. $entity->addinfo['formula']. '</b></div>';
            }
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_SUMMARY) {
            $summary = $entity->instantquiz->summary->by_feedback($entity);
            $rv = $entity->get_formatted_feedback(array(), true). ' : ';
            $rv .= $summary->feedbacks;
            if ($summary->totalcount) {
                $rv .= ' = '. sprintf("%d", $summary->feedbacks/$summary->totalcount*100). '%';
            }
        }
        return $this->render_instantquiz_entity($entity, $rv);
    }

    /**
     * Renders the instantquiz criterion to show to the user
     *
     * This function initially is 'public' so it can be called from template renderers
     *
     * @param instantquiz_criterion $entity
     * @return string
     */
    public function render_instantquiz_criterion(instantquiz_criterion $entity) {
        $rv = '';
        if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_PREVIEW ||
                $entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_EDIT) {
            $rv = $entity->criterion;
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_SUMMARY) {
            $summary = $entity->instantquiz->summary->by_criterion($entity);
            $rv = $entity->criterion. ' : ';
            if ($summary->totalcount) {
                $rv .= sprintf("%.2f", $summary->points/$summary->totalcount). ' / '. $summary->maxpoints;
                if ($summary->maxpoints) {
                    $rv .= ' = '. sprintf("%d", $summary->points/$summary->totalcount/$summary->maxpoints*100). '%';
                }
            } else {
                $rv .= '- / '. $summary->maxpoints;
            }
        }
        return $this->render_instantquiz_entity($entity, $rv);
    }

    /**
     * Renders the instantquiz question to show to the user
     *
     * This function initially is 'public' so it can be called from template renderers
     *
     * @param instantquiz_question $entity
     * @return string
     */
    public function render_instantquiz_question(instantquiz_question $entity) {
        $rv = format_text($entity->question, $entity->questionformat,
            array('context' => $entity->instantquiz->get_context()));

        if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_REVIEW) {
            // DISPLAYMODE_REVIEW
            $answer = $entity->currentanswer;
            if (!empty($entity->options)) {
                $rv .= html_writer::start_tag('ul');
                foreach ($entity->options as $option) {
                    if ($answer && $answer == $option['idx']) {
                        $rv .= html_writer::tag('li', $option['value']);
                    }
                }
                $rv .= html_writer::end_tag('ul');
            }
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_NORMAL) {
            // DISPLAYMODE_NORMAL
            // just the question text, the options are the part of the form
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_PREVIEW ||
                $entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_EDIT) {
            // DISPLAYMODE_PREVIEW, DISPLAYMODE_EDIT
            if (!empty($entity->options)) {
                $lines = array();
                foreach ($entity->options as $option) {
                    $lines[] = html_writer::tag('li', $option['value']);
                }
                $rv .= html_writer::tag('ul', join('', $lines));
            }
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_SUMMARY) {
            // DISPLAYMODE_SUMMARY
            $summary = $entity->instantquiz->summary->by_question($entity);
            $criteria = $entity->instantquiz->get_entities('criterion');
            $rv .= html_writer::start_tag('ul');
            foreach ($criteria as $criterion) {
                $rv .= html_writer::start_tag('li');
                $rv .= $criterion->criterion. ' : ';
                if ($summary->totalcount) {
                    $rv .= sprintf("%.2f", $summary->points[$criterion->id]/$summary->totalcount). ' / '. $summary->maxpoints[$criterion->id];
                    if ($summary->maxpoints[$criterion->id]) {
                        $rv .= ' = '. sprintf("%d", $summary->points[$criterion->id]/$summary->totalcount/$summary->maxpoints[$criterion->id]*100). '%';
                    }
                } else {
                    $rv .= '- / '. $summary->maxpoints[$criterion->id];
                }
                $rv .= html_writer::end_tag('li');
            }
            $rv .= html_writer::end_tag('ul');
            /*
            if (!empty($entity->options)) {
                $lines = array();
                foreach ($entity->options as $option) {
                    $lines[] = html_writer::tag('li', $option['value']);
                }
                $rv .= html_writer::tag('ul', join('', $lines));
            }
             */
        }
        return $this->render_instantquiz_entity($entity, $rv);
    }

    /**
     * Renders the instantquiz attempt to show to the user
     *
     * This function initially is 'public' so it can be called from template renderers
     *
     * @param attempt
     * @return string
     */
    public function render_instantquiz_attempt(instantquiz_attempt $entity) {
        $rv = '';
        if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_REVIEW) {
            foreach ($entity->instantquiz->get_entities('question', $entity->instantquiz->displaymode) as $question) {
                $question->currentanswer = $entity->get_answer($question->id);
                $rv .= $this->render($question);
            }
            foreach ($entity->get_feedbacks() as $feedback) {
                $rv .= $this->render($feedback);
            }
        }
        else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_NORMAL) {
            foreach ($entity->get_feedbacks() as $feedback) {
                $rv .= $this->render($feedback);
            }
            $rv .= $this->output->single_button(new moodle_url('/mod/instantquiz/view.php',
                    array('id' => $entity->instantquiz->get_cm()->id)),
                    get_string('back'), 'GET');
        } else if ($entity->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_PREVIEW) {
            $rv .= html_writer::link($entity->instantquiz->results_link(array('attemptid' => $entity->id)), 'USER '.$entity->userid.' at '.
                    userdate($entity->timefinished)); // TODO strings
        }

        return $this->render_instantquiz_entity($entity, $rv);
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
     * Renderer for instantquiz_table (makes an html_table renderable)
     *
     * @param instantquiz_table $table
     * @return string
     */
    protected function render_instantquiz_table($table) {
        return html_writer::table($table);
    }

    /**
     * Renderer for the list of entities (question, feedback, criterion, attempt)
     *
     * @param instantquiz_entitylist $entitylist
     */
    public function render_instantquiz_entitylist($entitylist) {
        $output = '';
        if ($entitylist->instantquiz->displaymode === instantquiz_instantquiz::DISPLAYMODE_EDIT &&
                in_array($entitylist->entitytype, array('question', 'feedback', 'criterion'))) {
            if (!empty($entitylist->entities)) {
                foreach ($entitylist->entities as $entity) {
                    $output .= $this->render($entity);
                }
                $output .= $this->single_button($entitylist->instantquiz->manage_link(array('cmd' => 'edit',
                    'entity' => $entitylist->entitytype)),
                    get_string('edit'));
            }
            $output .= $this->single_button($entitylist->instantquiz->manage_link(array('cmd' => 'add',
                'entity' => $entitylist->entitytype)),
                get_string('add'. $entitylist->entitytype, 'mod_instantquiz'));
        } else {
            // By default just render each element
            if (!empty($entitylist->entities)) {
                foreach ($entitylist->entities as $entity) {
                    $output .= $this->render($entity);
                }
            }
        }
        return $output;
    }

    /**
     * Renderer for summary
     *
     * @param instantquiz_summary $summary
     * @return string
     */
    public function render_instantquiz_summary(instantquiz_summary $summary) {
        $output = html_writer::tag('h3', get_string('summary', 'mod_instantquiz'));
        if (!empty($summary->totalcount)) {
            $output .= html_writer::tag('div',
                html_writer::link($summary->instantquiz->results_link(), $summary->totalcount. ' submissions'), // TODO string
                    array('class' => 'totalcount'));
        }

        $summary->instantquiz->displaymode = instantquiz_instantquiz::DISPLAYMODE_SUMMARY;

        if (!empty($summary->points['c'])) {
            // Show statistics by criterion
            $output .= html_writer::tag('h4', get_string('summarybycriterion', 'mod_instantquiz'));
            $criteria = $summary->instantquiz->get_entities('criterion');
            foreach ($criteria as &$criterion) {
                $output .= $this->render($criterion);
            }
        }

        if (!empty($summary->feedbacks)) {
            // Show statistics by feedback
            $output .= html_writer::tag('h4', get_string('summarybyfeedback', 'mod_instantquiz'));
            $feedbacks = $summary->instantquiz->get_entities('feedback');
            foreach ($feedbacks as &$feedback) {
                $output .= $this->render($feedback);
            }
        }

        if (!empty($summary->points['q']) || !empty($summary->answers)) {
            // Show statistics by question
            $output .= html_writer::tag('h4', get_string('summarybyquestion', 'mod_instantquiz'));
            $questions = $summary->instantquiz->get_entities('question');
            foreach ($questions as &$question) {
                $output .= $this->render($question);
            }
        }

        $classes = array('instantquiz_summary', $summary->instantquiz->template. '_summary');
        return html_writer::tag('div', $output,
                array('class' => join(' ', $classes)));
    }
}
