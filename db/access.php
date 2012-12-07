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

$capabilities = array(
    // Can edit timetables
    'block/objectives:edittimetables' => array(
         'captype' => 'write',
         'riskbitmask' => RISK_SPAM,
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'editingteacher' => CAP_ALLOW,
              'coursecreator' => CAP_ALLOW,
              'manager' => CAP_ALLOW
         )
    ),

    // Can edit objectives
    'block/objectives:editobjectives' => array(
         'captype' => 'write',
         'riskbitmask' => RISK_SPAM,
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'editingteacher' => CAP_ALLOW,
              'coursecreator' => CAP_ALLOW,
              'manager' => CAP_ALLOW
         )
    ),

    // Can check-off objectives
    'block/objectives:checkoffobjectives' => array(
         'captype' => 'write',
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'teacher' => CAP_ALLOW,
              'editingteacher' => CAP_ALLOW,
              'coursecreator' => CAP_ALLOW,
              'manager' => CAP_ALLOW
         )
    ),

    // Can view objectives on a course
    'block/objectives:viewobjectives' => array(
         'captype' => 'read',
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'student' => CAP_ALLOW,
              'teacher' => CAP_ALLOW,
              'editingteacher' => CAP_ALLOW,
              'coursecreator' => CAP_ALLOW,
              'manager' => CAP_ALLOW
         )
    ),

    'block/objectives:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        ),
    ),

    'block/objectives:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);
