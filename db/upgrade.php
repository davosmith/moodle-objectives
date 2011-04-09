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
    $result = true;

    if ($result && $oldversion < 2011040900) {
        // Define field weekstartstr to be added to objectives_objectives
        $table = new XMLDBTable('objectives_objectives');
        $field = new XMLDBField('weekstartstr');
        $field->setAttributes(XMLDB_TYPE_CHAR, '8', null, null, null, null, null, null, 'weekstart');
        $result = $result && add_field($table, $field);

        // Convert all existing 'weekstart' values into string representation
        $objs = get_records_select('objectives_objectives', 'weekstart > 0');
        foreach ($objs as $obj) {
            $newobj = new stdClass;
            $newobj->id = $obj->id;
            $newobj->weekstartstr = date('Ymd', $obj->weekstart);
            update_record('objectives_objectives', $newobj);
        }

        // Drop the old 'weekstart' index
        $index = new XMLDBIndex('weekstart');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('timetableid', 'weekstart'));
        $result = $result && drop_index($table, $index);

        // Drop the old 'weekstart' field
        $field = new XMLDBField('weekstart');
        $result = $result && drop_field($table, $field);

        // Rename 'weekstartstr' to 'weekstart'
        $field = new XMLDBField('weekstartstr');
        $field->setAttributes(XMLDB_TYPE_CHAR, '8', null, null, null, null, null, null, '');
        $result = $result && rename_field($table, $field, 'weekstart');

        // Add index for 'weekstart' field
        $index = new XMLDBIndex('weekstartstr');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('timetableid', 'weekstart'));
        $result = $result && add_index($table, $index);
    }

    return $result;
}