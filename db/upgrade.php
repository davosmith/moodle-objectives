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

function xmldb_block_objectives_upgrade($oldversion=0) {
    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2011040900) {
        // Define field weekstartstr to be added to objectives_objectives
        $table = new xmldb_table('objectives_objectives');
        $field = new xmldb_field('weekstartstr', XMLDB_TYPE_CHAR, '8', null, null, null, null, 'weekstart');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Convert all existing 'weekstart' values into string representation
        $objs = $DB->get_records_select('objectives_objectives', 'weekstart > 0');
        foreach ($objs as $obj) {
            $newobj = new stdClass;
            $newobj->id = $obj->id;
            $newobj->weekstartstr = date('Ymd', $obj->weekstart);
            $DB->update_record('objectives_objectives', $newobj);
        }

        // Drop the old 'weekstart' index
        $index = new xmldb_index('weekstart', XMLDB_INDEX_NOTUNIQUE, array('timetableid', 'weekstart'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Drop the old 'weekstart' field
        $field = new xmldb_field('weekstart');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Rename 'weekstartstr' to 'weekstart'
        $field = new xmldb_field('weekstartstr', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, null, 'timetableid');
        $dbman->rename_field($table, $field, 'weekstart');

        // Add index for 'weekstart' field
        $index = new xmldb_index('weekstart', XMLDB_INDEX_NOTUNIQUE, array('timetableid', 'weekstart'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // objectives savepoint reached
        upgrade_block_savepoint(true, 2011040900, 'objectives');
    }

    if ($result && $oldversion < 2011040901) {
        // Update all old lesson timestamps, using make_timestamp
        $lessons = $DB->get_records_select('objectives_timetable','starttime < 86400');
        foreach ($lessons as $lesson) {
            $starthour = intval($lesson->starttime / (60*60));
            $startmin = intval($lesson->starttime / 60) % 60;
            $endhour = intval($lesson->endtime / (60*60));
            $endmin = intval($lesson->endtime / 60) % 60;

            $updlesson = new stdClass;
            $updlesson->id = $lesson->id;
            $updlesson->starttime = make_timestamp(0, 0, 0, $starthour, $startmin, 0);
            $updlesson->endtime = make_timestamp(0, 0, 0, $endhour, $endmin, 0);
            $DB->update_record('objectives_timetable', $updlesson);
        }

        // objectives savepoint reached
        upgrade_block_savepoint(true, 2011040901, 'objectives');
    }

    if ($result && $oldversion < 2012020700) {
        $table = new xmldb_table('objectives');
        $dbman->rename_table($table, 'block_objectives');

        $table = new xmldb_table('objectives_timetable');
        $dbman->rename_table($table, 'block_objectives_timetable');

        $table = new xmldb_table('objectives_objectives');
        $dbman->rename_table($table, 'block_objectives_objectives');

        // objectives savepoint reached
        upgrade_block_savepoint(true, 2012020700, 'objectives');
    }

    return $result;
}