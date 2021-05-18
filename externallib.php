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
     * Return a description of the parameters for the get_criteria method.
     *
     * @return external_function_parameters
     */
    public static function get_criteria_parameters() {
        return new external_function_parameters(
            array(
                'contextid'     => new external_value(PARAM_INT, 'The context ID of the current assignment instance'),
                'criteriasetid' => new external_value(PARAM_INT, 'The criteria set ID for which to return criteria data')
            )
        );
    }

    /**
     * Return an array of criteria data for the criteria set with the id provided.
     *
     * @param int $contextid The context id of the current assignment instance.
     * @param int $criteriasetid The id of a criteria set.
     * @return array The criteria data.
     */
    public static function get_criteria($contextid, $criteriasetid) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
                'contextid'     => $contextid,
                'criteriasetid' => $criteriasetid
        );
        self::validate_parameters(self::get_criteria_parameters(), $parameters);
        $context = self::get_context_from_params(array('contextid' => $contextid));
        self::validate_context($context);

        $assignment = new assign($context, null, null);
        $feedback = new assign_feedback_structured($assignment, 'structured');

        return $feedback->get_criteria($criteriasetid);
    }

    /**
     * Return a description of the result value for the get_criteria method.
     *
     * @return external_description
     */
    public static function get_criteria_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'name'        => new external_value(PARAM_TEXT, 'The criterion name'),
                    'description' => new external_value(PARAM_RAW, 'The criterion description', VALUE_OPTIONAL)
                ), 'The data for a single criterion'
            ), 'The criteria data'
        );
    }

    /**
     * Return a description of the parameters for the get_criteriasets method.
     *
     * @return external_function_parameters
     */
    public static function get_criteriasets_parameters() {
        return new external_function_parameters(
            array(
                'contextid'     => new external_value(PARAM_INT, 'The context ID of the current assignment instance'),
                'includeshared' => new external_value(PARAM_BOOL, 'Whether to include shared criteria sets owned by other users')
            )
        );
    }

    /**
     * Return all saved criteria sets that the current user can manage (or copy into this assignment instance).
     *
     * @param int $contextid The context id of the current assignment instance.
     * @param bool $includeshared Include shared criteria sets owned by other users (for copying only).
     * @return array Grouped array of criteria sets.
     * @throws moodle_exception
     */
    public static function get_criteriasets($contextid, $includeshared) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
            'contextid'     => $contextid,
            'includeshared' => $includeshared
        );
        self::validate_parameters(self::get_criteriasets_parameters(), $parameters);
        $context = self::get_context_from_params(array('contextid' => $contextid));
        self::validate_context($context);
        if (!$includeshared && !has_capability('assignfeedback/structured:manageowncriteriasets', $context)) {
            throw new moodle_exception('nopermissionstomanage', 'assignfeedback_structured');
        }

        $assignment = new assign($context, null, null);
        $feedback = new assign_feedback_structured($assignment, 'structured');

        if (!is_array($criteriasets = $feedback->get_criteria_sets_for_user($includeshared))) {
            return array();
        }

        return $criteriasets;
    }

    /**
     * Return a description of the result value for the get_criteriasets method.
     *
     * @return external_description
     */
    public static function get_criteriasets_returns() {
        return new external_single_structure(
            array(
                'ownedsets' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'     => new external_value(PARAM_TEXT, 'The criteria set ID'),
                            'name'   => new external_value(PARAM_RAW, 'The criteria set name'),
                            'shared' => new external_value(PARAM_BOOL, 'Whether the criteria set is shared')
                        ), 'The data for a single owned criteria set'
                    ), 'The data for any owned criteria sets', VALUE_OPTIONAL
                ),
                'sharedsets' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'   => new external_value(PARAM_TEXT, 'The criteria set ID'),
                            'name' => new external_value(PARAM_RAW, 'The criteria set name')
                        ), 'The data for a single shared criteria set'
                    ), 'The data for any shared criteria sets', VALUE_OPTIONAL
                )
            )
        );
    }

    /**
     * Return a description of the parameters for the save_criteriaset method.
     *
     * @return external_function_parameters
     */
    public static function save_criteriaset_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context ID of the current assignment instance'),
                'name'      => new external_value(PARAM_TEXT, 'A name for the new criteria set'),
                'criteria'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name'        => new external_value(PARAM_TEXT, 'The criterion name'),
                            'description' => new external_value(PARAM_RAW, 'The criterion description', VALUE_OPTIONAL)
                        ), 'The data for a single criterion'
                    ), 'The criteria data'
                ),
                'shared'    => new external_value(PARAM_BOOL, 'Whether the new criteria set should be shared')
            )
        );
    }

    /**
     * Save a new named criteria set to copy later, using the data provided.
     *
     * @param int $contextid The context id of the current assignment instance.
     * @param string $name A name for the new criteria set.
     * @param array $criteria The criteria data.
     * @param bool $shared Whether the new criteria set should be shared.
     * @return array Details of a message to be displayed to the user.
     * @throws moodle_exception
     */
    public static function save_criteriaset($contextid, $name, $criteria, $shared) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
            'contextid' => $contextid,
            'name'      => $name,
            'criteria'  => $criteria,
            'shared'    => $shared
        );
        self::validate_parameters(self::save_criteriaset_parameters(), $parameters);
        $context = self::get_context_from_params(array('contextid' => $contextid));
        self::validate_context($context);
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $context)) {
            throw new moodle_exception('nopermissionstosave', 'assignfeedback_structured');
        }

        // A criteria set name must be provided.
        if (empty($name)) {
            return array(
                'hide'  => false,
                'title' => get_string('criteriasetnonametitle', 'assignfeedback_structured'),
                'body'  => get_string('criteriasetnoname', 'assignfeedback_structured'),
                'label' => get_string('continue')
            );
        }
        // Validate global uniqueness of criteria set name provided.
        if ($DB->record_exists('assignfeedback_structured_cs', array('name_lc' => strtolower($name)))) {
            return array(
                'hide'  => false,
                'title' => get_string('criteriasetnameusedtitle', 'assignfeedback_structured'),
                'body'  => get_string('criteriasetnameused', 'assignfeedback_structured', ucfirst($name)),
                'label' => get_string('continue')
            );
        }
        // Make sure at least one criterion has been defined.
        if (empty($criteria)) {
            return array(
                'hide'  => true,
                'title' => get_string('criteriasetemptytitle', 'assignfeedback_structured'),
                'body'  => get_string('criteriasetempty', 'assignfeedback_structured'),
                'label' => get_string('continue')
            );
        }

        $assignment = new assign($context, null, null);
        $feedback = new assign_feedback_structured($assignment, 'structured');

        if ($feedback->save_criteria_set($name, $criteria, $shared)) {
            return array(
                'hide'  => true,
                'title' => get_string('criteriasetsaved', 'assignfeedback_structured'),
                'body'  => get_string('criteriasetsavedsuccess', 'assignfeedback_structured', ucfirst($name)),
                'label' => get_string('ok')
            );
        }

        return array(
            'hide'  => true,
            'title' => get_string('error'),
            'body'  => get_string('criteriasetnotsaved', 'assignfeedback_structured'),
            'label' => get_string('continue')
        );
    }

    /**
     * Return a description of the result value for the save_criteriaset method.
     *
     * @return external_description
     */
    public static function save_criteriaset_returns() {
        return new external_single_structure(
            array(
                'hide'  => new external_value(PARAM_BOOL, 'Whether or not to hide the save dialogue'),
                'title' => new external_value(PARAM_TEXT, 'The title of the message to display to the user'),
                'body'  => new external_value(PARAM_TEXT, 'The body text of the message to display to the user'),
                'label' => new external_value(PARAM_TEXT, 'The button label for the message dialogue displayed to the user')
            )
        );
    }

    /**
     * Return a description of the parameters for the update_criteriaset method.
     *
     * @return external_function_parameters
     */
    public static function update_criteriaset_parameters() {
        return new external_function_parameters(
            array(
                'contextid'     => new external_value(PARAM_INT, 'The context ID of the current assignment instance'),
                'criteriasetid' => new external_value(PARAM_INT, 'The ID of the criteria set to be updated'),
                'updates'       => new external_single_structure(
                    array(
                        'name'   => new external_value(PARAM_TEXT, 'The new name for the criteria set', VALUE_OPTIONAL),
                        'shared' => new external_value(PARAM_BOOL, 'Whether the criteria set should be shared', VALUE_OPTIONAL)
                    ), 'The key/value pairs of attributes to be updated'
                )
            )
        );
    }

    /**
     * Update one or more criteria set attributes (e.g. name, visibility) with the new values provided.
     *
     * @param int $contextid The context id of the current assignment instance.
     * @param int $criteriasetid The id of the criteria set to be updated.
     * @param array $updates The key/value pairs of attributes to be updated.
     * @return array Details of a message to be displayed to the user.
     * @throws moodle_exception
     */
    public static function update_criteriaset($contextid, $criteriasetid, $updates) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

        $parameters = array(
            'contextid'     => $contextid,
            'criteriasetid' => $criteriasetid,
            'updates'       => $updates
        );
        self::validate_parameters(self::update_criteriaset_parameters(), $parameters);
        $context = self::get_context_from_params(array('contextid' => $contextid));
        self::validate_context($context);
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $context)) {
            throw new moodle_exception('nopermissionstoupdate', 'assignfeedback_structured');
        }
        foreach ($updates as $key => $value) {
            if ($key == 'shared' && $value == true && !has_capability('assignfeedback/structured:publishcriteriasets', $context)) {
                throw new moodle_exception('nopermissionstopublish', 'assignfeedback_structured');
            }

            // Validate new name if being updated.
            if ($key == 'name') {
                // The name cannot be empty.
                if (empty($value)) {
                    return array(
                        'success' => false,
                        'title'   => get_string('criteriasetnonametitle', 'assignfeedback_structured'),
                        'body'    => get_string('criteriasetnoname', 'assignfeedback_structured'),
                        'label'   => get_string('continue')
                    );
                }
                // Capitalise the initial letter.
                $updates['name'] = ucfirst($value);

                // The name must also be globally unique.
                $namelc = strtolower($value);
                if ($criteriaset = $DB->get_record('assignfeedback_structured_cs', array('name_lc' => $namelc), 'id')) {
                    if ($criteriaset->id != $criteriasetid) {
                        return array(
                            'success' => false,
                            'title'   => get_string('criteriasetnameusedtitle', 'assignfeedback_structured'),
                            'body'    => get_string('criteriasetnameused', 'assignfeedback_structured', $value),
                            'label'   => get_string('continue')
                        );
                    }
                }
                $updates = array_merge($updates, array('name_lc' => $namelc));
            }
        }

        $assignment = new assign($context, null, null);
        $feedback = new assign_feedback_structured($assignment, 'structured');

        if ($feedback->update_criteria_set($criteriasetid, $updates)) {
            return array(
                'success' => true,
                'title'   => get_string('criteriasetupdated', 'assignfeedback_structured'),
                'body'    => get_string('criteriasetupdatedsuccess', 'assignfeedback_structured'),
                'label'   => get_string('ok')
            );
        }

        return array(
            'success' => false,
            'title'   => get_string('error'),
            'body'    => get_string('criteriasetnotupdated', 'assignfeedback_structured'),
            'label'   => get_string('continue')
        );
    }

    /**
     * Return a description of the result value for the update_criteriaset method.
     *
     * @return external_description
     */
    public static function update_criteriaset_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Whether or not the criteria set was successfully updated'),
                'title'   => new external_value(PARAM_TEXT, 'The title of the message to display to the user'),
                'body'    => new external_value(PARAM_TEXT, 'The body text of the message to display to the user'),
                'label'   => new external_value(PARAM_TEXT, 'The button label for the message dialogue displayed to the user')
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
