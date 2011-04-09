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

require_once($CFG->libdir.'/formslib.php');

class block_objectives_edit_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('text', 'intro', get_string('introduction','block_objectives'), array('size' => 40));

        $mform->addElement('hidden', 'objectivesid', 0);
        $mform->setType('objectivesid', PARAM_INT);
        $mform->addElement('hidden', 'course', 0);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'action', 'savesettings');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }
}

?>