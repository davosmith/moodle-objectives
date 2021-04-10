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

/**
 * Main block class
 *
 * @package   block_objectives
 * @copyright Davo Smith (moodle@davosmith.co.uk)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_objectives
 */
class block_objectives extends block_base {

    /**
     * Block initialisation
     * @throws coding_exception
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_objectives');
    }

    /**
     * Where can this block be shown?
     * @return array
     */
    public function applicable_formats() {
        return array('all' => false, 'course' => true);
    }

    /**
     * Preferred block width
     * @return int
     */
    public function preferred_width() {
        return 240;
    }

    /**
     * Get the block content
     * @return stdClass|stdObject|null
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $obj = new \block_objectives\objectives($COURSE);

        $this->content = new stdClass;
        $this->content->text = $obj->get_block_text();
        $this->content->footer = $obj->get_block_footer();

        return $this->content;
    }

    /**
     * Is per-instance config allowed?
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }
}
