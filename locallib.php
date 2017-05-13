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
 * This file contains the definition for the library class for the structured feedback plugin.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

// File area for structured feedback.
define('ASSIGNFEEDBACK_STRUCTURED_FILEAREA', 'feedback_structured');

/**
 * Library class for structured feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */
class assign_feedback_structured extends assign_feedback_plugin {

    /**
     * @var context The assignment context for this plugin instance.
     */
    private $context = null;

    /**
     * @var \context_course The course context for this plugin instance.
     */
    private $coursecontext = null;

    /**
     * @var int The id of the criteria set configured for this plugin instance.
     */
    private $criteriasetid = 0;

    /**
     * Cache and return the assignment context for this plugin instance.
     *
     * @return context
     */
    private function get_context() {
        if (isset($this->context)) {
            return $this->context;
        }

        return $this->assignment->get_context();
    }

    /**
     * Get the name of the structured feedback plugin.
     *
     * @return string The name of the plugin.
     */
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_structured');
    }

    /**
     * Get the structured feedback comments from the database.
     *
     * @param int $gradeid The grade id.
     * @return array An array of feedback comments for the given grade, indexed by criterion id.
     */
    public function get_feedback_comments($gradeid) {
        global $DB;

        if (!$comments = $DB->get_records('assignfeedback_structured', array('grade' => $gradeid))) {
            return array();
        }

        $feedback = array();
        foreach ($comments as $comment) {
            $feedback[$comment->criterion] = $comment;
        }

        return $feedback;
    }

    /**
     * Cache and return the id of the criteria set configured for this plugin instance.
     *
     * @return int The criteria set id.
     */
    private function get_criteria_set_id() {
        if ($this->criteriasetid) {
            return $this->criteriasetid;
        }

        if ($criteriasetid = $this->get_config('criteriaset')) {
            $this->criteriasetid = $criteriasetid;
        }

        return $this->criteriasetid;
    }

    /**
     * Return all saved criteria sets that the current user can manage (or copy into this assignment instance).
     *
     * @param bool $includepublic Include shared criteria sets owned by other users (for copying only).
     * @return array|string Grouped array of criteria sets, or an error string.
     */
    public function get_criteria_sets_for_user($includepublic = false) {
        global $DB, $USER;

        // Return an error if any criteria are defined and have feedback already.
        if ($criteria = $this->get_criteria() and $includepublic) {
            foreach ($criteria as $criterion) {
                if ($this->is_criterion_used($criterion->id)) {
                    return get_string('criteriaused', 'assignfeedback_structured');
                }
            }
        }

        $criteriasets = array();
        $params = array('user' => $USER->id);

        $select = "name <> ''";
        if (!has_capability('moodle/site:config', context_system::instance()) || $includepublic) {
            $select .= " AND owner = :user";
        }
        $ownedsets = $DB->get_records_select('assignfeedback_structured_cs', $select, $params, 'name', 'id, name');
        if ($ownedsets) {
            $criteriasets['ownedSets'] = array_values($ownedsets);
        }

        if ($includepublic) {
            $select = "name <> '' AND owner <> :user AND public = 1";
            $publicsets = $DB->get_records_select('assignfeedback_structured_cs', $select, $params, 'name', 'id, name');
            if ($publicsets) {
                $criteriasets['publicSets'] = array_values($publicsets);
            }
        }

        return $criteriasets;
    }

    /**
     * Save a new named criteria set to copy later, using the data provided.
     *
     * @param string $name A name for the new criteria set.
     * @param array $criteria The criteria data.
     * @param bool $public Whether the new criteria set should be shared.
     * @return bool Success status.
     */
    public function save_criteria_set($name, $criteria, $public) {
        global $DB, $PAGE, $USER;

        // Make sure user has the appropriate permissions to save.
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $PAGE->context)) {
            return false;
        }

        // Save criteria and get ids.
        $critids = array();
        foreach ($criteria as $criterion) {
            $crit = new stdClass();
            $crit->name = $criterion['name'];
            $crit->description = $criterion['description'];
            if ($critid = $DB->insert_record('assignfeedback_structured_cr', $crit)) {
                $critids[] = $critid;
            }
        }
        if (!$critids) {
            return false;
        }

        // Write the new criteria set to the database.
        $criteriaset = new stdClass();
        $criteriaset->name = $name;
        $criteriaset->criteria = implode(',', $critids);
        $criteriaset->owner = $USER->id;
        $criteriaset->public = $public;
        if (!$DB->insert_record('assignfeedback_structured_cs', $criteriaset)) {
            return false;
        }

        return true;
    }

    /**
     * Save a new named criteria set to copy later, using the data provided.
     *
     * @param int $criteriasetid The id of the criteria set to be updated.
     * @param array $updates The key/value pairs of attributes to be updated.
     * @return bool Success status.
     */
    public function update_criteria_set($criteriasetid, $updates) {
        global $DB, $PAGE;

        // A criteria set id must be provided.
        if (empty($criteriasetid)) {
            return false;
        }

        // Make sure user has the appropriate permissions to update.
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $PAGE->context)) {
            return false;
        }

        // Write the updates to the database.
        $criteriaset = new stdClass();
        $criteriaset->id = $criteriasetid;
        foreach ($updates as $key => $value) {
            $criteriaset->$key = $value;
        }
        if (!$DB->update_record('assignfeedback_structured_cs', $criteriaset)) {
            return false;
        }

        return true;
    }

    /**
     * Delete the saved criteria set with the given id, provided it isn't used in an assignment.
     *
     * @param int $criteriasetid The id of the criteria set to be deleted.
     * @return bool Success status.
     */
    public function delete_criteria_set($criteriasetid) {
        global $DB, $PAGE, $USER;

        // A criteria set id must be provided.
        if (empty($criteriasetid)) {
            return false;
        }

        // Don't delete the criteria set if it is used in an assignment.
        $select = "plugin = :type AND subtype = :subtype AND name = :name AND value = :value";
        $params = array(
            'type'    => $this->get_type(),
            'subtype' => $this->get_subtype(),
            'name'    => 'criteriaset',
            'value'   => $criteriasetid
        );
        if ($DB->record_exists_select('assign_plugin_config', $select, $params)) {
            return false;
        }

        if (!$criteriaset = $this->get_criteria_set($criteriasetid)) {
            return false;
        }

        // Make sure user has the appropriate permissions to delete.
        if (!has_capability('moodle/site:config', context_system::instance())) {
            if ($criteriaset->owner != $USER->id ||
                    !has_capability('assignfeedback/structured:manageowncriteriasets', $PAGE->context)) {
                return false;
            }
        }

        // Delete the criteria set and its criteria.
        $DB->delete_records('assignfeedback_structured_cs', array('id' => $criteriasetid));
        $criterionids = explode(',', $criteriaset->criteria);
        $DB->delete_records_list('assignfeedback_structured_cr', 'id', $criterionids);

        return true;
    }

    /**
     * Return the criteria set matching the given id, or the set already configured for this plugin instance.
     *
     * @param int $criteriasetid The id of a criteria set to return.
     * @return stdClass|bool The criteria set or false.
     */
    private function get_criteria_set($criteriasetid = 0) {
        global $DB;

        if (!$criteriasetid) {
            if (!$criteriasetid = $this->get_criteria_set_id()) {
                return false;
            }
        }

        return $DB->get_record('assignfeedback_structured_cs', array('id' => $criteriasetid));
    }

    /**
     * Return the criteria for the given criteria set id, or those already configured for this plugin instance.
     *
     * @param int $criteriasetid The id of a criteria set.
     * @return array Correctly ordered array of criterion records.
     */
    public function get_criteria($criteriasetid = 0) {
        global $DB;

        if (!$criteriaset = $this->get_criteria_set($criteriasetid)) {
            return array();
        }

        $criterionids = explode(',', $criteriaset->criteria);
        list($select, $params) = $DB->get_in_or_equal($criterionids);
        if (!$criteria = $DB->get_records_select('assignfeedback_structured_cr', 'id ' . $select, $params)) {
            return array();
        }

        // Return them in the right order.
        return array_replace(array_flip($criterionids), $criteria);
    }

    /**
     * Get quickgrading form elements as html.
     *
     * @param int $userid The user id in the table this quickgrading element relates to.
     * @param stdClass|null $grade The grade data - may be null if there are no grades for this user (yet).
     * @return string An html string containing the html form elements required for quickgrading.
     */
    public function get_quickgrading_html($userid, $grade) {
        if (!$criteria = $this->get_criteria()) {
            return '';
        }

        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        $html = '';
        foreach ($criteria as $critid => $criterion) {
            $critname = $criterion->name;
            $critdesc = $criterion->description;
            $commenttext = !empty($feedbackcomments[$critid]) ? $feedbackcomments[$critid]->commenttext : '';
            $labeloptions = array('for' => 'quickgrade_structured_' . $critid . '_' . $userid);
            $textareaoptions = array(
                'name'  => 'quickgrade_structured_' . $critid . '_' . $userid,
                'id'    => 'quickgrade_structured_' . $critid . '_' . $userid,
                'title' => $critdesc,
                'class' => 'quickgrade'
            );
            $html .= html_writer::tag('label', $critname, $labeloptions);
            $html .= html_writer::tag('textarea', $commenttext, $textareaoptions);
        }

        return $html;
    }

    /**
     * Has the plugin quickgrading form element been modified in the current form submission?
     *
     * @param int $userid The user id in the table this quickgrading element relates to.
     * @param stdClass $grade The grade object.
     * @return bool True if the quickgrading form element has been modified, else false.
     */
    public function is_quickgrading_modified($userid, $grade) {
        if (!$criteriaset = $this->get_criteria_set()) {
            return false;
        }

        $criterionids = explode(',', $criteriaset->criteria);
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        foreach ($criterionids as $critid) {
            $commenttext = !empty($feedbackcomments[$critid]) ? $feedbackcomments[$critid]->commenttext : '';

            // Note that this handles the difference between empty and not in the quickgrading form at all (hidden column).
            $newvalue = optional_param('quickgrade_structured_' . $critid . '_' . $userid, false, PARAM_RAW);
            if ($newvalue !== false && $newvalue != $commenttext) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has the structured feedback been modified?
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data Data from the form submission.
     * @return bool True if the structured feedback has been modified, else false.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        if (!$criteriaset = $this->get_criteria_set()) {
            return false;
        }

        $criterionids = explode(',', $criteriaset->criteria);
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        foreach ($criterionids as $critid) {
            $commenttext = !empty($feedbackcomments[$critid]) ? $feedbackcomments[$critid]->commenttext : '';

            $editor = 'assignfeedbackstructured_editor_' . $critid;
            if ($commenttext != $data->{$editor}['text']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the criterion with the given id has any feedback associated with it.
     *
     * @param $criterionid
     * @return bool True if any feedback exists.
     */
    private function is_criterion_used($criterionid) {
        global $DB;

        return $DB->record_exists('assignfeedback_structured', array('criterion' => $criterionid));
    }

    /**
     * Override to indicate a plugin supports quickgrading.
     *
     * @return bool True if the plugin supports quickgrading.
     */
    public function supports_quickgrading() {
        return true;
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin.
     *
     * @return array An array of field names and descriptions (name => description, ... ).
     */
    public function get_editor_fields() {
        if (!$criteria = $this->get_criteria()) {
            return array();
        }

        $fields = array();
        foreach ($criteria as $criterion) {
            $fields[$criterion->name] = $criterion->description;
        }

        return $fields;
    }

    /**
     * Get the saved text content for the editor.
     *
     * @param string $name The field name.
     * @param int $gradeid The grade id.
     * @return string The text content.
     */
    public function get_editor_text($name, $gradeid) {
        if (!$criteria = $this->get_criteria()) {
            return '';
        }

        $feedbackcomments = $this->get_feedback_comments($gradeid);

        foreach ($criteria as $critid => $criterion) {
            if ($name == $criterion->name) {
                $commenttext = !empty($feedbackcomments[$critid]) ? $feedbackcomments[$critid]->commenttext : '';
                return $commenttext;
            }
        }

        return '';
    }

    /**
     * Save the text content from the editor.
     *
     * @param string $name The field name.
     * @param string $value The text content.
     * @param int $gradeid The grade id.
     * @return bool Success status.
     */
    public function set_editor_text($name, $value, $gradeid) {
        global $DB;

        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        $feedbackcomments = $this->get_feedback_comments($gradeid);

        foreach ($criteria as $critid => $criterion) {
            if ($name == $criterion->name) {
                if (!empty($feedbackcomments[$critid])) {
                    $feedbackcomments[$critid]->commenttext = $value;
                    return $DB->update_record('assignfeedback_structured', $feedbackcomments[$critid]);
                } else {
                    $feedbackcomment = new stdClass();
                    $feedbackcomment->criterion = $critid;
                    $feedbackcomment->commenttext = $value;
                    $feedbackcomment->commentformat = FORMAT_HTML;
                    $feedbackcomment->grade = $gradeid;
                    $feedbackcomment->assignment = $this->assignment->get_instance()->id;
                    return $DB->insert_record('assignfeedback_structured', $feedbackcomment) > 0;
                }
            }
        }

        return false;
    }

    /**
     * Save quickgrading changes.
     *
     * @param int $userid The user id in the table this quickgrading element relates to.
     * @param stdClass $grade The grade.
     * @return bool True if the grade changes were saved correctly.
     */
    public function save_quickgrading_changes($userid, $grade) {
        global $DB;

        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        foreach ($criteria as $critid => $criterion) {
            $quickgradecomment = optional_param('quickgrade_structured_' . $critid . '_' . $userid, null, PARAM_RAW);
            if (!$quickgradecomment) {
                continue;
            }

            if (!empty($feedbackcomments[$critid])) {
                $feedbackcomments[$critid]->commenttext = $quickgradecomment;
                $DB->update_record('assignfeedback_structured', $feedbackcomments[$critid]);
            } else {
                $feedbackcomment = new stdClass();
                $feedbackcomment->criterion = $critid;
                $feedbackcomment->commenttext = $quickgradecomment;
                $feedbackcomment->commentformat = FORMAT_HTML;
                $feedbackcomment->grade = $grade->id;
                $feedbackcomment->assignment = $this->assignment->get_instance()->id;
                $DB->insert_record('assignfeedback_structured', $feedbackcomment);
            }
        }

        return true;
    }

    /**
     * Save the settings for the structured feedback plugin instance.
     *
     * @param stdClass $data The data from the config form.
     * @return bool True.
     */
    public function save_settings(stdClass $data) {
        global $DB, $USER;

        // Update any existing criteria or create new ones.
        $criteria = $this->get_criteria();
        $critids = array();
        foreach ($data->assignfeedback_structured_critname as $key => $critname) {
            $critname = trim($critname);
            // Ignore unnamed criteria.
            if (empty($critname)) {
                continue;
            }
            $critdesc = trim($data->assignfeedback_structured_critdesc[$key]);
            if (!empty($criteria) && in_array($data->assignfeedback_structured_critid[$key], array_keys($criteria))) {
                $critid = $data->assignfeedback_structured_critid[$key];
                if ($criteria[$critid]->name != $critname || $criteria[$critid]->description != $critdesc) {
                    $criteria[$critid]->name = $critname;
                    $criteria[$critid]->description = $critdesc;
                    $DB->update_record('assignfeedback_structured_cr', $criteria[$critid]);
                }
                $critids[] = $critid;
                unset($criteria[$critid]);
            } else {
                $criterion = new stdClass();
                $criterion->name = $critname;
                $criterion->description = $critdesc;
                if ($critid = $DB->insert_record('assignfeedback_structured_cr', $criterion)) {
                    $critids[] = $critid;
                }
            }
        }

        // Clean up any remaining (unneeded) criteria records.
        if (!empty($criteria)) {
            $DB->delete_records_list('assignfeedback_structured_cr', 'id', array_keys($criteria));
        }

        // Update existing criteria set or create a new one.
        if (!empty($critids)) {
            $criteria = implode(',', $critids);
            if ($criteriaset = $this->get_criteria_set()) {
                $criteriaset->criteria = $criteria;
                if ($DB->update_record('assignfeedback_structured_cs', $criteriaset)) {
                    return true;
                }
            }
            $criteriaset = new stdClass();
            $criteriaset->name = '';
            $criteriaset->criteria = $criteria;
            $criteriaset->owner = $USER->id;
            $criteriaset->public = false;
            if ($criteriaset->id = $DB->insert_record('assignfeedback_structured_cs', $criteriaset)) {
                $this->set_config('criteriaset', $criteriaset->id);
            }
            return true;
        }

        // Delete criteria set if no longer needed.
        if ($criteriaset = $this->get_criteria_set()) {
            $DB->delete_records('assignfeedback_structured_cs', array('id' => $criteriaset->id));
            $this->set_config('criteriaset', 0);
        }

        // If nothing is configured disable the plugin.
        $this->disable();

        return true;
    }

    /**
     * Get the settings for the structured feedback plugin instance.
     *
     * @param MoodleQuickForm $mform The form to add elements to.
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $PAGE;

        $mform->addElement('header', 'assignfeedback_structured_criteria', get_string('criteria', 'assignfeedback_structured'));

        $criteriasetbutton = $mform->addElement('button', 'assignfeedback_structured_critset',
                get_string('criteriasetusesaved', 'assignfeedback_structured'));
        $criteriasetbutton->setLabel(get_string('criteriaset', 'assignfeedback_structured'));
        $mform->addHelpButton('assignfeedback_structured_critset', 'criteriaset', 'assignfeedback_structured');

        // Check if there are any saved criteria sets that can be used here.
        if (is_array($criteriasets = $this->get_criteria_sets_for_user(true))) {
            $params = array_merge(array(
                'contextid' => $PAGE->context->id,
                'manage'    => false
            ), $criteriasets);
            $PAGE->requires->js_call_amd('assignfeedback_structured/criteriasets', 'init', $params);
        } else {
            $mform->updateElementAttr('assignfeedback_structured_critset',
                    array('title' => $criteriasets, 'disabled' => 'disabled'));
        }

        $critelements = array();
        $critelements[] = $mform->createElement('text', 'assignfeedback_structured_critname',
                get_string('criterionname', 'assignfeedback_structured'), array('size' => '64', 'maxlength' => '255'));
        $critelements[] = $mform->createElement('textarea', 'assignfeedback_structured_critdesc',
                get_string('criteriondesc', 'assignfeedback_structured'), 'rows="4" cols="64"');
        $critelements[] = $mform->createElement('hidden', 'assignfeedback_structured_critid', 0);

        if ($criteria = $this->get_criteria()) {
            $mform->setExpanded('assignfeedback_structured_criteria', true);
            $critrepeats = count($criteria) + 2;
        } else {
            $critrepeats = 5;
        }

        $critoptions = array();
        $critoptions['assignfeedback_structured_critname']['rule'] = array(null, 'maxlength', 255, 'client');
        $critoptions['assignfeedback_structured_critname']['type'] = PARAM_TEXT;
        $critoptions['assignfeedback_structured_critdesc']['type'] = PARAM_TEXT;
        $critoptions['assignfeedback_structured_critid']['type'] = PARAM_INT;

        $critfields = $this->repeat_elements($mform, $critelements, $critrepeats, $critoptions, 'assignfeedback_structured_repeats',
                'assignfeedback_structured_critfieldsadd', 3, get_string('criteriafieldsadd', 'assignfeedback_structured'), true);
        $lastfield = 'assignfeedback_structured_critname[' . ($critfields - 1) . ']';
        $mform->disabledIf('assignfeedback_structured_critfieldsadd', $lastfield, 'eq', '');
        $mform->addHelpButton('assignfeedback_structured_critname[0]', 'criteria', 'assignfeedback_structured');

        if (has_capability('assignfeedback/structured:manageowncriteriasets', $PAGE->context)) {
            // Enable teachers to save criteria sets for use in other assignments.
            $criteriasetsavebutton = $mform->addElement('button', 'assignfeedback_structured_critsetsave',
                    get_string('criteriasetsave', 'assignfeedback_structured'));
            $criteriasetsavebutton->setLabel(get_string('criteriasetsave', 'assignfeedback_structured'));
            $mform->addHelpButton('assignfeedback_structured_critsetsave', 'criteriasetsave', 'assignfeedback_structured');
            $mform->setAdvanced('assignfeedback_structured_critsetsave');
            $mform->disabledIf('assignfeedback_structured_critsetsave', 'assignfeedback_structured_critname[0]', 'eq', '');
            $params = array(
                'contextid'  => $PAGE->context->id,
                'canpublish' => has_capability('assignfeedback/structured:publishcriteriasets', $PAGE->context)
            );
            $PAGE->requires->js_call_amd('assignfeedback_structured/criteriasetsave', 'init', $params);

            // Enable teachers to manage their saved criteria sets.
            $criteriasetsmanagebutton = $mform->addElement('button', 'assignfeedback_structured_critsetsmanage',
                    get_string('criteriasetsmanage', 'assignfeedback_structured'));
            $criteriasetsmanagebutton->setLabel(get_string('criteriasetsmanage', 'assignfeedback_structured'));
            $mform->addHelpButton('assignfeedback_structured_critsetsmanage', 'criteriasetsmanage', 'assignfeedback_structured');
            $mform->setAdvanced('assignfeedback_structured_critsetsmanage');
            if ($criteriasets = $this->get_criteria_sets_for_user(false)) {
                $params = array_merge(array(
                    'contextid' => $PAGE->context->id,
                    'manage'    => true
                ), $criteriasets);
                $PAGE->requires->js_call_amd('assignfeedback_structured/criteriasets', 'init', $params);
            } else {
                $mform->updateElementAttr('assignfeedback_structured_critsetsmanage', array('disabled' => 'disabled'));
            }
        }

        // If this is not the last feedback plugin, add a section to contain the settings for the rest.
        if (!$this->is_last()) {
            $mform->addElement('header', 'feedbacksettings', get_string('feedbacksettings', 'assign'));
        }

        // Pre-populate fields with existing data and lock as appropriate.
        $criteria = array_values($criteria);
        foreach ($criteria as $index => $criterion) {
            $mform->setDefault('assignfeedback_structured_critname[' . $index . ']', $criterion->name);
            $mform->setDefault('assignfeedback_structured_critdesc[' . $index . ']', $criterion->description);
            $mform->setDefault('assignfeedback_structured_critid[' . $index . ']', $criterion->id);
            if ($this->is_criterion_used($criterion->id)) {
                $elements = array(
                    'assignfeedback_structured_critname[' . $index . ']',
                    'assignfeedback_structured_critdesc[' . $index . ']'
                );
                $mform->freeze($elements);
                $mform->updateElementAttr($elements, array('title' => get_string('criterionused', 'assignfeedback_structured')));
            }
        }
    }

    /**
     * Helper used by {@link repeat_elements()}.
     *
     * @param int $i the index of this element.
     * @param HTML_QuickForm_element $elementclone
     * @param array $namecloned array of names
     */
    private function repeat_elements_fix_clone($i, $elementclone, &$namecloned) {
        $name = $elementclone->getName();
        $namecloned[] = $name;

        if (!empty($name)) {
            $elementclone->setName($name."[$i]");
        }

        if (is_a($elementclone, 'HTML_QuickForm_header')) {
            $value = $elementclone->_text;
            $elementclone->setValue(str_replace('{$a}', ($i + 1), $value));
        } else if (is_a($elementclone, 'HTML_QuickForm_submit') || is_a($elementclone, 'HTML_QuickForm_button')) {
            $elementclone->setValue(str_replace('{$a}', ($i + 1), $elementclone->getValue()));
        } else {
            $value = $elementclone->getLabel();
            $elementclone->setLabel(str_replace('{$a}', ($i + 1), $value));
        }
    }

    /**
     * Method to add a repeating group of elements to a form.
     *
     * @param MoodleQuickForm $mform The form to add elements to.
     * @param array $elementobjs Array of elements or groups of elements that are to be repeated
     * @param int $repeats no of times to repeat elements initially
     * @param array $options a nested array. The first array key is the element name.
     *    the second array key is the type of option to set, and depend on that option,
     *    the value takes different forms.
     *         'default'    - default value to set. Can include '{$a}' which is replaced by the repeat number.
     *         'type'       - PARAM_* type.
     *         'helpbutton' - array containing the helpbutton params.
     *         'disabledif' - array containing the disabledIf() arguments after the element name.
     *         'rule'       - array containing the addRule arguments after the element name.
     *         'expanded'   - whether this section of the form should be expanded by default. (Name be a header element.)
     *         'advanced'   - whether this element is hidden by 'Show more ...'.
     * @param string $repeathiddenname name for hidden element storing no of repeats in this form
     * @param string $addfieldsname name for button to add more fields
     * @param int $addfieldsno how many fields to add at a time
     * @param string $addstring name of button, {$a} is replaced by no of blanks that will be added.
     * @param bool $addbuttoninside if true, don't call closeHeaderBefore($addfieldsname). Default false.
     * @return int no of repeats of element in this page
     */
    private function repeat_elements(&$mform, $elementobjs, $repeats, $options, $repeathiddenname, $addfieldsname, $addfieldsno = 5,
                                     $addstring = null, $addbuttoninside = false) {
        if ($addstring === null) {
            $addstring = get_string('addfields', 'form', $addfieldsno);
        } else {
            $addstring = str_ireplace('{$a}', $addfieldsno, $addstring);
        }
        $repeats = optional_param($repeathiddenname, $repeats, PARAM_INT);
        $addfields = optional_param($addfieldsname, '', PARAM_TEXT);
        if (!empty($addfields)) {
            $repeats += $addfieldsno;
        }
        $mform->registerNoSubmitButton($addfieldsname);
        $mform->addElement('hidden', $repeathiddenname, $repeats);
        $mform->setType($repeathiddenname, PARAM_INT);
        // Value not to be overridden by submitted value.
        $mform->setConstants(array($repeathiddenname => $repeats));
        $namecloned = array();
        for ($i = 0; $i < $repeats; $i++) {
            foreach ($elementobjs as $elementobj) {
                $elementclone = fullclone($elementobj);
                $this->repeat_elements_fix_clone($i, $elementclone, $namecloned);

                if ($elementclone instanceof HTML_QuickForm_group && !$elementclone->_appendName) {
                    foreach ($elementclone->getElements() as $el) {
                        $this->repeat_elements_fix_clone($i, $el, $namecloned);
                    }
                    $elementclone->setLabel(str_replace('{$a}', $i + 1, $elementclone->getLabel()));
                }

                $mform->addElement($elementclone);
            }
        }
        for ($i = 0; $i < $repeats; $i++) {
            foreach ($options as $elementname => $elementoptions) {
                $pos = strpos($elementname, '[');
                if ($pos !== false) {
                    $realelementname = substr($elementname, 0, $pos) . "[$i]";
                    $realelementname .= substr($elementname, $pos);
                } else {
                    $realelementname = $elementname . "[$i]";
                }
                foreach ($elementoptions as $option => $params) {
                    switch ($option){
                        case 'default':
                            $mform->setDefault($realelementname, str_replace('{$a}', $i + 1, $params));
                            break;
                        case 'helpbutton':
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addHelpButton'), $params);
                            break;
                        case 'disabledif':
                            foreach ($namecloned as $num => $name) {
                                if ($params[0] == $name) {
                                    $params[0] = $params[0] . "[$i]";
                                    break;
                                }
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'disabledIf'), $params);
                            break;
                        case 'rule':
                            if (is_string($params)) {
                                $params = array(null, $params, null, 'client');
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addRule'), $params);
                            break;

                        case 'type':
                            $mform->setType($realelementname, $params);
                            break;

                        case 'expanded':
                            $mform->setExpanded($realelementname, $params);
                            break;

                        case 'advanced':
                            $mform->setAdvanced($realelementname, $params);
                            break;
                    }
                }
            }
        }
        $mform->addElement('submit', $addfieldsname, $addstring);

        if (!$addbuttoninside) {
            $mform->closeHeaderBefore($addfieldsname);
        }

        return $repeats;
    }

    /**
     * Get form elements for the grading page.
     *
     * @param stdClass|null $grade The grade object.
     * @param MoodleQuickForm $mform The form to add elements to.
     * @param stdClass $data The feedback data.
     * @param int $userid Unused param from parent method.
     * @return bool True if elements were added to the form.
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        foreach ($criteria as $critid => $criterion) {
            $editor = 'assignfeedbackstructured_editor_' . $critid;
            if (!empty($feedbackcomments[$critid]) && !empty($feedbackcomments[$critid]->commenttext)) {
                $data->{$editor}['text'] = $feedbackcomments[$critid]->commenttext;
                $data->{$editor}['format'] = $feedbackcomments[$critid]->commentformat;
            }
            $mform->addElement('editor', $editor, get_string('criteriontitle', 'assignfeedback_structured',
                    array('name' => $criterion->name, 'desc' => $criterion->description)));
            $mform->setType($editor, PARAM_RAW);
        }

        return true;
    }

    /**
     * Save the feedback content to the database.
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data The feedback data.
     * @return bool Success status.
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;

        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        foreach ($criteria as $critid => $criterion) {
            $editor = 'assignfeedbackstructured_editor_' . $critid;
            if (!empty($feedbackcomments[$critid]) && $feedbackcomments[$critid]->commenttext != $data->{$editor}['text']) {
                $feedbackcomments[$critid]->commenttext = $data->{$editor}['text'];
                $feedbackcomments[$critid]->commentformat = $data->{$editor}['format'];
                $DB->update_record('assignfeedback_structured', $feedbackcomments[$critid]);
            } else if (!empty($data->{$editor}['text'])) {
                $feedbackcomment = new stdClass();
                $feedbackcomment->criterion = $critid;
                $feedbackcomment->commenttext = $data->{$editor}['text'];
                $feedbackcomment->commentformat = $data->{$editor}['format'];
                $feedbackcomment->grade = $grade->id;
                $feedbackcomment->assignment = $this->assignment->get_instance()->id;
                $DB->insert_record('assignfeedback_structured', $feedbackcomment);
            }
        }

        return true;
    }

    /**
     * Display the feedback in the feedback summary.
     *
     * @param stdClass $grade The grade object.
     * @param bool $showviewlink Set to true to show a link to view the full feedback.
     * @return string The feedback to display.
     */
    public function view_summary(stdClass $grade, &$showviewlink) {
        if (!$criteria = $this->get_criteria()) {
            return '';
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        $text = '';
        foreach ($criteria as $critid => $criterion) {
            if (!empty($feedbackcomments[$critid]) && !empty($feedbackcomments[$critid]->commenttext)) {
                $desc = format_text($criterion->description, FORMAT_PLAIN, array('context' => $this->get_context()));
                $crit = get_string('criteriontitle', 'assignfeedback_structured',
                        array('name' => format_string($criterion->name), 'desc' => ''));
                $comment = format_text($feedbackcomments[$critid]->commenttext, $feedbackcomments[$critid]->commentformat,
                        array('context' => $this->get_context()));
                $text .= html_writer::div($crit, '', array('title' => $desc));
                $text .= html_writer::div($comment, 'well-small');
            }
        }
        $short = shorten_text($text, 140);

        // Show the view all link if the text has been shortened.
        $showviewlink = $short != $text;

        return $short;
    }

    /**
     * Display the feedback in the feedback table.
     *
     * @param stdClass $grade The grade object.
     * @return string The feedback to display.
     */
    public function view(stdClass $grade) {
        if (!$criteria = $this->get_criteria()) {
            return '';
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        $text = '';
        foreach ($criteria as $critid => $criterion) {
            if (!empty($feedbackcomments[$critid]) && !empty($feedbackcomments[$critid]->commenttext)) {
                $desc = format_text($criterion->description, FORMAT_PLAIN, array('context' => $this->get_context()));
                $crit = get_string('criteriontitle', 'assignfeedback_structured',
                        array('name' => format_string($criterion->name), 'desc' => $desc));
                $comment = format_text($feedbackcomments[$critid]->commenttext, $feedbackcomments[$critid]->commentformat,
                        array('context' => $this->get_context()));
                $text .= html_writer::div($crit);
                $text .= html_writer::div($comment, 'well');
            }
        }

        return $text;
    }

    /**
     * Produce a list of files suitable for export that represent this feedback.
     *
     * @param stdClass $grade The user grade.
     * @param stdClass $user The user record.
     * @return array An array of files indexed by filename.
     */
    public function get_files(stdClass $grade, stdClass $user) {
        if (!$criteria = $this->get_criteria()) {
            return array();
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        $files = array();

        if ($feedbackcomments) {
            $formattedtext = html_writer::tag('h2', get_string('pluginname', 'assignfeedback_structured'),
                    array('style' => 'font-family: Arial, sans-serif;'));
            foreach ($criteria as $critid => $criterion) {
                if (!empty($feedbackcomments[$critid]) && !empty($feedbackcomments[$critid]->commenttext)) {
                    $commenttext = $this->assignment->download_rewrite_pluginfile_urls($feedbackcomments[$critid]->commenttext,
                            $user, $this);
                    $description = format_text($criterion->description, FORMAT_PLAIN, array('context' => $this->get_context()));
                    $comment = format_text($commenttext, $feedbackcomments[$critid]->commentformat,
                            array('context' => $this->get_context()));
                    $formattedtext .= html_writer::tag('h3', format_string($criterion->name),
                            array('style' => 'font-family: Arial, sans-serif;'));
                    $formattedtext .= html_writer::tag('div', $description, array('style' => 'font-style: italic;'));
                    $formattedtext .= html_writer::tag('p', $comment);
                }
            }
            $head = '<head><meta charset="UTF-8"></head>';
            $body = '<body style="font-family: Arial, sans-serif; font-size: 14px;">' . $formattedtext . '</body>';
            $feedbackcontent = '<!DOCTYPE html><html>' . $head . $body . '</html>';
            $filename = get_string('structuredfilename', 'assignfeedback_structured');
            $files[$filename] = array($feedbackcontent);
        }

        $fs = get_file_storage();
        $fsfiles = $fs->get_area_files($this->get_context()->id, 'assignfeedback_structured', ASSIGNFEEDBACK_STRUCTURED_FILEAREA,
                $grade->id, 'timemodified', false);
        foreach ($fsfiles as $file) {
            $files[$file->get_filename()] = $file;
        }

        return $files;
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type Old assignment subtype.
     * @param int $version Old assignment version.
     * @return bool True if upgrade is possible.
     */
    public function can_upgrade($type, $version) {
        return false;
    }

    /**
     * The assignment has been deleted - cleanup.
     *
     * @return bool True.
     */
    public function delete_instance() {
        global $DB;

        // Delete comments first.
        $DB->delete_records('assignfeedback_structured', array('assignment' => $this->assignment->get_instance()->id));

        // Then delete criteria set and criteria.
        if ($criteriaset = $this->get_criteria_set()) {
            $DB->delete_records('assignfeedback_structured_cs', array('id' => $criteriaset->id));
            $criterionids = explode(',', $criteriaset->criteria);
            $DB->delete_records_list('assignfeedback_structured_cr', 'id', $criterionids);
        }

        return true;
    }

    /**
     * Automatically enable the structured feedback plugin.
     *
     * @return bool True.
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Automatically hide the checkbox for the structured feedback plugin.
     *
     * @return bool False.
     */
    public function is_configurable() {
        return false;
    }

    /**
     * Returns true if there is no structured feedback for the given grade.
     *
     * @param stdClass $grade The grade object.
     * @return bool True if no feedback.
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }

    /**
     * Return a description of external params suitable for uploading structured feedback from a webservice.
     *
     * @return array Description of external params.
     */
    public function get_external_parameters() {
        if (!$criteria = $this->get_criteria()) {
            return array();
        }

        $editors = array();
        foreach ($criteria as $critid => $criterion) {
            $editorparams = array(
                'text' => new external_value(PARAM_RAW, 'The comment text for criterion: ' . $criterion->name),
                'format' => new external_value(PARAM_INT, 'The comment format for criterion: ' . $criterion->name)
            );
            $editorstructure = new external_single_structure($editorparams, 'Criterion ' . $critid . ' editor structure',
                    VALUE_OPTIONAL);
            $editors['assignfeedbackstructured_editor_' . $critid] = $editorstructure;
        }

        return $editors;
    }

}
