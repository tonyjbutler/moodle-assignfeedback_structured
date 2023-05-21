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
 * Admin settings for the structured feedback plugin.
 *
 * @package    assignfeedback_structured
 * @copyright  2023 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die;

$settings->add(new admin_setting_configtext('assignfeedback_structured/defaultcritname',
        get_string('defaultcritname', 'assignfeedback_structured'),
        get_string('defaultcritname_help', 'assignfeedback_structured'), '', PARAM_TEXT));
$settings->add(new admin_setting_configtextarea('assignfeedback_structured/defaultcritdesc',
        get_string('defaultcritdesc', 'assignfeedback_structured'),
        get_string('defaultcritdesc_help', 'assignfeedback_structured'), '', PARAM_TEXT));
