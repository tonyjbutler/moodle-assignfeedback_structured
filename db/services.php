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
 * Web service for the structured feedback plugin.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'assignfeedback_structured_get_criteria' => array(
        'classname'     => 'assignfeedback_structured_external',
        'methodname'    => 'get_criteria',
        'classpath'     => 'mod/assign/feedback/structured/externallib.php',
        'description'   => 'Get the criteria data for a given criteria set',
        'type'          => 'read',
        'ajax'          => true
    ),
    'assignfeedback_structured_get_criteriasets' => array(
        'classname'     => 'assignfeedback_structured_external',
        'methodname'    => 'get_criteriasets',
        'classpath'     => 'mod/assign/feedback/structured/externallib.php',
        'description'   => 'Get all saved criteria sets for the current user',
        'type'          => 'read',
        'ajax'          => true
    ),
    'assignfeedback_structured_save_criteriaset' => array(
        'classname'     => 'assignfeedback_structured_external',
        'methodname'    => 'save_criteriaset',
        'classpath'     => 'mod/assign/feedback/structured/externallib.php',
        'description'   => 'Save a criteria set to copy later',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'assignfeedback/structured:manageowncriteriasets'
    ),
    'assignfeedback_structured_update_criteriaset' => array(
        'classname'     => 'assignfeedback_structured_external',
        'methodname'    => 'update_criteriaset',
        'classpath'     => 'mod/assign/feedback/structured/externallib.php',
        'description'   => 'Update the details of a saved criteria set',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'assignfeedback/structured:manageowncriteriasets'
    ),
    'assignfeedback_structured_delete_criteriaset' => array(
        'classname'     => 'assignfeedback_structured_external',
        'methodname'    => 'delete_criteriaset',
        'classpath'     => 'mod/assign/feedback/structured/externallib.php',
        'description'   => 'Delete a saved criteria set',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'assignfeedback/structured:manageowncriteriasets'
    )
);
