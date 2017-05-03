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
     * Return a description of the parameters for the save_criteriaset method.
     *
     * @return external_function_parameters
     */
    public static function save_criteriaset_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context ID of the current assignment instance'),
                'name' => new external_value(PARAM_TEXT, 'A name for the new criteria set'),
                'criteria' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_TEXT, 'The criterion name'),
                            'description' => new external_value(PARAM_RAW, 'The criterion description', VALUE_OPTIONAL)
                        ), 'The data for a single criterion'
                    ), 'The criteria data'
                ),
                'public' => new external_value(PARAM_BOOL, 'Whether the new criteria set should be shared')
            )
        );
    }

    /**
     * Save a new named criteria set to copy later, using the data provided.
     *
     * @param int $contextid The context id of the current assignment instance.
     * @param string $name A name for the new criteria set.
     * @param array $criteria The criteria data.
     * @param bool $public Whether the new criteria set should be shared.
     * @return array Details of a message to be displayed to the user.
     * @throws moodle_exception
     */
    public static function save_criteriaset($contextid, $name, $criteria, $public) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
            'contextid' => $contextid,
            'name'      => $name,
            'criteria'  => $criteria,
            'public'    => $public
        );
        self::validate_parameters(self::save_criteriaset_parameters(), $parameters);
        $context = self::get_context_from_params(array('contextid' => $contextid));
        self::validate_context($context);
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $context)) {
            throw new moodle_exception('nopermissionstosave', 'assignfeedback_structured');
        }

        // Validate global uniqueness of criteria set name provided.
        if ($DB->record_exists('assignfeedback_structured_cs', array('name' => $name))) {
            return array(
                'hide' => false,
                'title' => get_string('criteriasetnameusedtitle', 'assignfeedback_structured'),
                'body' => get_string('criteriasetnameused', 'assignfeedback_structured', $name),
                'label' => get_string('continue')
            );
        }

        $assignment = new assign($context, null, null);
        $feedback = new assign_feedback_structured($assignment, 'structured');

        if ($feedback->save_criteria_set($name, $criteria, $public)) {
            return array(
                'hide' => true,
                'title' => get_string('criteriasetsaved', 'assignfeedback_structured'),
                'body' => get_string('criteriasetsavedsuccess', 'assignfeedback_structured', $name),
                'label' => get_string('ok')
            );
        }

        return array(
            'hide' => true,
            'title' => get_string('error'),
            'body' => get_string('criteriasetnotsaved', 'assignfeedback_structured'),
            'label' => get_string('continue')
        );
    }

    /**
     * Return a description of the result value for the save_criteriaset method.
     *
     * @return external_description
     */
    public static function save_criteriaset_returns() {
        new external_single_structure(
            array(
                'hide' => new external_value(PARAM_BOOL, 'Whether or not to hide the save dialogue'),
                'title' => new external_value(PARAM_TEXT, 'The title of the message to display to the user'),
                'body' => new external_value(PARAM_TEXT, 'The body text of the message to display to the user'),
                'label' => new external_value(PARAM_TEXT, 'The button label for the message dialogue displayed to the user')
            )
        );
    }

    /**
     * Return a description of the parameters for the delete_criteriaset method.
     *
     * @return external_function_parameters
     */
    public static function delete_criteriaset_parameters() {
        return new external_function_parameters(
            array(
                'contextid'     => new external_value(PARAM_INT, 'The context ID of the current assignment instance'),
                'criteriasetid' => new external_value(PARAM_INT, 'The ID of the criteria set to be deleted')
            )
        );
    }

    /**
     * Delete the saved criteria set with the given id.
     *
     * @param int $contextid The context id of the current assignment instance.
     * @param int $criteriasetid The id of the criteria set to be deleted.
     * @return bool Success status.
     * @throws moodle_exception
     */
    public static function delete_criteriaset($contextid, $criteriasetid) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
            'contextid'     => $contextid,
            'criteriasetid' => $criteriasetid
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
