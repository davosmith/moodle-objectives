<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Timetable form
 *
 * @package   block_objectives
 * @copyright 2020 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_objectives;

use moodleform;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class timetable_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $course = $custom['course'];
        $days = $custom['days'];

        $groups = groups_get_all_groups($course->id, 0, 0, 'g.id, g.name');
        $groupnames = array();
        $groupnames[-1] = get_string('disable');
        $groupnames[0] = get_string('allgroups');
        if ($groups) {
            foreach ($groups as $id => $group) {
                $groupnames[$id] = $group->name;
            }
        }
        $hours = array();
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = sprintf('%02d', $i);
        }
        $minutes = array();
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[$i] = sprintf('%02d', $i);
        }

        $weekday = 0;
        foreach ($days as $day => $lessons) {
            $mform->addElement('header', $day, get_string($day, 'calendar'));
            foreach ($lessons as $lid) {
                $lel = array();
                $lel[] = $mform->createElement('select', "lgroup[$lid]", get_string('group', 'block_objectives'), $groupnames);
                $mform->setDefault("lgroup[$lid]", -1);
                $lel[] = $mform->createElement('static', null, '', '&nbsp;&nbsp;'.get_string('lessonstart', 'block_objectives'));
                $lel[] = $mform->createElement('select', "lstarthour[$lid]", get_string('lessonstarthour', 'block_objectives'),
                                               $hours);
                $mform->setDefault("lstarthour[$lid]", 8);
                $mform->disabledIf("lstarthour[$lid]", "lgroup[$lid]", 'eq', -1);
                $lel[] = $mform->createElement('select', "lstartminute[$lid]", get_string('lessonstartminute', 'block_objectives'),
                                               $minutes);
                $mform->disabledIf("lstartminute[$lid]", "lgroup[$lid]", 'eq', -1);

                $lel[] = $mform->createElement('static', null, '', '&nbsp;&nbsp;'.get_string('lessonend', 'block_objectives'));
                $lel[] = $mform->createElement('select', "lendhour[$lid]", get_string('lessonendhour', 'block_objectives'), $hours);
                $mform->setDefault("lendhour[$lid]", 8);
                $mform->disabledIf("lendhour[$lid]", "lgroup[$lid]", 'eq', -1);
                $lel[] = $mform->createElement('select', "lendminute[$lid]", get_string('lessonstartminute', 'block_objectives'),
                                               $minutes);
                $mform->disabledIf("lendminute[$lid]", "lgroup[$lid]", 'eq', -1);

                $lel[] = $mform->createElement('hidden', "lday[$lid]", $weekday);
                $mform->setType("lday[$lid]", PARAM_INT);

                $mform->addGroup($lel, 'lesson'.$lid.'group', get_string('lesson', 'block_objectives'), array(''), false);
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
        $buttons[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttons[] = $mform->createElement('submit', 'saveandobjectives', get_string('saveandobjectives', 'block_objectives'));
        $buttons[] = $mform->createElement('cancel');
        $mform->addGroup($buttons, 'actionbuttons', '', array(' '), false);
        $mform->closeHeaderBefore('actionbuttons');
    }
}
