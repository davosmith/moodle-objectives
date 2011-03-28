<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$courseid = required_param('course',PARAM_INT);
$weekstart = optional_param('weekstart', 0, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid));
if (!$course) {
    //UT
    error('Invalid courseid');
}

require_login($course);

$obj = new block_objectives_class($course);

$obj->view_objectives($weekstart);

?>