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
 * Define all the backup steps that wll be used by the backup_objectives_block_task
 */

/**
 * Define the complete objectives structure for backup, with file and id annotations
 */
class backup_objectives_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated
        $objectives = new backup_nested_element('objectives', array('id'), array('intro') );

        $objectives_timetables = new backup_nested_element('objectives_timetables');
        $objectives_timetable = new backup_nested_element('objectives_timetable', array('id'), array('groupid','day','starttime','endtime') );

        $objectives_objectives = new backup_nested_element('objectives_objectives');
        $objectives_objective = new backup_nested_element('objectives_objective', array('id'), array('weekstart','objectives') );

        // Build the tree

        $objectives->add_child($objectives_timetables);
        $objectives_timetables->add_child($objectives_timetable);
        $objectives_timetable->add_child($objectives_objectives);
        $objectives_objectives->add_child($objectives_objective);

        // Define sources

        $objectives->set_source_table('block_objectives', array('course' => backup::VAR_COURSEID));
        $objectives_timetable->set_source_table('block_objectives_timetable', array('objectivesid' => backup::VAR_PARENTID));
        $objectives_objective->set_source_table('block_objectives_objectives', array('timetableid' => backup::VAR_PARENTID));

        // ID annotations
        $objectives_timetable->annotate_ids('groups','groupid');

        // No file annotations

        // Return the root element (objectives), wrapped into standard block structure
        return $this->prepare_block_structure($objectives);
    }
}
