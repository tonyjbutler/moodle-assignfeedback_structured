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
 * External API for the structured feedback plugin.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Library class for the structured feedback plugin external API functions.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */
class assignfeedback_structured_external extends external_api {

    /**
     * Return a description of the parameters for the delete_criteriaset method.
     *
     * @return external_function_parameters
     */
    public static function delete_criteriaset_parameters() {
        return new external_function_parameters(
                array(
                    'criteriasetid' => new external_value(PARAM_INT, 'The ID of the criteria set to be deleted', VALUE_REQUIRED),
                    'contextid'     => new external_value(PARAM_INT, 'The ID of the current assignment instance', VALUE_REQUIRED)
                )
        );
    }

    /**
     * Delete the saved criteria set with the given id, prompting for user confirmation first.
     *
     * @param int $criteriasetid The id of the criteria set to be deleted.
     * @param int $contextid The context id of the current assignment instance.
     * @return bool Success status.
     * @throws moodle_exception
     */
    public static function delete_criteriaset($criteriasetid, $contextid) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
            'criteriasetid' => $criteriasetid,
            'contextid'     => $contextid
        );
        self::validate_parameters(self::delete_criteriaset_parameters(), $parameters);
        $context = self::get_context_from_params(array('contextid' => $contextid));
        self::validate_context($context);
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $context)) {
            throw new moodle_exception('nopermissionstodelete', 'assignfeedback_structured');
        }

        $assignment = new assign($context, null, null);
        $feedback = new assign_feedback_structured($assignment, 'structured');

        if ($feedback->delete_criteria_set($criteriasetid)) {
            return true;
        }

        return false;
    }

    /**
     * Return a description of the result value for the delete_criteriaset method.
     *
     * @return external_description
     */
    public static function delete_criteriaset_returns() {
        return new external_value(PARAM_BOOL, 'Whether or not the criteria set was successfully deleted');
    }

}
