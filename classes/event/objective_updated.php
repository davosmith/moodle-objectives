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
 * The block_objectives student checks updated event.
 *
 * @package    block_objectives
 * @copyright  2014 Davo Smith <moodle@davosmith.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_objectives\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The block_objectives objective updated class.
 *
 * @package    block_objectives
 * @since      Moodle 2.7
 * @copyright  2014 Davo Smith <moodle@davosmith.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_updated extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_objectives_objectives';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('objectiveupdated', 'block_objectives');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if ($this->other['completed']) {
            return "The user with id '$this->userid' has marked a lesson objective in '$this->objectid' as completed";
        } else {
            return "The user with id '$this->userid' has marked a lesson objective in '$this->objectid' as not completed";
        }
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/checklist/report.php', array('id' => $this->contextinstanceid,
                                                                  'studentid' => $this->userid));
    }

    protected function validate_data() {
        if (!isset($this->other['completed'])) {
            throw new \coding_exception("Must specify '\$other['completed']' - whether the objective was marked as completed or not");
        }
    }
}