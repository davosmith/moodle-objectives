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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');

class block_objectives extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_objectives');
    }

    public function applicable_formats() {
        return array('all' => false, 'course' => true);
    }

    public function preferred_width() {
        return 240;
    }

    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $obj = new block_objectives_class($COURSE);

        $this->content = new stdClass;
        $this->content->text = $obj->get_block_text();
        $this->content->footer = $obj->get_block_footer();

        return $this->content;
    }

    public function instance_allow_config() {
        return true;
    }
}
