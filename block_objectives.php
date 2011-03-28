<?php

require_once(dirname(__FILE__).'/mod_form.php');
require_once(dirname(__FILE__).'/lib.php');

class block_objectives extends block_base {

    function init() {
        $this->title = get_string('pluginname','block_objectives');
    }

    function preferred_width() {
        return 240;
    }

    function get_content() {
        global $COURSE;
        
        if ($this->content !== NULL) {
            return $this->content;
        }
        
        //UT
        $obj = new block_objectives_class($COURSE);

        $this->content = new stdClass;
        $this->content->text = $obj->get_block_text();
        $this->content->footer = $obj->get_block_footer();

        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function instance_config_print() {
        global $COURSE;

        //UT
        $obj = new block_objectives_class($COURSE);
        $settings = $obj->get_settings();

        $returl = new moodle_url('/course/view.php', array('id'=>$settings->course));
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
        //UT
        $this->instance_config_print();
        return true;
    }
}

?>