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

require_once(dirname(__FILE__).'/mod_form.php');
require_once(dirname(__FILE__).'/lib.php');

class block_objectives extends block_base {

    function init() {
        $this->title = get_string('pluginname','block_objectives');
        $this->version = 2011031301;
    }

    function preferred_width() {
        return 240;
    }

    function get_content() {
        global $CFG, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $obj = new block_objectives_class($COURSE);

        $this->content = new stdClass;
        $this->content->text = $obj->get_block_text();
        $this->content->footer = $obj->get_block_footer();

        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function instance_config_print() {
        global $CFG, $COURSE;

        $obj = new block_objectives_class($COURSE);
        $settings = $obj->get_settings();

        $returl = $CFG->wwwroot.'/course/view.php?id='.$settings->course;
        $mform = new block_objectives_edit_form(qualified_me());

        $settings->objectivesid = $settings->id;
        unset($settings->id);
        $mform->set_data($settings);

        if ($mform->is_cancelled()) {
            redirect($returl);
        }

        if ($data = $mform->get_data() and $data->action == 'savesettings') {

            $update = new stdClass;
            $update->id = $data->objectivesid;
            //$update->course = $data->course; // Should not change
            $update->intro = $data->intro;
            update_record('objectives',$update);

            redirect($returl);
        }

        echo '</form>'; // Close the 'helpful' form provided
        $mform->display();
        echo '<form>'; // Start a fake form, to make sure the tags match

        return true;
    }

    function instance_config_save($data) {
        $this->instance_config_print();
        return true;
    }
}

?>