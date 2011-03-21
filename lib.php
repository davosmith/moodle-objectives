<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
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

    // Get timestamp for midnight Monday of the week containting $weekstart
    function getweekstart($timestamp=0) {
        if ($timestamp) {
            $dateinfo = getdate($timestamp);
        } else {
            $dateinfo = getdate();
        }

        $wday = ($dateinfo['wday'] + 6) % 7; // I have Monday as day 0
        
        // Work out midnight today
        $weekstart = mktime(0,0,0,$dateinfo['mon'],$dateinfo['mday'],$dateinfo['year']);
        $weekstart -= (24 * 60 * 60) * $wday; // Subtract number of days to get back to Monday

        return $weekstart;
    }

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

    function edit_objectives($weekstart = 0) {
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

        // TODO limit to only show objectives for selected group
        $timetables = get_records('objectives_timetable', 'objectivesid', $this->settings->id, 'day, starttime, groupid');
        if (!$timetables) {
            if ($canedittimetables) {
                $this->edit_timetables();
                return;
            } else {
                //UT
                $this->print_header();
                print_simple_box(get_string('notimetables','block_objectives'));
                print_continue($courseurl);
                $this->print_footer();
                return;
            }
        }

        $weekstart = $this->getweekstart($weekstart);
        $prevweek = $weekstart - (7 * 24 * 60 * 60);
        $nextweek = $weekstart + (7 * 24 * 60 * 60);

        $thisurl = $CFG->wwwroot.'/blocks/objectives/edit.php?viewtab=objectives&course='.$this->course->id;
        $nextlink = $thisurl.'&weekstart='.$nextweek;
        $prevlink = $thisurl.'&weekstart='.$prevweek;
        $thisurl .= '&weekstart='.$weekstart;
        
        $mform = new block_objectives_objectives_form($thisurl, array('timetables'=>$timetables, 'course'=>$this->course));
        
        // Load all the objectives for the selected week
        $objectives = get_records_select('objectives_objectives', 'timetableid IN ('.implode(',',array_keys($timetables)).') AND weekstart = '.$weekstart);
        $formdata = array();
        $formdata['weekstart'] = $weekstart;
        if ($objectives) {
            foreach ($objectives as $obj) {
                // TODO - will need to remove the 'completed' symbols from the start of each line
                $formdata["obj[{$obj->timetableid}]"] = $obj->objectives;
            }
        }
        
        $mform->set_data($formdata);

        if ($mform->is_cancelled()) {
            redirect($courseurl);
        }

        if (($data = $mform->get_data()) && ($data->action == 'savesettings')) {
            foreach ($data->obj as $timetableid=>$obj) {
                $addnew = true;
                if ($objectives) {
                    foreach ($objectives as $dbobj) {
                        if ($dbobj->timetableid == $timetableid) {
                            $addnew = false;
                            if (trim($obj) == '') {
                                delete_records('objectives_objectives','id',$dbobj->id);
                            } elseif ($dbobj->objectives != $obj) {
                                $upd = new stdClass;
                                $upd->id = $dbobj->id;
                                $upd->objectives = $obj; // TODO add '-' to start of each line
                                update_record('objectives_objectives',$upd);
                            }
                        }
                    }
                }
                if ($addnew && trim($obj) != '') {
                    $new = new stdClass;
                    $new->timetableid = $timetableid;
                    $new->weekstart = $weekstart;
                    $new->objectives = $obj; // TODO add '-' to start of each line
                    $new->id = insert_record('objectives_objectives',$new);
                }
            }
            
            if (isset($data->saveandcourse)) {
                redirect($courseurl);
            }
        }
        
        $this->print_header();
        print_heading(get_string('editobjectives','block_objectives'));
        print_simple_box_start();
        if ($canedittimetables) {
            $timetablesurl = $CFG->wwwroot.'/blocks/objectives/edit.php';
            print_single_button($timetablesurl, array('viewtab'=>'timetables', 'course'=>$this->course->id), get_string('edittimetables','block_objectives'));
        }

        // Output the week navigation options
        echo '<a href="'.$prevlink.'">&lt;&lt;&lt; '.get_string('prevweek','block_objectives').'</a> ';
        echo get_string('weekbegining','block_objectives').' <strong>'.userdate($weekstart, get_string('strftimedaydate')).'</strong>';
        echo ' <a href="'.$nextlink.'">'.get_string('nextweek','block_objectives').' &gt;&gt;&gt;</a>';
        print_simple_box_end();

        print_string('editobjectivesinst','block_objectives');
        
        $mform->display();
        
        $this->print_footer();
    }

    function edit_timetables() {
        global $CFG;

        $caneditobjectives = $this->can_edit_objectives();
        $canedittimetables = $this->can_edit_timetables();
        $courseurl = $CFG->wwwroot.'/course/view.php?id='.$this->course->id;

        if (!$canedittimetables) {
            if ($caneditobjectives) {
                $this->edit_objectives();
                return;
            } else {
                error('You do not have permission to change any lesson objective settings');
            }
        }

        $timetables = get_records('objectives_timetable', 'objectivesid', $this->settings->id, 'day, starttime, groupid');
        $days = array('monday'=>array(), 'tuesday'=>array(), 'wednesday'=>array(),
                         'thursday'=>array(), 'friday'=>array(), 'saturday'=>array(),
                         'sunday'=>array());
        $num2day = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
        $settings = array();
        $settings['id'] = $this->settings->id;
        $settings['course'] = $this->course->id;
        
        if ($timetables) {
            $weekday = 0;
            reset($days);
            foreach ($timetables as $lesson) {
                $days[$num2day[$lesson->day]][] = $lesson->id; // Store the id to use when creating the form
                // Store the settings for this entry
                $settings["lgroup[{$lesson->id}]"] = $lesson->groupid;
                $settings["lstarthour[{$lesson->id}]"] = (int)($lesson->starttime / (60 * 60));
                $settings["lstartminute[{$lesson->id}]"] = (int)(($lesson->starttime / 60) % 60);
                $settings["lendhour[{$lesson->id}]"] = (int)($lesson->endtime / (60 * 60));
                $settings["lendminute[{$lesson->id}]"] = (int)(($lesson->endtime / 60) % 60);
            }
        }
        $lastnew = 0;
        foreach ($days as $key=>$day) {
            for ($i=0; $i<3; $i++) {
                $lastnew--;
                $days[$key][] = $lastnew; // The blank entries have distinct, negative ids
            }
        }
        
        $thisurl = $CFG->wwwroot.'/blocks/objectives/edit.php?viewtab=timetables&course='.$this->course->id;
        $objurl = str_replace('viewtab=timetables','viewtab=objectives', $thisurl);
        $mform = new block_objectives_timetable_form($thisurl, array('course' => $this->course, 'days' => $days));

        $mform->set_data($settings);

        if ($mform->is_cancelled()) {
            if ($timetables) {
                redirect($objurl);
            } else {
                // Going back to objectives edit screen, with no timetables, would loop back here
                redirect($courseurl);
            }
        }
        
        if (($data = $mform->get_data()) && ($data->action == 'savesettings')) {
            foreach ($data->lgroup as $lid=>$lgroup) {
                if ($lid < 0 || !isset($timetables[$lid])) { // New entry
                    if ($lgroup >= 0) { // Not disabled
                        $new = new stdClass;
                        $new->objectivesid = $this->settings->id;
                        $new->groupid = $lgroup;
                        $new->day = $data->lday[$lid];
                        $new->starttime = ($data->lstarthour[$lid] * 60 * 60) + ($data->lstartminute[$lid] * 60);
                        $new->endtime = ($data->lendhour[$lid] * 60 * 60) + ($data->lendminute[$lid] * 60);
                        $new->id = insert_record('objectives_timetable', $new);
                    }
                } else { // Existing entry
                    if ($lgroup < 0) { // Entry disabled
                        delete_records('objectives_timetable','id',$lid,'objectivesid',$this->settings->id); // Added 'objectivesid' check, just to be on the safe side
                    } else { // Update entry (if changed)
                        $upd = new stdClass;
                        $upd->id = $lid;
                        $upd->objectivesid = $this->settings->id;
                        $upd->groupid = $lgroup;
                        $upd->day = $data->lday[$lid];
                        $upd->starttime = ($data->lstarthour[$lid] * 60 * 60) + ($data->lstartminute[$lid] * 60);
                        $upd->endtime = ($data->lendhour[$lid] * 60 * 60) + ($data->lendminute[$lid] * 60);

                        if ($upd->groupid != $timetables[$lid]->groupid ||
                            $upd->starttime != $timetables[$lid]->starttime ||
                            $upd->endtime != $timetables[$lid]->endtime) {  // Something has changed
                            if ($upd->day == $timetables[$lid] && $upd->objectivesid == $timetables[$lid]) {
                                update_record('objectives_timetable',$upd);
                            } else {
                                $this->print_header();
                                error('Attempting to update record that does not match database');
                            }
                        }
                    }
                }
            }
            
            if (isset($data->saveandobjectives)) {
                redirect($objurl);
            } else {
                redirect($thisurl);
            }
        }

        $this->print_header();
        print_heading(get_string('edittimetables','block_objectives'));
        print_simple_box(get_string('edittimetablesinst','block_objectives'));
        $mform->display();
        $this->print_footer();
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
        $custom = $this->_customdata;
        $course = $custom['course'];
        $days = $custom['days'];

        $groups = groups_get_all_groups($course->id, 0, 0, 'g.id, g.name');
        $groupnames = array();
        $groupnames[-1] = get_string('disable');
        $groupnames[0] = get_string('allgroups');
        foreach ($groups as $id=>$group) {
            $groupnames[$id] = $group->name;
        }
        $hours = array();
        for ($i=0; $i<24; $i++) {
            $hours[$i] = sprintf('%02d',$i);
        }
        $minutes = array();
        for ($i=0; $i<60; $i+=5) {
            $minutes[$i] = sprintf('%02d',$i);
        }

        $weekday = 0;
        foreach ($days as $day=>$lessons) {
            $mform->addElement('header', $day, get_string($day,'calendar'));
            foreach ($lessons as $lid) {
                $lel = array();
                $lel[] =& $mform->createElement('select', "lgroup[$lid]", get_string('group','block_objectives'), $groupnames);
                $mform->setDefault("lgroup[$lid]", -1);
                $lel[] =& $mform->createElement('static', null, '', '&nbsp;&nbsp;'.get_string('lessonstart', 'block_objectives'));
                $lel[] =& $mform->createElement('select', "lstarthour[$lid]", get_string('lessonstarthour', 'block_objectives'), $hours);
                $mform->setDefault("lstarthour[$lid]",8);
                $mform->disabledIf("lstarthour[$lid]","lgroup[$lid]",'eq',-1);
                $lel[] =& $mform->createElement('select', "lstartminute[$lid]", get_string('lessonstartminute', 'block_objectives'), $minutes);
                $mform->disabledIf("lstartminute[$lid]","lgroup[$lid]",'eq',-1);

                $lel[] =& $mform->createElement('static', null, '', '&nbsp;&nbsp;'.get_string('lessonend', 'block_objectives'));
                $lel[] =& $mform->createElement('select', "lendhour[$lid]", get_string('lessonendhour', 'block_objectives'), $hours);
                $mform->setDefault("lendhour[$lid]",8);
                $mform->disabledIf("lendhour[$lid]","lgroup[$lid]",'eq',-1);
                $lel[] =& $mform->createElement('select', "lendminute[$lid]", get_string('lessonstartminute', 'block_objectives'), $minutes);
                $mform->disabledIf("lendminute[$lid]","lgroup[$lid]",'eq',-1);

                $lel[] =& $mform->createElement('hidden',"lday[$lid]",$weekday);

                $mform->addGroup($lel, 'lesson'.$lid.'group', get_string('lesson','block_objectives'), array(''), false);
            }
            $weekday++;
        }

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'course', $course->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'action', 'savesettings');
        $mform->setType('action', PARAM_TEXT);

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttons[] =& $mform->createElement('submit', 'saveandobjectives', get_string('saveandobjectives','block_objectives'));
        $buttons[] =& $mform->createElement('cancel');
        $mform->addGroup($buttons, 'actionbuttons', '', array(' '), false);
        $mform->closeHeaderBefore('actionbuttons');
    }
}

class block_objectives_objectives_form extends moodleform {
    function definition() {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $timetables = $custom['timetables'];
        $course = $custom['course'];
        $groups = groups_get_all_groups($course->id, 0, 0, 'g.id, g.name');
        $num2day = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');

        $lastday = -1;
        foreach ($timetables as $lesson) {
            if ($lastday != $lesson->day) {
                $day = $num2day[$lesson->day];
                $mform->addElement('header', $day, get_string($day,'calendar'));
                $lastday = $lesson->day;
            }
            $objlabel = userdate($lesson->starttime, get_string('strftimetime')).'-';
            $objlabel .= userdate($lesson->endtime, get_string('strftimetime'));
            if ($lesson->groupid > 0) {
                $objlabel .= ' ('.$groups[$lesson->groupid]->name.')';
            }
            $mform->addElement('textarea',"obj[{$lesson->id}]", $objlabel, array('cols'=>40,'rows'=>5));
        }

        $mform->addElement('hidden', 'course', $course->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'weekstart', 0);
        $mform->setType('weekstart', PARAM_INT);

        $mform->addElement('hidden', 'action', 'savesettings');
        $mform->setType('action', PARAM_TEXT);

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttons[] =& $mform->createElement('submit', 'saveandcourse', get_string('saveandcourse','block_objectives'));
        $buttons[] =& $mform->createElement('cancel');
        $mform->addGroup($buttons, 'actionbuttons', '', array(' '), false);
        $mform->closeHeaderBefore('actionbuttons');
    }
}

?>