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
 * Objectives form
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

/**
 * Class objectives_form
 * @package block_objectives
 */
class objectives_form extends moodleform {
    /**
     * Define the form elements
     * @throws \coding_exception
     */
    protected function definition() {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $timetables = $custom['timetables'];
        $course = $custom['course'];
        $groups = groups_get_all_groups($course->id, 0, 0, 'g.id, g.name');
        $num2day = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

        $lastday = -1;
        foreach ($timetables as $lesson) {
            if ($lastday != $lesson->day) {
                $day = $num2day[$lesson->day];
                $mform->addElement('header', $day, get_string($day, 'calendar'));
                $lastday = $lesson->day;
            }
            $objlabel = userdate($lesson->starttime, get_string('strftimetime')).'-';
            $objlabel .= userdate($lesson->endtime, get_string('strftimetime'));
            if ($lesson->groupid > 0) {
                $objlabel .= ' ('.$groups[$lesson->groupid]->name.')';
            }
            $mform->addElement('textarea', "obj[{$lesson->id}]", $objlabel, array('cols' => 40, 'rows' => 5));
        }

        $mform->addElement('hidden', 'course', $course->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'weekstart', 0);
        $mform->setType('weekstart', PARAM_TEXT);

        $mform->addElement('hidden', 'action', 'savesettings');
        $mform->setType('action', PARAM_TEXT);

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttons[] =& $mform->createElement('submit', 'saveandcourse', get_string('saveandcourse', 'block_objectives'));
        $buttons[] =& $mform->createElement('cancel');
        $mform->addGroup($buttons, 'actionbuttons', '', array(' '), false);
        $mform->closeHeaderBefore('actionbuttons');
    }
}
