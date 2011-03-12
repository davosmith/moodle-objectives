<?php

$block_objectives_capabilities = array(
    // Can edit timetables
    'blocks/objectives:edittimetables' => array(
         'captype' => 'write',
         'riskbitmask' => RISK_SPAM,
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'editingteacher' => CAP_ALLOW,
              'admin' => CAP_ALLOW
         )
    ),

    // Can edit objectives
    'blocks/objectives:editobjectives' => array(
         'captype' => 'write',
         'riskbitmask' => RISK_SPAM,
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'editingteacher' => CAP_ALLOW,
              'admin' => CAP_ALLOW
         )
    ),

    // Can check-off objectives
    'blocks/objectives:checkoffobjectives' => array(
         'captype' => 'write',
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'teacher' => CAP_ALLOW,
              'editingteacher' => CAP_ALLOW,
              'admin' => CAP_ALLOW
         )
    ),
    
    // Can view objectives on a course
    'blocks/coursedates:viewobjectives' => array(
         'captype' => 'read',
         'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
              'student' => CAP_ALLOW,
              'teacher' => CAP_ALLOW,             
              'editingteacher' => CAP_ALLOW,
              'admin' => CAP_ALLOW
         )
    )
);