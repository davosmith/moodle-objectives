<?php

// This file is part of the Objectives block for Moodle
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
 * @package blocks
 * @subpackage objectives
 * @copyright 2011 Davo Smith ( davo@davodev.co.uk )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that wll be used by the restore_objectives_block_task
 */

/**
 * Define the complete objectives structure for restore
 */
class restore_objectives_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        //$paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('objectives', '/block/objectives');
        $paths[] = new restore_path_element('objectives_timetable', '/block/objectives/objectives_timetables/objectives_timetable');
        $paths[] = new restore_path_element('objectives_objective', '/block/objectives/objectives_timetables/objectives_timetable/objectives_objectives/objectives_objective');

        return $paths;
    }

    public function process_objectives($data) {
        global $DB;

        $data = (object)$data;

        // For any reason (non multiple, dupe detected...) block not restored, return
        if (!$this->task->get_blockid()) {
            echo "No blockid";
            return;
        }

        $courseid = $this->task->get_courseid();
        if (!$courseid) {
            echo "No courseid";
            return;
        }

        $oldid = $data->id;
        $data->course = $courseid;
        $newid = $DB->insert_record('block_objectives', $data);
        $this->set_mapping('objectives', $oldid, $newid);
    }

    public function process_objectives_timetable($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->objectivesid = $this->get_new_parentid('objectives');
        $data->groupid = $this->get_mappingid('groups', $data->groupid, 0);
        $newid = $DB->insert_record('block_objectives_timetable', $data);
        $this->set_mapping('objectives_timetable', $oldid, $newid);
    }

    public function process_objectives_objective($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timetableid = $this->get_new_parentid('objectives_timetable');
        $newid = $DB->insert_record('block_objectives_objectives', $data);
        $this->set_mapping('objectives_objective', $oldid, $newid);
    }
}
