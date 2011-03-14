<?php

require_once(dirname(__FILE__).'/mod_form.php');

class block_objectives extends block_base {

    function init() {
        $this->title = get_string('pluginname','block_objectives');
        $this->version = 2011031300;
    }

    function _get_settings() {
        global $COURSE;

        $settings = get_record('objectives','course',$COURSE->id);
        if (!$settings) {
            $settings = new stdClass;
            $settings->course = $COURSE->id;
            $settings->intro = get_string('defaultintro','block_objectives');
            $settings->id = insert_record('objectives',$settings);
        }

        return $settings;
    }

    function get_content() {
        global $CFG;
        
        if ($this->content !== NULL) {
            return $this->content;
        }

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (!has_capability('block/objectives:viewobjectives', $context)) {
            return NULL;
        }

        $settings = $this->_get_settings();
        $editlink = $CFG->wwwroot.'/blocks/objectives/edit.php?course='.$settings->course;

        $this->content = new stdClass;
        $this->content->footer = '<a href="'.$editlink.'">Edit objectives</a>';
        $this->content->text = '<strong>'.userdate(time(), get_string('strftimedaydate')).'</strong><br/>';
        $this->content->text .= s($settings->intro);

        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function instance_config_print() {
        global $CFG, $COURSE;
        
        $settings = $this->_get_settings();

        $returl = $CFG->wwwroot.'/course/view.php?id='.$settings->course;
        $mform = new block_objectives_edit_form(qualified_me());

        $settings->objectivesid = $settings->id;
        unset($settings->id);
        $mform->set_data($settings);

        if ($mform->is_cancelled()) {
            redirect($returl);
        }

        if ($data = $mform->get_data() and $data->action == 'savesettings') {

            $update = new stdClass;
            $update->id = $data->objectivesid;
            //$update->course = $data->course; // Should not change
            $update->intro = $data->intro;
            update_record('objectives',$update);

            redirect($returl);
        }

        echo '</form>'; // Close the 'helpful' form provided
        $mform->display();
        echo '<form>'; // Start a fake form, to make sure the tags match
        
        return true;
    }

    function instance_config_save($data) {
        $this->instance_config_print();
        return true;
    }
}

?>