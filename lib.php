<?php

require_once($CFG->libdir.'/formslib.php');

class block_objectives_class {
    
    var $settings;
    var $context;
    var $course;

    function block_objectives_class($course) {
        if (is_int($course)) {
            $course = get_record('course','id',$course);
            if (!$course) {
                error('Invalid course id');
            }
        }

        $this->course = $course;
        
        $this->settings = get_record('objectives','course',$course->id);
        if (!$this->settings) {
            $this->settings = new stdClass;
            $this->settings->course = $course->id;
            $this->settings->intro = get_string('defaultintro','block_objectives');
            $this->settings->id = insert_record('objectives',$settings);
        }

        $this->context = get_context_instance(CONTEXT_COURSE, $course->id);
    }

    function get_settings() { return $this->settings; }

    function can_view_objectives() { return has_capability('block/objectives:viewobjectives', $this->context); }
    function can_edit_objectives() { return has_capability('block/objectives:editobjectives', $this->context); }
    function can_edit_timetables() { return has_capability('block/objectives:edittimetables', $this->context); }
    function can_checkoff_objectives() { return has_capability('block/objectives:checkofftimetables', $this->context); }

    function get_block_text() {
        if (!$this->can_view_objectives()) {
            return null;
        }

        $text = '<strong>'.userdate(time(), get_string('strftimedaydate')).'</strong><br/>';
        $text .= s($this->settings->intro);

        return $text;
    }

    function get_block_footer() {
        global $CFG;
        
        if ($this->can_edit_timetables() || $this->can_edit_objectives()) {
            $editlink = $CFG->wwwroot.'/blocks/objectives/edit.php?course='.$this->settings->course;
            return '<a href="'.$editlink.'">'.get_string('editobjectives', 'block_objectives').'</a>';
        }

        return null;
    }

    function edit_objectives() {
        global $CFG;
        
        $caneditobjectives = $this->can_edit_objectives();
        $canedittimetables = $this->can_edit_timetables();
        $returl = $CFG->wwwroot.'/course/view.php?id='.$this->course->id;

        if (!$canedittimetables && !$caneditobjectives) {
            error('You do not have permission to change any lesson objective settings');
        }

        if (!$caneditobjectives) {
            $this->edit_timetables();
            return;
        }

        $timetables = get_records('objectives_timetable', 'objectivesid', $this->settings->id);
        if (!$timetables) {
            if ($canedittimetables) {
                $this->edit_timetables();
                return;
            } else {
                //UT
                $this->print_header();
                print_simple_box(get_string('notimetables','block_objectives'));
                print_continue($returl);
                // FIXME - put a simple box & print_string message & continue button
                error('Timetables have not yet been configured for this course and you do not have permission to do so');
                $this->print_footer();
                return;
            }
        }

        $mform = new block_objectives_objectives_form($CFG->wwwroot.'/blocks/objectives/edit.php?viewtab=objectives&course='.$this->course->id);
        
        $this->print_header();
        $this->print_tabs('objectives');
        $mform->display();
        $this->print_footer();
    }

    function edit_timetables() {
        global $CFG;

        $caneditobjectives = $this->can_edit_objectives();
        $canedittimetables = $this->can_edit_timetables();
        $returl = $CFG->wwwroot.'/course/view.php?id='.$this->course->id;

        if (!$canedittimetables) {
            if ($caneditobjectives) {
                $this->edit_objectives();
                return;
            } else {
                error('You do not have permission to change any lesson objective settings');
            }
        }

        $mform = new block_objectives_timetable_form($CFG->wwwroot.'/blocks/objectives/edit.php?viewtab=timetables&course='.$this->course->id);

        $this->print_header();
        $this->print_tabs('timetables');
        $mform->display();
        $this->print_footer();
    }

    function print_tabs($sel) {
        
    }

    function print_header() {
        $navlinks = array(array('name' => get_string('pluginname','block_objectives')));
        $navigation = build_navigation($navlinks);

        print_header_simple(get_string('pluginname', 'block_objectives'), '', $navigation, '', '', true, '', false);
    }

    function print_footer() {
        print_footer($this->course);
    }
}

class block_objectives_timetable_form extends moodleform {
    function definition() {
        $mform = $this->_form;
    }
}

class block_objectives_objectives_form extends moodleform {
    function definition() {
        $mform = $this->_form;
    }
}

?>