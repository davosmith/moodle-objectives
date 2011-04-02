<?php

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
        
        $obj = new block_objectives_class($COURSE);

        $this->content = new stdClass;
        $this->content->text = $obj->get_block_text();
        $this->content->footer = $obj->get_block_footer();

        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }
}

?>