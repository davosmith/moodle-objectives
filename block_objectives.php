<?php

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
}

?>