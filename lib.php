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
        global $DB, $CFG;

        if (is_int($course)) {
            $course = $DB->get_record('course',array('id'=>$course));
            if (!$course) {
                error('Invalid course id');
            }
        }

        $this->course = $course;

        $this->settings = $DB->get_record('block_objectives', array('course' => $course->id));
        if (!$this->settings) {
            $this->settings = new stdClass;
            $this->settings->course = $course->id;
            $this->settings->intro = get_string('defaultintro','block_objectives');
            $this->settings->id = $DB->insert_record('block_objectives', $this->settings);
        }

        if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_COURSE, $course->id);
        } else {
            $this->context = context_course::instance($course->id);
        }
    }

    function get_settings() { return $this->settings; }

    function can_view_objectives() { return has_capability('block/objectives:viewobjectives', $this->context); }
    function can_edit_objectives() { return has_capability('block/objectives:editobjectives', $this->context); }
    function can_edit_timetables() { return has_capability('block/objectives:edittimetables', $this->context); }
    function can_checkoff_objectives() { return has_capability('block/objectives:checkoffobjectives', $this->context); }

    // Convert weekstart into a timestamp (noon on that day)
    function ws2ts($weekstart) {
        return mktime(12, 0, 0, intval(substr($weekstart, 4, 2)), intval(substr($weekstart, 6, 2)), intval(substr($weekstart, 0, 4)));
    }

    // Convert a timestamp into 'YYYYMMDD' for that day
    function ts2ws($timestamp) {
        return date('Ymd', $timestamp);
    }

    // Get string representing start of week in YYYYMMDD format
    function getweekstart($weekstart=null) {
        if ($weekstart and strlen($weekstart) == 8) {
            $ts = $this->ws2ts($weekstart);
            $dateinfo = getdate($ts);
            if ($dateinfo['wday'] == 1) { // Passed in string was for a Monday
                return $weekstart;
            }
        } else {
            $dateinfo = getdate();
        }

        $wday = ($dateinfo['wday'] + 6) % 7; // I have Monday as day 0

        // Work out noon today
        $weekstartts = mktime(12, 0, 0, $dateinfo['mon'], $dateinfo['mday'], $dateinfo['year']);
        $weekstartts -= (24 * 60 * 60) * $wday; // Subtract number of days to get back to Monday
        return $this->ts2ws($weekstartts); // Convert to string YYYYMMDD
    }

    function addweek($weekstart, $offset) {
        // Work out timestamp for noon on specified day
        $ts = $this->ws2ts($weekstart);
        $ts += 7 * 24 * 60 * 60 * $offset; // Add on number of weeeks requested
        return $this->ts2ws($ts); // Convert to string YYYYMMDD
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

        $timenow = mktime((int)$dateinfo['hours'], (int)$dateinfo['minutes'], (int)$dateinfo['seconds'], 0, 0, 0);
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
        global $OUTPUT;

        if (count($objectives) < 2) {
            return '';
        }

        $selected = $this->objectives_for_selected_group($objectives);

        $groupsmenu = array();
        foreach ($objectives as $obj) {
            $groupsmenu[$obj->groupid] = $groups[$obj->groupid]->name;
        }

        $baseurl = new moodle_url('/course/view.php',array('id'=>$this->course->id));

        return get_string('view').': <span class="lesson_objectives_groupsmenu">'.$OUTPUT->single_select($baseurl, 'objectives_group', $groupsmenu, $selected->groupid).'</span>';
    }

    function get_block_text() {
        global $USER, $DB, $OUTPUT, $PAGE, $CFG;

        if (!$this->can_view_objectives()) {
            return null;
        }

        $courseurl = new moodle_url('/course/view.php',array('id'=>$this->course->id));

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

        list($gsql, $gparam) = $DB->get_in_or_equal(array_keys($groups));
        $sql = 'SELECT o.id, o.objectives, t.starttime, t.endtime, t.groupid ';
        $sql .= "FROM {block_objectives_objectives} o, {block_objectives_timetable} t ";
        $sql .= 'WHERE o.timetableid = t.id AND o.weekstart = ?';
        $sql .= ' AND t.objectivesid = ? AND t.day = ? AND t.starttime <= ? AND t.endtime > ?';
        $sql .= ' AND t.groupid '.$gsql;
        $params = array_merge(array($weekstart, $this->settings->id, $day, $timenow, $timenow), $gparam);
        $objectives = $DB->get_records_sql($sql, $params);

        $text = '<strong>'.userdate(time(), get_string('strftimedaydate')).'</strong>';

        if (!$objectives) {
            $text .= '<br/>';
            $text .= get_string('noobjectives','block_objectives');
            $text .= ' ('.userdate($timenow, get_string('strftimetime')).')';
        } else {
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
            $icons = array('+'=>'<img src="'.$OUTPUT->pix_url('tick_box','block_objectives').'" alt="'.get_string('complete','block_objectives').'" />',
                           '-'=>'<img src="'.$OUTPUT->pix_url('empty_box','block_objectives').'" alt="'.get_string('incomplete','block_objectives').'" />');

            if ($cancheckoff) {
                $link = array('+'=>'<a href="'.$courseurl->out(true, array('incomplete_objective'=>$objsel->id)).':%d" >',
                              '-'=>'<a href="'.$courseurl->out(true, array('complete_objective'=>$objsel->id)).':%d" >');
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
                            $DB->update_record('block_objectives_objectives',$upd);

                            $this->log_update($objsel->id, false);
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
                            $DB->update_record('block_objectives_objectives',$upd);

                            $this->log_update($objsel->id, true);
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
                    $objtext .= sprintf($link[$complete],$idx);
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
            $fshtml .= preg_replace('/(href="[^"]*)"/i','$1&amp;lesson_objectives_fullscreen=1"', $objtext);
            $fshtml .= '</div></div>';

            $text .= $fshtml;
            $text .= $groupsmenu;

            $startfull = optional_param('lesson_objectives_fullscreen',0,PARAM_INT);
            $fsicon = $OUTPUT->pix_url('fullscreen_maximize','block_objectives');

            if ($CFG->version < 2012120300) { // < Moodle 2.4
                $jsmodule = array('name' => 'block_objectives',
                                  'fullpath' => new moodle_url('/blocks/objectives/objectives.js'),
                                  'requires' => array('yui2-yahoo','yui2-dom','yui2-container','yui2-animation'));
            } else { // Moodle 2.4
                $jsmodule = array('name' => 'block_objectives',
                                  'fullpath' => new moodle_url('/blocks/objectives/objectives24.js'),
                                  'requires' => array());
            }
            $params = array($fsicon->out(), get_string('fullscreen','block_objectives'), $startfull);
            $PAGE->requires->js_init_call('M.block_objectives.init_fullscreen', $params, true, $jsmodule);
        }

        return $text;
    }

    protected function log_update($objectivesid, $completed) {
        global $CFG;
        if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $objectivesid,
                'other' => array('completed' => $completed),
            );
            $event = \block_objectives\event\objective_updated::create($params);
            $event->trigger();
        }
    }

    function get_block_footer() {
        $edittext = '';
        if ($this->can_edit_timetables() || $this->can_edit_objectives()) {
            $editlink = new moodle_url('/blocks/objectives/edit.php',array('course'=>$this->settings->course));
            $edittext =  '<a href="'.$editlink.'">'.get_string('editobjectives', 'block_objectives').' &hellip;</a><br/>';
        }
        $viewlink = new moodle_url('/blocks/objectives/view.php',array('course'=>$this->settings->course));
        return $edittext.'<a href="'.$viewlink.'">'.get_string('viewobjectives', 'block_objectives').' &hellip;</a>';
    }

    function view_objectives($weekstart = null) {
        global $USER, $DB, $OUTPUT;

        if (!$this->can_view_objectives()) {
            redirect(new moodle_url('/course/view.php',array('id'=>$this->course->id)));
        }

        $weekstart = $this->getweekstart($weekstart);
        $prevweek = $this->addweek($weekstart, -1);
        $nextweek = $this->addweek($weekstart, 1);

        $thisurl = new moodle_url('/blocks/objectives/view.php',
                                  array('course'=>$this->course->id, 'weekstart'=>$weekstart));
        $nextlink = new moodle_url($thisurl, array('weekstart'=>$nextweek));
        $prevlink = new moodle_url($thisurl, array('weekstart'=>$prevweek));

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

        list($gsql, $gparam) = $DB->get_in_or_equal(array_keys($groups));
        $params = array_merge(array($this->settings->id),$gparam);
        $timetables = $DB->get_records_select('block_objectives_timetable',
                                              'objectivesid = ? AND groupid '.$gsql,
                                              $params, 'day, starttime, groupid');

        if (empty($timetables)) {
            $objectives = array();
        } else {
            list($tsql, $tparam) = $DB->get_in_or_equal(array_keys($timetables));
            $params = array_merge(array($weekstart),$tparam);
            $objectives = $DB->get_records_select('block_objectives_objectives',
                                                  'weekstart = ? AND timetableid '.$tsql,
                                                  $params);
        }

        foreach ($objectives as $obj) {
            $timetables[$obj->timetableid]->objectives = $obj->objectives;
        }

        $num2day = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
        $icons = array('+'=>'<img src="'.$OUTPUT->pix_url('tick_box','block_objectives').'" alt="'.get_string('complete','block_objectives').'" />',
                       '-'=>'<img src="'.$OUTPUT->pix_url('empty_box','block_objectives').'" alt="'.get_string('incomplete','block_objectives').'" />');
        $this->print_header();
        echo $OUTPUT->heading(get_string('viewobjectives','block_objectives'));

        // Output the week navigation options
        echo $OUTPUT->box_start();
        echo '<a href="'.$prevlink.'">&lt;&lt;&lt; '.get_string('prevweek','block_objectives').'</a> ';
        echo get_string('weekbegining','block_objectives').' <strong>'.userdate($this->ws2ts($weekstart), get_string('strftimedaydate')).'</strong>';
        echo ' <a href="'.$nextlink.'">'.get_string('nextweek','block_objectives').' &gt;&gt;&gt;</a>';
        echo $OUTPUT->box_end();

        echo $OUTPUT->box_start();
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
        echo $OUTPUT->box_end();

        $this->print_footer();
    }

    function remove_checkedoff($obj) {
        return preg_replace(array('/^\+/m','/^-/m'),'',$obj);
    }

    function add_not_checkedoff($obj) {
        return preg_replace('/^(.)/m','-$1',$obj); // Start each line with '-' (incomplete)
    }

    function edit_objectives($weekstart = null) {
        global $DB, $OUTPUT;
        $caneditobjectives = $this->can_edit_objectives();
        $canedittimetables = $this->can_edit_timetables();
        $courseurl = new moodle_url('/course/view.php',array('id'=>$this->course->id));

        if (!$canedittimetables && !$caneditobjectives) {
            error('You do not have permission to change any lesson objective settings');
        }

        if (!$caneditobjectives) {
            $this->edit_timetables();
            return;
        }

        // TODO limit to only show objectives for selected group
        $timetables = $DB->get_records('block_objectives_timetable', array('objectivesid'=>$this->settings->id), 'day, starttime, groupid');
        if (empty($timetables)) {
            if ($canedittimetables) {
                $this->edit_timetables();
                return;
            } else {
                $this->print_header();
                echo $OUTPUT->box(get_string('notimetables','block_objectives'));
                echo $OUTPUT->continue($courseurl);
                $this->print_footer();
                return;
            }
        }

        $weekstart = $this->getweekstart($weekstart);
        $prevweek = $this->addweek($weekstart, -1);
        $nextweek = $this->addweek($weekstart, 1);

        $thisurl = new moodle_url('/blocks/objectives/edit.php',
                                  array('viewtab'=>'objectives', 'course'=>$this->course->id, 'weekstart'=>$weekstart));
        $nextlink = new moodle_url($thisurl, array('weekstart'=>$nextweek));
        $prevlink = new moodle_url($thisurl, array('weekstart'=>$prevweek));

        $mform = new block_objectives_objectives_form($thisurl, array('timetables'=>$timetables, 'course'=>$this->course));

        // Load all the objectives for the selected week
        list($tsql, $tparam) = $DB->get_in_or_equal(array_keys($timetables));
        $params = array_merge(array($weekstart),$tparam);
        $objectives = $DB->get_records_select('block_objectives_objectives', 'weekstart = ? AND timetableid '.$tsql, $params);

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
                                $DB->delete_records('block_objectives_objectives',array('id'=>$dbobj->id));
                            } elseif ($this->remove_checkedoff($dbobj->objectives) != $obj) {
                                $upd = new stdClass;
                                $upd->id = $dbobj->id;
                                $upd->objectives = $this->add_not_checkedoff($obj);
                                $DB->update_record('block_objectives_objectives',$upd);
                            }
                        }
                    }
                }
                if ($addnew && trim($obj) != '') {
                    $new = new stdClass;
                    $new->timetableid = $timetableid;
                    $new->weekstart = $weekstart;
                    $new->objectives = $this->add_not_checkedoff($obj);
                    $new->id = $DB->insert_record('block_objectives_objectives',$new);
                }
            }

            if (isset($data->saveandcourse)) {
                redirect($courseurl);
            }
        }

        $this->print_header();
        echo $OUTPUT->heading(get_string('editobjectives','block_objectives'));
        echo $OUTPUT->box_start();
        if ($canedittimetables) {
            $timetablesurl = new moodle_url('/blocks/objectives/edit.php',array('viewtab'=>'timetables', 'course'=>$this->course->id));
            echo $OUTPUT->single_button($timetablesurl, get_string('edittimetables','block_objectives'));
        }

        // Output the week navigation options
        echo '<a href="'.$prevlink.'">&lt;&lt;&lt; '.get_string('prevweek','block_objectives').'</a> ';
        echo get_string('weekbegining','block_objectives').
            ' <strong>'.userdate($this->ws2ts($weekstart), get_string('strftimedaydate')).'</strong>';
        echo ' <a href="'.$nextlink.'">'.get_string('nextweek','block_objectives').' &gt;&gt;&gt;</a>';
        echo $OUTPUT->box_end();

        print_string('editobjectivesinst','block_objectives');

        $mform->display();

        $this->print_footer();
    }

    function edit_timetables() {
        global $DB, $OUTPUT;

        $caneditobjectives = $this->can_edit_objectives();
        $canedittimetables = $this->can_edit_timetables();
        $courseurl = new moodle_url('/course/view.php', array('id'=>$this->course->id));

        if (!$canedittimetables) {
            if ($caneditobjectives) {
                $this->edit_objectives();
                return;
            } else {
                error('You do not have permission to change any lesson objective settings');
            }
        }

        $timetables = $DB->get_records('block_objectives_timetable', array('objectivesid'=>$this->settings->id), 'day, starttime, groupid');
        $days = array('monday'=>array(), 'tuesday'=>array(), 'wednesday'=>array(),
                         'thursday'=>array(), 'friday'=>array(), 'saturday'=>array(),
                         'sunday'=>array());
        $num2day = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
        $settings = array();
        $settings['id'] = $this->settings->id;
        $settings['course'] = $this->course->id;

        if ($timetables) {
            reset($days);
            foreach ($timetables as $lesson) {
                $days[$num2day[$lesson->day]][] = $lesson->id; // Store the id to use when creating the form
                // Store the settings for this entry
                $settings["lgroup[{$lesson->id}]"] = $lesson->groupid;
                $settings["lstarthour[{$lesson->id}]"] = intval(userdate($lesson->starttime, '%H'));
                $settings["lstartminute[{$lesson->id}]"] = intval(userdate($lesson->starttime, '%M'));
                $settings["lendhour[{$lesson->id}]"] = intval(userdate($lesson->endtime, '%H'));
                $settings["lendminute[{$lesson->id}]"] = intval(userdate($lesson->endtime, '%M'));
            }
        }
        $lastnew = 0;
        foreach ($days as $key=>$day) {
            for ($i=0; $i<3; $i++) {
                $lastnew--;
                $days[$key][] = $lastnew; // The blank entries have distinct, negative ids
            }
        }

        $thisurl = new moodle_url('/blocks/objectives/edit.php',array('viewtab'=>'timetables', 'course'=>$this->course->id));
        $objurl = new moodle_url($thisurl, array('viewtab'=>'objectives'));
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
                        $new->starttime = make_timestamp(0, 0, 0, $data->lstarthour[$lid], $data->lstartminute[$lid], 0);
                        $new->endtime = make_timestamp(0, 0, 0, $data->lendhour[$lid], $data->lendminute[$lid], 0);
                        $new->id = $DB->insert_record('block_objectives_timetable', $new);
                    }
                } else { // Existing entry
                    if ($lgroup < 0) { // Entry disabled
                        $DB->delete_records('block_objectives_timetable',array('id'=>$lid,'objectivesid'=>$this->settings->id)); // Added 'objectivesid' check, just to be on the safe side
                    } else { // Update entry (if changed)
                        $upd = new stdClass;
                        $upd->id = $lid;
                        $upd->objectivesid = $this->settings->id;
                        $upd->groupid = $lgroup;
                        $upd->day = $data->lday[$lid];
                        $upd->starttime = make_timestamp(0, 0, 0, $data->lstarthour[$lid], $data->lstartminute[$lid], 0);
                        $upd->endtime = make_timestamp(0, 0, 0, $data->lendhour[$lid], $data->lendminute[$lid], 0);

                        if ($upd->groupid != $timetables[$lid]->groupid ||
                            $upd->starttime != $timetables[$lid]->starttime ||
                            $upd->endtime != $timetables[$lid]->endtime) {  // Something has changed
                            if ($upd->day == $timetables[$lid]->day && $upd->objectivesid == $timetables[$lid]->objectivesid) {
                                $DB->update_record('block_objectives_timetable',$upd);
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
        echo $OUTPUT->heading(get_string('edittimetables','block_objectives'));
        echo $OUTPUT->box(get_string('edittimetablesinst','block_objectives'));
        $mform->display();
        $this->print_footer();
    }

    function print_header() {
        global $PAGE, $OUTPUT;

        $pagetitle = strip_tags($this->course->shortname.': '.get_string('pluginname','block_objectives'));

        $PAGE->set_title($pagetitle);
        $PAGE->set_heading($this->course->fullname);

        echo $OUTPUT->header();
    }

    function print_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
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
                $mform->setType("lday[$lid]", PARAM_INT);

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
        $mform->setType('weekstart', PARAM_TEXT);

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
