<?php

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
    )
);
