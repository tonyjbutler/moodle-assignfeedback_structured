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

$functions = array(
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
