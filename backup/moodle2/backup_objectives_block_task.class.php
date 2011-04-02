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
 * @package blocks
 * @subpackage objectives
 * @copyright 2011 Davo Smith ( davo@davodev.co.uk )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/blocks/objectives/backup/moodle2/backup_objectives_stepslib.php'); // We have structure steps

/**
 * Specialised backup task for the rss_client block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
class backup_objectives_block_task extends backup_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        // objectives has one structure step
        $this->add_step(new backup_objectives_block_structure_step('objectives_structure', 'objectives.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }

    static public function encode_content_links($content) {
        return $content; // No special encoding of links
    }
}

