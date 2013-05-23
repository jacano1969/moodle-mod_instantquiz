<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/mod/instantquiz/classes/question.class.php');
require_once($CFG->libdir. '/formslib.php');

class instantquiz_question_form extends moodleform {
    protected function definition() {
        $entity = $this->_customdata;
    }
}