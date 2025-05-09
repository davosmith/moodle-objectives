<?php
// This file is part of Moodle - http://moodle.org/
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
 * Restore objective block instance
 *
 * @package   block_objectives
 * @copyright Davo Smith (moodle@davosmith.co.uk)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/objectives/backup/moodle2/restore_objectives_stepslib.php');

/**
 * Class restore_objectives_block_task
 */
class restore_objectives_block_task extends restore_block_task {

    /**
     * Define the restore settings
     */
    protected function define_my_settings() {
    }

    /**
     * Define the restore steps
     * @throws base_task_exception
     */
    protected function define_my_steps() {
        // Objectives has one structure step.
        $this->add_step(new restore_objectives_block_structure_step('objectives_structure', 'objectives.xml'));
    }

    /**
     * Define the file areas used
     * @return array
     */
    public function get_fileareas() {
        return []; // No associated fileareas.
    }

    /**
     * Handle encoded config data
     * @return array
     */
    public function get_configdata_encoded_attributes() {
        return []; // No special handling of configdata.
    }

    /**
     * Decode the block content
     * @return array
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Define any decode rules for the content
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }
}

