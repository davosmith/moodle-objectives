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
 * Behat step definitions
 *
 * @package   block_objectives
 * @copyright 2015 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Gherkin\Node\TableNode;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related to the objectives block.
 *
 * @package    block_objectives
 * @copyright  2015 Davo Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_objectives extends behat_base {

    /**
     * Override the objectives block current time - this is a global setting, affecting all users.
     * The page is also reloaded (to make sure the change appears immediately).
     *
     * @When /^I force the objectives block current time to "(?P<date_string>[^"]*)"$/
     */
    public function i_force_the_objectives_block_current_time_to($datestring) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/objectives/lib.php');

        $timestamp = strtotime($datestring);
        block_objectives_class::override_current_time($timestamp);

        $this->getSession()->reload();
    }

    /**
     * Add the given timetable entries to the given course
     *
     * @Given /^the following objectives timetable exists in course "(?P<course_string>[^"]*)":$/
     * @param string $coursename
     * @param TableNode $table
     */
    public function the_following_objectives_timetable_exists_in_course($coursename, TableNode $table) {
        global $DB;

        $required = array(
            'day',
            'starttime',
            'endtime',
        );
        $optional = array(
            'group' => ''
        );

        // Valid settings for 'day'.
        $validdays = array(
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
            'Sunday' => 6,
        );

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Objectives timetables require the field '.$reqname.' to be set');
            }
        }
        foreach ($firstrow as $fieldname => $unused) {
            if (!in_array($fieldname, $required) && !array_key_exists($fieldname, $optional)) {
                throw new Exception('Objectives timetable unknown field '.$fieldname);
            }
        }

        $course = $DB->get_record('course', array('shortname' => $coursename), 'id', MUST_EXIST);
        $objectives = $DB->get_record('block_objectives', array('course' => $course->id), 'id', MUST_EXIST);
        $groups = $DB->get_records('groups', array('courseid' => $course->id), '', 'idnumber, id');

        foreach ($data as $row) {
            $groupid = 0;
            if (!empty($row['group'])) {
                $groupidnumber = $row['group'];
                if (!isset($groups[$groupidnumber])) {
                    throw new Exception('Group with idnumber '.$groupidnumber.' not found');
                }
                $groupid = $groups[$groupidnumber]->id;
            }
            $dayname = $row['day'];
            if (!isset($validdays[$dayname])) {
                throw new Exception('Unknown week day '.$dayname);
            }
            $day = $validdays[$dayname];
            $starttime = $this->timestring_to_timetable_timestamp($row['starttime']);
            $endtime = $this->timestring_to_timetable_timestamp($row['endtime']);

            $ins = (object)array(
                'objectivesid' => $objectives->id,
                'groupid' => $groupid,
                'day' => $day,
                'starttime' => $starttime,
                'endtime' => $endtime,
            );
            $DB->insert_record('block_objectives_timetable', $ins);
        }
    }

    private function timestring_to_timetable_timestamp($time) {
        $timestamp = strtotime($time);
        $dateinfo = getdate($timestamp);
        $timestamp = mktime((int)$dateinfo['hours'], (int)$dateinfo['minutes'], (int)$dateinfo['seconds'], 0, 0, 0);
        return $timestamp;
    }

    /**
     * Add the given lesson objectives to the given course
     *
     * @Given /^the following objectives exist in course "(?P<course_string>[^"]*)":$/
     * @param string $coursename
     * @param TableNode $table
     */
    public function the_following_objectives_exist_in_course($coursename, TableNode $table) {
        global $DB;

        $required = array(
            'weekstart', // A string representing the start of the week YYYYMMDD.
            'day',
            'starttime',
            'objectives', // Comma-separated.
        );

        // Valid settings for 'day'.
        $validdays = array(
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
            'Sunday' => 6,
        );

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Objectives require the field '.$reqname.' to be set');
            }
        }

        $course = $DB->get_record('course', array('shortname' => $coursename), 'id', MUST_EXIST);
        $objectives = $DB->get_record('block_objectives', array('course' => $course->id), 'id', MUST_EXIST);

        foreach ($data as $row) {
            $weekstart = $row['weekstart'];
            if (!preg_match('|20\d{6,6}|', $weekstart)) {
                throw new Exception('Objectives week must be in the form YYYYMMDD');
            }
            $dayname = $row['day'];
            if (!isset($validdays[$dayname])) {
                throw new Exception('Unknown week day '.$dayname);
            }
            $day = $validdays[$dayname];
            $starttime = $this->timestring_to_timetable_timestamp($row['starttime']);
            $params = array(
                'objectivesid' => $objectives->id,
                'day' => $day,
                'starttime' => $starttime,
            );
            $timetable = $DB->get_record('block_objectives_timetable', $params, 'id', MUST_EXIST);
            $obj = explode(',', $row['objectives']);
            $obj = array_map(function($obj) {
                return '-'.$obj;
            }, $obj);
            $obj = implode("\n", $obj);

            $ins = (object)array(
                'timetableid' => $timetable->id,
                'weekstart' => $weekstart,
                'objectives' => $obj,
            );
            $DB->insert_record('block_objectives_objectives', $ins);
        }
    }
}
