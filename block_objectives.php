<?php

require_once(dirname(__FILE__).'/mod_form.php');

class block_objectives extends block_base {

    function init() {
        $this->title = get_string('pluginname','block_objectives');
        $this->version = 2011031300;
    }

    function get_content() {
        global $COURSE;
        
        if ($this->content !== NULL) {
            return $this->content;
        }

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (!has_capability('blocks/objectives:viewobjectives', $context)) {
            return NULL;
        }

        $this->content = new stdClass;
        $this->content->footer = 'Edit objectives';
        $this->content->text = date('j M Y');
        $this->content->text .= 'This lesson you need to ...';

        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function instance_config_print() {
        global $CFG, $COURSE;
        echo '</form>'; // Close the 'helpful' form provided
        
        $settings = get_record('objectives','course',$COURSE->id);
        if (!$settings) {
            $settings = new stdClass;
            $settings->course = $COURSE->id;
            $settings->intro = get_string('defaultintro','block_objectives');
            $settings->id = insert_record('objectives',$settings);
        }

        $returl = $CFG->wwwroot.'/course/view.php?id='.$settings->course;
        $mform = new block_objectives_edit_form();

        $mform->set_data($settings);

        if ($mform->is_cancelled()) {
            redirect($returl);
        }

        if ($data = $mform->get_data() and $data->action == 'savesettings') {
            $update = new stdClass;
            $update->id = $data->id;
            //$update->course = $COURSE->id; // Should not change
            $update->intro = $data->intro;
            update_record('objectives',$update);

            echo 'Hello world';
            die();

            redirect($returl);
        }

        $mform->display();

        echo '<form>'; // Start a fake form, to make sure the tags match
        
        return true;
    }

    function instance_config_save($data) {
        echo "What are you doing here?";
        die();
        return true;
    }
}

?>