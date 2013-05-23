<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/instantquiz/templatebase.php');

class instantquiztmpl_basic_1 extends instantquiz_tmpl {

    public static function get_template_name() {
        return 'Basic 1';
    }

    public static function edit_form($form) {
        $mform = $form->_form;
        $mform->addElement('select', 'addinfo[numoptions]', 'Number of options in one question', array(2,3,4,5));
    }

}