<?php

require_once($CFG->libdir.'/formslib.php');

class block_objectives_edit_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('text', 'intro', get_string('introduction'), array('size' => 40));
        
        $mform->addElement('hidden', 'objectivesid', 0);
        $mform->setType('objectivesid', PARAM_INT);
        $mform->addElement('hidden', 'course', 0);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'action', 'savesettings');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }
}

?>