<?php
// This file is part of the Objectives block for Moodle
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
 * Backup objectives block instance
 *
 * @package block_objectives
 * @copyright 2011 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/blocks/objectives/backup/moodle2/backup_objectives_stepslib.php');

/**
 * Class backup_objectives_block_task
 */
class backup_objectives_block_task extends backup_block_task {

    /**
     * Define the backup settings
     */
    protected function define_my_settings() {
    }

    /**
     * Define the backup steps
     * @throws base_task_exception
     */
    protected function define_my_steps() {
        // Objectives has one structure step.
        $this->add_step(new backup_objectives_block_structure_step('objectives_structure', 'objectives.xml'));
    }

    /**
     * Define the file areas involved
     * @return array
     */
    public function get_fileareas() {
        return array(); // No associated fileareas.
    }

    /**
     * Extra encoding of config data
     * @return array
     */
    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata.
    }

    /**
     * Encode links in the content
     * @param mixed $content
     */
    public static function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}

