<?php

class block_objectives_edit_form extends block_edit_form {
    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        $mform->addElement('text', 'objectivesintro', get_string('introduction','block_objectives'), array('size' => 60));
        
        $mform->addElement('hidden', 'objectivesid', 0);
        $mform->setType('objectivesid', PARAM_INT);
        $mform->addElement('hidden', 'course', 0);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'action', 'savesettings');
        $mform->setType('action', PARAM_TEXT);
    }

    function set_data($defaults) {
        global $COURSE, $DB;

        $obj = new block_objectives_class($COURSE);
        $settings = $obj->get_settings();

        $defaults->objectivesintro = $settings->intro;
        $defaults->objectivesid = $settings->id;
        $defaults->course = $settings->course;

        parent::set_data($defaults);

        // If the form has been submitted
        if (!$this->is_cancelled() && $this->is_submitted() && $this->is_validated()) {
            $mform = $this->_form;
            $data = (object)$mform->exportValues();
            if ($data->action == 'savesettings') {
                // Save out the settings I am interested in
                $upd = new stdClass;
                $upd->id = $data->objectivesid;
                $upd->intro = $data->objectivesintro;
                $DB->update_record('objectives',$upd);

                // Remove these, to prevent them being saved into the block config
                $mform->removeElement('objectivesintro');
                $mform->removeElement('objectivesid');
                $mform->removeElement('course');
                $mform->removeElement('action');
            }
        }
    }
}

?>