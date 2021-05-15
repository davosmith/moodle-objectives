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

/**
 * Edit objectives
 *
 * @package   block_objectives
 * @copyright Davo Smith (moodle@davosmith.co.uk)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
global $DB, $PAGE;

$courseid = required_param('course', PARAM_INT);
$viewtab = optional_param('viewtab', 'objectives', PARAM_TEXT);
$weekstart = optional_param('weekstart', null, PARAM_TEXT);

$course = $DB->get_record('course', array('id' => $courseid));
if (!$course) {
    throw new moodle_exception('Invalid courseid');
}

$url = new moodle_url('/blocks/objectives/edit.php', array('course' => $course->id, 'viewtab' => $viewtab));
if ($weekstart != 0) {
    $url->param('weekstart', $weekstart);
}
$PAGE->set_url($url);

require_login($course);

$obj = new \block_objectives\objectives($course);

if ($viewtab == 'timetables') {
    $obj->edit_timetables();
} else {
    $obj->edit_objectives($weekstart);
}
