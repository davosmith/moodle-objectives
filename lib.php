<?php

// This file is part of the Lesson Objectives plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
            $this->settings->id = insert_record('objectives',$this->settings);
        }

        $this->context = get_context_instance(CONTEXT_COURSE, $course->id);
    }

    function get_settings() { return $this->settings; }

    function can_view_objectives() { return has_capability('block/objectives:viewobjectives', $this->context); }
    function can_edit_objectives() { return has_capability('block/objectives:editobjectives', $this->context); }
    function can_edit_timetables() { return has_capability('block/objectives:edittimetables', $this->context); }
    function can_checkoff_objectives() { return has_capability('block/objectives:checkoffobjectives', $this->context); }

    // Get timestamp for midnight Monday of the week containting $timestamp (or this week, if $timestamp is 0)
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

    function getweekday($timestamp=0) {
        if ($timestamp) {
            $dateinfo = getdate($timestamp);
        } else {
            $dateinfo = getdate();
        }

        $wday = ($dateinfo['wday'] + 6) % 7; // I have Monday as day 0
        return $wday;
    }

    // Seconds since the start of today
    function gettimenow($timestamp=0) {
        if ($timestamp) {
            $dateinfo = getdate($timestamp);
        } else {
            $dateinfo = getdate();
        }

        $timenow = (($dateinfo['hours']*60) + $dateinfo['minutes']) * 60 + $dateinfo['seconds'];
        return $timenow;
    }

    // Select the objectives that match the selected group (or select a new one if no suitable objectives)
    function objectives_for_selected_group($objectives) {
        global $SESSION;

        if (!$objectives) {
            return false;
        }

        if (count($objectives) == 1) {
            return reset($objectives);
        }

        if (!isset($SESSION->objectives_group)) {
            $SESSION->objectives_group = array(); // Create the SESSION array, if it doesn't already exist
        }
        $changegroup = optional_param('objectives_group', -1, PARAM_INT);
        if ($changegroup != -1) {
            foreach ($objectives as $obj) {
                if ($obj->groupid == $changegroup) {
                    $SESSION->objectives_group[$this->course->id] = $changegroup;
                    return $obj;  // Objectives exist for newly selected group => return them
                }
            }
        }
        if (array_key_exists($this->course->id, $SESSION->objectives_group)) {
            $lastgroup = $SESSION->objectives_group[$this->course->id];
            foreach ($objectives as $obj) {
                if ($obj->groupid == $lastgroup) {
                    return $obj;  // Objectives exist for last selected group => return them
                }
            }
        }
        // No lastgroup, or no objectives for that group - select most suitable objectives
        foreach ($objectives as $obj) {
            if ($obj->groupid != 0) {
                $SESSION->objectives_group[$this->course->id] = $obj->groupid;
                return $obj;
            }
        }

        return reset($objectives);  // Should not reach here, but just in case...
    }

    function groups_menu($objectives, $groups) {
        global $CFG;

        if (count($objectives) < 2) {
            return '';
        }

        $selected = $this->selected_group($objectives);

        $groupsmenu = array();
        foreach ($objectives as $obj) {
            $groupsmenu[$obj->groupid] = $groups[$obj->groupid]->name;
        }

        return get_string('view').': '.popup_form($CFG->wwwroot.'/course/view.php?id='.$this->course->id.'&amp;objectives_group=', $groupsmenu, 'selectobjgroup', $selected->groupid, '', '', '', true);
    }

    function get_block_text() {
        global $USER, $CFG;

        if (!$this->can_view_objectives()) {
            return null;
        }

        $cancheckoff = $this->can_checkoff_objectives();

        $weekstart = $this->getweekstart();
        $day = $this->getweekday();
        $timenow = $this->gettimenow();

        $allgroups = has_capability('moodle/site:accessallgroups', $this->context);

        $userid = $USER->id;
        if ($allgroups) {
            $userid = 0;
        }
        $groups = groups_get_all_groups($this->course->id, $userid, 0, 'g.id, g.name');
        if (!$groups) {
            $groups = array();
        }
        $allgroups = new stdClass;
        $allgroups->id = 0;
        $allgroups->name = get_string('allgroups');
        $groups[0] = $allgroups;

        $sql = 'SELECT o.id, o.objectives, t.starttime, t.endtime, t.groupid ';
        $sql .= "FROM {$CFG->prefix}objectives_objectives o, {$CFG->prefix}objectives_timetable t ";
        $sql .= 'WHERE o.timetableid = t.id AND o.weekstart = '.$weekstart;
        $sql .= ' AND t.objectivesid = '.$this->settings->id.' AND t.day = '.$day.' AND t.starttime <= '.$timenow.' AND t.endtime > '.$timenow;
        $sql .= ' AND t.groupid IN ('.implode(',',array_keys($groups)).')';
        $objectives = get_records_sql($sql);

        $text = '<strong>'.userdate(time(), get_string('strftimedaydate')).'</strong>';

        if (!$objectives) {
            $text .= '<br/>'.get_string('noobjectives','block_objectives');
        } else {
            require_js(array('yui_yahoo','yui_dom-event','yui_container','yui_animation',$CFG->wwwroot.'/blocks/objectives/objectives.js'));

            $groupsmenu = '';
            if (count($objectives) > 1) {
                // More than one eligible lesson with objectives - select the best one and display a menu to choose further
                $objsel = $this->objectives_for_selected_group($objectives);
                $groupsmenu = $this->groups_menu($objectives, $groups);
            } else {
                // Only one eligiblw lesson with objectives - select it
                $objsel = reset($objectives);
            }
            $objarray = explode("\n", $objsel->objectives);
            $icons = array('+'=>'<img src="'.$CFG->wwwroot.'/blocks/objectives/pix/tick_box.gif" alt="'.get_string('complete','block_objectives').'" />',
                           '-'=>'<img src="'.$CFG->wwwroot.'/blocks/objectives/pix/empty_box.gif" alt="'.get_string('incomplete','block_objectives').'" />');

            if ($cancheckoff) {
                $link = array('+'=>'<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'&amp;incomplete_objective='.$objsel->id.':%d" >',
                              '-'=>'<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'&amp;complete_objective='.$objsel->id.':%d" >');
            }

            if ($cancheckoff) {
                $incompleteobj = optional_param('incomplete_objective',false,PARAM_TEXT);
                $completeobj = optional_param('complete_objective',false,PARAM_TEXT);
                if ($incompleteobj) {
                    $toupdate = explode(':',$incompleteobj);
                    if ($toupdate[0] == $objsel->id) {
                        if (array_key_exists($toupdate[1], $objarray)) {
                            $objarray[$toupdate[1]] = '-'.substr($objarray[$toupdate[1]], 1);
                            $upd = new stdClass;
                            $upd->id = $objsel->id;
                            $upd->objectives = implode("\n",$objarray);
                            update_record('objectives_objectives',$upd);
                        }
                    }
                } elseif ($completeobj) {
                    $toupdate = explode(':',$completeobj);
                    if ($toupdate[0] == $objsel->id) {
                        if (array_key_exists($toupdate[1], $objarray)) {
                            $objarray[$toupdate[1]] = '+'.substr($objarray[$toupdate[1]], 1);
                            $upd = new stdClass;
                            $upd->id = $objsel->id;
                            $upd->objectives = implode("\n",$objarray);
                            update_record('objectives_objectives',$upd);
                        }
                    }
                }
            }

            $text .= '<span id="lesson_objectives_fullscreen_icon" style="float:right;"></span>';
            $text .= '<br/>';
            $objtext = '<strong>'.userdate($objsel->starttime, get_string('strftimetime')).'-';
            $objtext .= userdate($objsel->endtime, get_string('strftimetime')).'</strong><br/>';
            $objtext .= s($this->settings->intro);
            $objtext .= '<ul class="lesson_objectives_list">';
            $idx = 0;
            foreach ($objarray as $obj) {
                $complete = substr($obj, 0, 1);
                $obj = substr($obj,1);
                if (trim($obj) == '') {
                    continue;
                }
                if ($complete != '+') {
                    $complete = '-';
                }
                $indent = 0;
                while ($indent < 2 && substr($obj, $indent, 1) == ' ') {
                    $indent++;
                    $objtext .= '<ul>';
                }
                $objtext .= '<li>';
                if ($cancheckoff) { // Add a 'check-off' link
                    $objtext .= sprintf($link[$complete], $idx);
                }
                $objtext .= $icons[$complete].s(trim($obj));
                if ($cancheckoff) {
                    $objtext .= '</a>';
                }
                $objtext .= '</li>';
                for ($i=0; $i<$indent; $i++) {
                    $objtext .= '</ul>';
                }
                $idx++;
            }
            $objtext .= '</ul>';
            $text .= $objtext;

            $fshtml = '<div id="lesson_objectives_fullscreen_text" style="display:none;"><div class="lesson_objectives_fullscreen_area">';
            $fshtml .= preg_replace('/(href="[^"]*)"/i','$1&lesson_objectives_fullscreen=1"', $objtext);
            $fshtml .= '</div></div>';

            $text .= $fshtml;
            $text .= $groupsmenu;

            $startfull = optional_param('lesson_objectives_fullscreen',0,PARAM_INT);
            $fsicon = $CFG->wwwroot.'/blocks/objectives/pix/fullscreen_maximize.gif';
            $text .= '<script type="text/javascript">lesson_objectives.init_fullscreen("'.$fsicon.'","'.get_string('fullscreen','block_objectives').'","'.$startfull.'");</script>';
        }

        return $text;
    }

    function get_block_footer() {
        global $CFG;

        $edittext = '';
        if ($this->can_edit_timetables() || $this->can_edit_objectives()) {
            $editlink = $CFG->wwwroot.'/blocks/objectives/edit.php?course='.$this->settings->course;
            $edittext =  '<a href="'.$editlink.'">'.get_string('editobjectives', 'block_objectives').' &hellip;</a><br/>';
        }
        $viewlink = $CFG->wwwroot.'/blocks/objectives/view.php?course='.$this->settings->course;
        return $edittext.'<a href="'.$viewlink.'">'.get_string('viewobjectives', 'block_objectives').' &hellip;</a>';

        return null;
    }

    function view_objectives($weekstart = 0) {
        global $CFG, $USER;

        if (!$this->can_view_objectives()) {
            redirect($CFG->wwwroot.'/course/view.php/id='.$this->course->id);
        }

        $weekstart = $this->getweekstart($weekstart);
        $prevweek = $weekstart - (6 * 24 * 60 * 60); // Put it inside the previous week, to avoid summer time / timezone errors
        $nextweek = $weekstart + (8 * 24 * 60 * 60);

        $thisurl = $CFG->wwwroot.'/blocks/objectives/view.php?viewtab=objectives&course='.$this->course->id;
        $nextlink = $thisurl.'&weekstart='.$nextweek;
        $prevlink = $thisurl.'&weekstart='.$prevweek;
        $thisurl .= '&weekstart='.$weekstart;

        // Load all the objectives for the selected week
        $allgroups = has_capability('moodle/site:accessallgroups', $this->context);

        $userid = $USER->id;
        if ($allgroups) {
            $userid = 0;
        }
        $groups = groups_get_all_groups($this->course->id, $userid, 0, 'g.id, g.name');
        if (!$groups) {
            $groups = array();
        }
        $allgroups = new stdClass;
        $allgroups->id = 0;
        $allgroups->name = get_string('allgroups');
        $groups[0] = $allgroups;

        $timetables = get_records_select('objectives_timetable', "objectivesid = {$this->settings->id} AND groupid IN (".implode(',',array_keys($groups)).')', 'day, starttime, groupid');
        $objectives = get_records_select('objectives_objectives', 'timetableid IN ('.implode(',',array_keys($timetables)).') AND weekstart = '.$weekstart);

        if ($objectives) {
            foreach ($objectives as $obj) {
                $timetables[$obj->timetableid]->objectives = $obj->objectives;
            }
        }

        $num2day = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
        $icons = array('+'=>'<img src="'.$CFG->wwwroot.'/blocks/objectives/pix/tick_box.gif" alt="'.get_string('complete','block_objectives').'" />',
                       '-'=>'<img src="'.$CFG->wwwroot.'/blocks/objectives/pix/empty_box.gif" alt="'.get_string('incomplete','block_objectives').'" />');

        $this->print_header();
        print_heading(get_string('viewobjectives','block_objectives'));

        // Output the week navigation options
        print_simple_box_start();
        echo '<a href="'.$prevlink.'">&lt;&lt;&lt; '.get_string('prevweek','block_objectives').'</a> ';
        echo get_string('weekbegining','block_objectives').' <strong>'.userdate($weekstart, get_string('strftimedaydate')).'</strong>';
        echo ' <a href="'.$nextlink.'">'.get_string('nextweek','block_objectives').' &gt;&gt;&gt;</a>';
        print_simple_box_end();

        print_simple_box_start();
        echo '<table class="lesson_objectives_table">';
        $lastday = -1;
        $oddrow = true;
        foreach ($timetables as $lesson) {
            echo '<tr class="'.($oddrow ? 'oddrow' : 'evenrow').'" >';
            if ($lastday != $lesson->day) {
                echo '<td><strong>'.get_string($num2day[$lesson->day],'calendar').'</strong></td>';
                $lastday = $lesson->day;
            } else {
                echo '<td>&nbsp;</td>';
            }
            echo '<td>';
            echo userdate($lesson->starttime, get_string('strftimetime')).'-';
            echo userdate($lesson->endtime, get_string('strftimetime'));
            if ($lesson->groupid > 0) {
                echo ' ('.$groups[$lesson->groupid]->name.')';
            }
            echo '</td><td>';
            if (isset($lesson->objectives)) {
                $objarray = explode("\n", $lesson->objectives);
                $objtext = '<ul class="lesson_objectives_list">';
                foreach ($objarray as $obj) {
                    $complete = substr($obj, 0, 1);
                    $obj = substr($obj,1);
                    if (trim($obj) == '') {
                        continue;
                    }
                    if ($complete != '+') {
                        $complete = '-';
                    }
                    $indent = 0;
                    while ($indent < 2 && substr($obj, $indent, 1) == ' ') {
                        $indent++;
                        $objtext .= '<ul>';
                    }
                    $objtext .= '<li>';
                    $objtext .= $icons[$complete].s(trim($obj));
                    $objtext .= '</li>';
                    for ($i=0; $i<$indent; $i++) {
                        $objtext .= '</ul>';
                    }
                }
                $objtext .= '</ul>';

                echo $objtext;
            } else {
                echo '&nbsp;';
            }
            echo '</td></tr>';
            $oddrow = !$oddrow;
        }
        echo '</table>';
        print_simple_box_end();

        $this->print_footer();
    }

    function remove_checkedoff($obj) {
        return preg_replace(array('/^\+/m','/^-/m'),'',$obj);
    }

    function add_not_checkedoff($obj) {
        return preg_replace('/^(.)/m','-$1',$obj); // Start each line with '-' (incomplete)
    }

    function edit_objectives($weekstart = 0) {
        global $CFG;

        $caneditobjectives = $this->can_edit_objectives();
        $canedittimetables = $this->can_edit_timetables();
        $courseurl = $CFG->wwwroot.'/course/view.php?id='.$this->course->id;

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
        $prevweek = $weekstart - (6 * 24 * 60 * 60); // Put it within last week, to avoid timezone / summer time errors
        $nextweek = $weekstart + (8 * 24 * 60 * 60);

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
                // Remove the 'completed' symbols from the start of each line
                $formdata["obj[{$obj->timetableid}]"] = $this->remove_checkedoff($obj->objectives);
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
                            } elseif ($this->remove_checkedoff($dbobj->objectives) != $obj) {
                                $upd = new stdClass;
                                $upd->id = $dbobj->id;
                                $upd->objectives = $this->add_not_checkedoff($obj);
                                update_record('objectives_objectives',$upd);
                            }
                        }
                    }
                }
                if ($addnew && trim($obj) != '') {
                    $new = new stdClass;
                    $new->timetableid = $timetableid;
                    $new->weekstart = $weekstart;
                    $new->objectives = $this->add_not_checkedoff($obj);
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
                            if ($upd->day == $timetables[$lid]->day && $upd->objectivesid == $timetables[$lid]->objectivesid) {
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
        if ($groups) {
            foreach ($groups as $id=>$group) {
                $groupnames[$id] = $group->name;
            }
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