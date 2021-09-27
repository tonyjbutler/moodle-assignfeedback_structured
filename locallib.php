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

// File component for structured feedback.
define('ASSIGNFEEDBACK_STRUCTURED_COMPONENT', 'assignfeedback_structured');

// File area for structured feedback.
define('ASSIGNFEEDBACK_STRUCTURED_FILEAREA', 'feedback');

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
     * @var string The JSON encoded criteria configured for this plugin instance.
     */
    private $criteria = '';

    /**
     * Get the name of the structured feedback plugin.
     *
     * @return string The name of the plugin.
     */
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_structured');
    }

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
     * Cache and return the JSON encoded criteria configured for this assignment instance.
     *
     * @return string The configured criteria.
     */
    private function get_criteria_config() {
        if ($this->criteria) {
            return $this->criteria;
        }

        if ($criteria = $this->get_config('criteria')) {
            $this->criteria = $criteria;
        }

        return $this->criteria;
    }

    /**
     * Fetch the saved criteria set with the given id.
     *
     * @param int $criteriasetid The id of the criteria set to fetch.
     * @return stdClass|bool The criteria set or false.
     */
    private function get_criteria_set($criteriasetid) {
        global $DB;

        // A criteria set id must be provided.
        if (empty($criteriasetid)) {
            return false;
        }

        return $DB->get_record('assignfeedback_structured_cs', array('id' => $criteriasetid));
    }

    /**
     * Return the criteria for the given criteria set id, or those configured for this assignment instance.
     *
     * @param int $criteriasetid The id of a saved criteria set (optional).
     * @return array The criteria data.
     */
    public function get_criteria($criteriasetid = 0) {
        if (!empty($criteriasetid)) {
            if (!$criteriaset = $this->get_criteria_set($criteriasetid)) {
                return array();
            }
            $criteria = $criteriaset->criteria;
        } else {
            if (!$criteria = $this->get_criteria_config()) {
                return array();
            }
        }

        return json_decode($criteria);
    }

    /**
     * Check whether the criterion with the given key has any feedback associated with it.
     *
     * @param int $criterion Key of criterion to check.
     * @return bool True if any feedback exists.
     */
    private function is_criterion_used($criterion) {
        global $DB;

        $assignment = $this->assignment->get_instance()->id;

        return $DB->record_exists('assignfeedback_structured', array('assignment' => $assignment, 'criterion' => $criterion));
    }

    /**
     * Return all saved criteria sets that the current user can manage (or copy into this assignment instance).
     *
     * @param bool $includeshared Include shared criteria sets owned by other users (for copying only).
     * @return array|string Grouped array of criteria sets, or an error string.
     */
    public function get_criteria_sets_for_user($includeshared = false) {
        global $DB, $USER;

        // Return an error if user is copying, and any criteria are configured and have feedback already.
        if ($criteria = $this->get_criteria() and $includeshared) {
            foreach ($criteria as $key => $criterion) {
                if ($this->is_criterion_used($key)) {
                    return get_string('criteriaused', 'assignfeedback_structured');
                }
            }
        }

        $criteriasets = array();
        $params = array('user' => $USER->id);

        $select = "name <> ''";
        if (!has_capability('moodle/site:config', context_system::instance()) || $includeshared) {
            $select .= " AND owner = :user";
        }
        $ownedsets = $DB->get_records_select('assignfeedback_structured_cs', $select, $params, 'name', 'id, name, shared');
        if ($ownedsets) {
            foreach ($ownedsets as $ownedset) {
                $ownedset->shared = (bool) $ownedset->shared;
            }
            $criteriasets['ownedsets'] = array_values($ownedsets);
        }

        if ($includeshared) {
            $select = "name <> '' AND owner <> :user AND shared = 1";
            $sharedsets = $DB->get_records_select('assignfeedback_structured_cs', $select, $params, 'name', 'id, name');
            if ($sharedsets) {
                $criteriasets['sharedsets'] = array_values($sharedsets);
            }
        }

        return $criteriasets;
    }

    /**
     * Save a new named criteria set to copy later, using the data provided.
     *
     * @param string $name A name for the new criteria set.
     * @param array $criteria The criteria data.
     * @param bool $shared Whether the new criteria set should be shared.
     * @return bool Success status.
     */
    public function save_criteria_set($name, $criteria, $shared) {
        global $DB, $PAGE, $USER;

        // Make sure user has the appropriate permissions to save.
        if (!has_capability('assignfeedback/structured:manageowncriteriasets', $PAGE->context)) {
            return false;
        }

        // Write the new criteria set to the database.
        $criteriaset = new stdClass();
        $criteriaset->name = ucfirst($name);
        $criteriaset->name_lc = strtolower($name);
        $criteriaset->criteria = json_encode($criteria);
        $criteriaset->owner = $USER->id;
        $criteriaset->shared = $shared;

        return $DB->insert_record('assignfeedback_structured_cs', $criteriaset);
    }

    /**
     * Update one or more criteria set attributes (e.g. name, visibility) with the new values provided.
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

        return $DB->update_record('assignfeedback_structured_cs', $criteriaset);
    }

    /**
     * Delete the saved criteria set with the given id.
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

        return $DB->delete_records('assignfeedback_structured_cs', array('id' => $criteriasetid));
    }

    /**
     * Get the structured feedback comments from the database.
     *
     * @param int $gradeid The grade id.
     * @return array An array of feedback comments for the given grade, indexed by criterion key.
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
     * Has the structured feedback been modified?
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data Data from the form submission.
     * @return bool True if the structured feedback has been modified, else false.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        $keys = array_keys($criteria);
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        foreach ($keys as $key) {
            $commenttext = !empty($feedbackcomments[$key]) ? $feedbackcomments[$key]->commenttext : '';

            $editor = 'assignfeedbackstructured' . $key . '_editor';
            $formtext = $data->{$editor}['text'];

            // Need to convert the form text to use @@PLUGINFILE@@ so we can compare it with what is stored in the DB.
            if (isset($data->{$editor}['itemid'])) {
                $formtext = file_rewrite_urls_to_pluginfile($formtext, $data->{$editor}['itemid']);
            }

            if ($commenttext != $formtext) {
                return true;
            }
        }

        return false;
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
            if (!empty($criterion->description)) {
                $description = get_string('criterionheader', 'assignfeedback_structured',
                        ['name' => $criterion->name, 'desc' => $criterion->description]);
            } else {
                $description = $criterion->name;
            }
            $fields[$criterion->name] = $description;
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

        foreach ($criteria as $key => $criterion) {
            if ($name == $criterion->name) {
                $commenttext = !empty($feedbackcomments[$key]) ? $feedbackcomments[$key]->commenttext : '';
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

        foreach ($criteria as $key => $criterion) {
            if ($name == $criterion->name) {
                if (!empty($feedbackcomments[$key])) {
                    $feedbackcomments[$key]->commenttext = $value;
                    return $DB->update_record('assignfeedback_structured', $feedbackcomments[$key]);
                } else {
                    $feedbackcomment = new stdClass();
                    $feedbackcomment->criterion = $key;
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
     * Override to indicate a plugin supports quickgrading.
     *
     * @return bool True if the plugin supports quickgrading.
     */
    public function supports_quickgrading() {
        return true;
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
        foreach ($criteria as $key => $criterion) {
            $critname = $criterion->name;
            $critdesc = $criterion->description;
            $commenttext = !empty($feedbackcomments[$key]) ? $feedbackcomments[$key]->commenttext : '';
            $labeloptions = array('for' => 'quickgrade_structured_' . $key . '_' . $userid);
            $textareaoptions = array(
                    'name'        => 'quickgrade_structured_' . $key . '_' . $userid,
                    'id'          => 'quickgrade_structured_' . $key . '_' . $userid,
                    'placeholder' => $critdesc,
                    'class'       => 'quickgrade'
            );
            $html .= html_writer::tag('label', $critname, $labeloptions);
            $html .= html_writer::empty_tag('br');
            $html .= html_writer::tag('textarea', $commenttext, $textareaoptions);
            $html .= html_writer::empty_tag('br');
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
        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        $keys = array_keys($criteria);
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        foreach ($keys as $key) {
            $commenttext = !empty($feedbackcomments[$key]) ? $feedbackcomments[$key]->commenttext : '';

            // Note that this handles the difference between empty and not in the quickgrading form at all (hidden column).
            $newvalue = optional_param('quickgrade_structured_' . $key . '_' . $userid, false, PARAM_RAW);
            if ($newvalue !== false && $newvalue != $commenttext) {
                return true;
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

        foreach ($criteria as $key => $criterion) {
            $quickgradecomment = optional_param('quickgrade_structured_' . $key . '_' . $userid, null, PARAM_RAW);
            if (!$quickgradecomment) {
                continue;
            }

            if (!empty($feedbackcomments[$key])) {
                $feedbackcomments[$key]->commenttext = $quickgradecomment;
                $DB->update_record('assignfeedback_structured', $feedbackcomments[$key]);
            } else {
                $feedbackcomment = new stdClass();
                $feedbackcomment->criterion = $key;
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
        $criteria = array();

        foreach ($data->assignfeedback_structured_critname as $key => $critname) {
            $critname = trim($critname);
            // Ignore unnamed criteria.
            if (empty($critname)) {
                continue;
            }
            $criterion = new stdClass();
            $criterion->name = $critname;
            $criterion->description = trim($data->assignfeedback_structured_critdesc[$key]);
            $criteria[] = $criterion;
        }

        if (!empty($criteria)) {
            // Update the config if criteria have changed.
            if ($criteria != $this->get_criteria()) {
                $this->set_config('criteria', json_encode($criteria));
            }
        } else {
            // If no criteria are configured, unset the config and disable the plugin instance.
            if ($this->get_criteria()) {
                $this->set_config('criteria', '');
            }
            $this->disable();
        }

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
                'contextid'  => $PAGE->context->id,
                'manage'     => false,
                'canpublish' => false
            ), $criteriasets);
            $PAGE->requires->js_call_amd('assignfeedback_structured/criteriasets', 'init', $params);
        } else {
            $mform->updateElementAttr('assignfeedback_structured_critset',
                    array('title' => $criteriasets, 'disabled' => 'disabled'));
            $criterialocked = true;
        }

        $critelements = array();
        $critelements[] = $mform->createElement('text', 'assignfeedback_structured_critname',
                get_string('criterionname', 'assignfeedback_structured'), array('size' => '64', 'maxlength' => '255'));
        $critelements[] = $mform->createElement('textarea', 'assignfeedback_structured_critdesc',
                get_string('criteriondesc', 'assignfeedback_structured'), 'rows="4" cols="64"');

        if ($criteria = $this->get_criteria()) {
            $mform->setExpanded('assignfeedback_structured_criteria', true);
            if (!empty($criterialocked)) {
                $critrepeats = count($criteria);
            } else {
                $critrepeats = count($criteria) + 2;
            }
        } else {
            $critrepeats = 5;
        }

        $critoptions = array();
        $critoptions['assignfeedback_structured_critname']['rule'] = array(null, 'maxlength', 255, 'client');
        $critoptions['assignfeedback_structured_critname']['type'] = PARAM_TEXT;
        $critoptions['assignfeedback_structured_critdesc']['type'] = PARAM_TEXT;

        $critfields = $this->repeat_elements($mform, $critelements, $critrepeats, $critoptions, 'assignfeedback_structured_repeats',
                'assignfeedback_structured_critfieldsadd', 3, get_string('criteriafieldsadd', 'assignfeedback_structured'), true);
        $lastfield = 'assignfeedback_structured_critname[' . ($critfields - 1) . ']';
        $mform->disabledIf('assignfeedback_structured_critfieldsadd', $lastfield, 'eq', '');
        $mform->addHelpButton('assignfeedback_structured_critname[0]', 'criteria', 'assignfeedback_structured');
        if (!empty($criterialocked)) {
            $mform->updateElementAttr('assignfeedback_structured_critfieldsadd',
                    array('title' => get_string('criteriaused', 'assignfeedback_structured'), 'disabled' => 'disabled'));
        }

        if (has_capability('assignfeedback/structured:manageowncriteriasets', $PAGE->context)) {
            // Enable users to save criteria sets for use in other assignments.
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

            // Enable users to manage their saved criteria sets.
            $criteriasetsmanagebutton = $mform->addElement('button', 'assignfeedback_structured_critsetsmanage',
                    get_string('criteriasetsmanage', 'assignfeedback_structured'));
            $criteriasetsmanagebutton->setLabel(get_string('criteriasetsmanage', 'assignfeedback_structured'));
            $mform->addHelpButton('assignfeedback_structured_critsetsmanage', 'criteriasetsmanage', 'assignfeedback_structured');
            $mform->setAdvanced('assignfeedback_structured_critsetsmanage');
            $params = array(
                'contextid'  => $PAGE->context->id,
                'manage'     => true,
                'canpublish' => has_capability('assignfeedback/structured:publishcriteriasets', $PAGE->context)
            );
            if ($criteriasets = $this->get_criteria_sets_for_user(false)) {
                $params = array_merge($params, $criteriasets);
            } else {
                $mform->updateElementAttr('assignfeedback_structured_critsetsmanage', array('disabled' => 'disabled'));
            }
            $PAGE->requires->js_call_amd('assignfeedback_structured/criteriasets', 'init', $params);
        }

        // If this is not the last feedback plugin, add a section to contain the settings for the rest.
        if (!$this->is_last()) {
            $mform->addElement('header', 'feedbacksettings', get_string('feedbacksettings', 'assign'));
        }

        // Pre-populate fields with existing data and lock as appropriate.
        $elements = array();
        foreach ($criteria as $key => $criterion) {
            $mform->setDefault('assignfeedback_structured_critname[' . $key . ']', $criterion->name);
            $mform->setDefault('assignfeedback_structured_critdesc[' . $key . ']', $criterion->description);
            if (!empty($criterialocked)) {
                $elements[] = 'assignfeedback_structured_critname[' . $key . ']';
                $elements[] = 'assignfeedback_structured_critdesc[' . $key . ']';
            }
        }
        if (!empty($criterialocked) && !empty($elements)) {
            $mform->freeze($elements);
            $mform->updateElementAttr($elements, array('title' => get_string('criteriaused', 'assignfeedback_structured')));
        }
    }

    /**
     * Helper used by {@see \assign_feedback_structured::repeat_elements()}.
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

        foreach ($criteria as $key => $criterion) {
            $field = 'assignfeedbackstructured' . $key;
            $editor = $field . '_editor';
            // Check first for data from last form submission in case grading validation failed.
            if (!empty($data->{$editor}['text'])) {
                $data->{$field} = $data->{$editor}['text'];
                $data->{$field . 'format'} = $data->{$editor}['format'];
            } else if (!empty($feedbackcomments[$key]) && !empty($feedbackcomments[$key]->commenttext)) {
                $data->{$field} = $feedbackcomments[$key]->commenttext;
                $data->{$field . 'format'} = $feedbackcomments[$key]->commentformat;
            } else { // Set it to empty.
                $data->{$field} = '';
                $data->{$field . 'format'} = FORMAT_HTML;
            }
            file_prepare_standard_editor(
                $data,
                $field,
                $this->get_editor_options(),
                $this->get_context(),
                ASSIGNFEEDBACK_STRUCTURED_COMPONENT,
                ASSIGNFEEDBACK_STRUCTURED_FILEAREA,
                $grade->id
            );
            $editorlabel = get_string('criteriontitle', 'assignfeedback_structured',
                    ['name' => $criterion->name, 'desc' => $criterion->description]);
            $mform->addElement('editor', $editor, $editorlabel, null, $this->get_editor_options());

            // Remove any merged draft files belonging to other editors from the current editor's draft area.
            file_remove_editor_orphaned_files($data->{$editor});
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
        global $DB, $USER;

        if (!$criteria = $this->get_criteria()) {
            return false;
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        foreach ($criteria as $key => $criterion) {
            $field = 'assignfeedbackstructured' . $key;
            if ($key > 0) {
                // Merge any draft files from the previous editor into the current one to prevent erroneous deletions.
                $getfromdraftid = $data->{'assignfeedbackstructured' . ($key - 1) . '_editor'}['itemid'];
                if (!empty($fs->get_area_files($usercontext->id, 'user', 'draft', $getfromdraftid))) {
                    $mergeintodraftid = $data->{$field . '_editor'}['itemid'];
                    file_merge_draft_area_into_draft_area($getfromdraftid, $mergeintodraftid);
                }
            }
            $data = file_postupdate_standard_editor(
                $data,
                $field,
                $this->get_editor_options(),
                $this->get_context(),
                ASSIGNFEEDBACK_STRUCTURED_COMPONENT,
                ASSIGNFEEDBACK_STRUCTURED_FILEAREA,
                $grade->id
            );
            if (!empty($feedbackcomments[$key]) && $feedbackcomments[$key]->commenttext != $data->{$field}) {
                $feedbackcomments[$key]->commenttext = $data->{$field};
                $feedbackcomments[$key]->commentformat = $data->{$field . 'format'};
                $DB->update_record('assignfeedback_structured', $feedbackcomments[$key]);
            } else if (!empty($data->{$field})) {
                $feedbackcomment = new stdClass();
                $feedbackcomment->criterion = $key;
                $feedbackcomment->commenttext = $data->{$field};
                $feedbackcomment->commentformat = $data->{$field . 'format'};
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
        foreach ($criteria as $key => $criterion) {
            if (!empty($feedbackcomments[$key]) && !empty($feedbackcomments[$key]->commenttext)) {
                $desc = format_text($criterion->description, FORMAT_PLAIN, ['context' => $this->get_context()]);
                $crit = get_string('criteriontitle', 'assignfeedback_structured',
                        ['name' => format_string($criterion->name), 'desc' => '']);
                $commenttext = $this->rewrite_feedback_comments_urls($feedbackcomments[$key]->commenttext, $grade->id);
                $comment = format_text($commenttext, $feedbackcomments[$key]->commentformat, ['context' => $this->get_context()]);
                $text .= html_writer::div($crit, '', ['title' => $desc]);
                $text .= html_writer::div($comment, 'well-small');
            }
        }

        // Show the view all link if the text has been shortened.
        $short = shorten_text($text, 140);
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
        foreach ($criteria as $key => $criterion) {
            if (!empty($feedbackcomments[$key]) && !empty($feedbackcomments[$key]->commenttext)) {
                $desc = format_text($criterion->description, FORMAT_PLAIN, ['context' => $this->get_context()]);
                $crit = get_string('criteriontitle', 'assignfeedback_structured',
                        ['name' => format_string($criterion->name), 'desc' => $desc]);
                $commenttext = $this->rewrite_feedback_comments_urls($feedbackcomments[$key]->commenttext, $grade->id);
                $comment = format_text($commenttext, $feedbackcomments[$key]->commentformat, ['context' => $this->get_context()]);
                $text .= html_writer::div($crit);
                $text .= html_writer::div($comment, 'card p-1');
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
            foreach ($criteria as $key => $criterion) {
                if (!empty($feedbackcomments[$key]) && !empty($feedbackcomments[$key]->commenttext)) {
                    $commenttext = $this->rewrite_feedback_comments_urls($feedbackcomments[$key]->commenttext, $grade->id);
                    $description = format_text($criterion->description, FORMAT_PLAIN, array('context' => $this->get_context()));
                    $comment = format_text($commenttext, $feedbackcomments[$key]->commentformat,
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
        $fsfiles = $fs->get_area_files($this->get_context()->id, ASSIGNFEEDBACK_STRUCTURED_COMPONENT,
                ASSIGNFEEDBACK_STRUCTURED_FILEAREA, $grade->id, 'timemodified', false);
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

        // Will throw exception on failure.
        $DB->delete_records('assignfeedback_structured', array('assignment' => $this->assignment->get_instance()->id));

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
     * Should the structured feedback plugin include a column in the grading table or a row on the summary page?
     *
     * @return bool True if any criteria are defined.
     */
    public function has_user_summary() {
        if (empty($this->get_criteria())) {
            return false;
        }

        return true;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must specify the format of the text of the comment.
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment settings page.
     *
     * @param stdClass $grade The grade
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        return FORMAT_HTML;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must format the text of the comment.
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment settings page.
     *
     * @param stdClass $grade The grade
     * @return string
     */
    public function text_for_gradebook(stdClass $grade) {
        if (!$criteria = $this->get_criteria()) {
            return '';
        }

        $feedbackcomments = $this->get_feedback_comments($grade->id);

        $text = '';
        foreach ($criteria as $key => $criterion) {
            if (!empty($feedbackcomments[$key]) && !empty($feedbackcomments[$key]->commenttext)) {
                $desc = format_text($criterion->description, FORMAT_PLAIN, ['context' => $this->get_context()]);
                $crit = get_string('criteriontitle', 'assignfeedback_structured',
                        ['name' => format_string($criterion->name), 'desc' => $desc]);
                $comment = format_text($feedbackcomments[$key]->commenttext, $feedbackcomments[$key]->commentformat,
                        ['context' => $this->get_context()]);
                $text .= html_writer::div($crit);
                $text .= html_writer::div($comment, 'card p-1');
            }
        }

        return $text;
    }

    /**
     * Return any files this plugin wishes to save to the gradebook.
     *
     * @param stdClass $grade The assign_grades object from the db
     * @return array
     */
    public function files_for_gradebook(stdClass $grade) : array {
        return [
            'contextid' => $this->get_context()->id,
            'component' => ASSIGNFEEDBACK_STRUCTURED_COMPONENT,
            'filearea' => ASSIGNFEEDBACK_STRUCTURED_FILEAREA,
            'itemid' => $grade->id
        ];
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
        foreach ($criteria as $key => $criterion) {
            $editorparams = array(
                'text' => new external_value(PARAM_RAW, 'The comment text for criterion: ' . $criterion->name),
                'format' => new external_value(PARAM_INT, 'The comment format for criterion: ' . $criterion->name)
            );
            $editorstructure = new external_single_structure($editorparams, 'Criterion ' . $key . ' editor structure',
                    VALUE_OPTIONAL);
            $editors['assignfeedbackstructured' . $key . '_editor'] = $editorstructure;
        }

        return $editors;
    }

    /**
     * Return the plugin config for external functions.
     *
     * @return array The list of settings.
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }

    /**
     * Convert encoded URLs in $text from the @@PLUGINFILE@@/... form to an actual URL.
     *
     * @param string $text The text to check
     * @param int $gradeid The grade ID which refers to the id in the gradebook
     * @return string
     */
    private function rewrite_feedback_comments_urls(string $text, int $gradeid) {
        return file_rewrite_pluginfile_urls(
            $text,
            'pluginfile.php',
            $this->get_context()->id,
            ASSIGNFEEDBACK_STRUCTURED_COMPONENT,
            ASSIGNFEEDBACK_STRUCTURED_FILEAREA,
            $gradeid
        );
    }

    /**
     * File format options.
     *
     * @return array
     */
    private function get_editor_options() {
        global $COURSE;

        return [
            'subdirs' => 1,
            'maxbytes' => $COURSE->maxbytes,
            'accepted_types' => '*',
            'context' => $this->get_context(),
            'maxfiles' => EDITOR_UNLIMITED_FILES
        ];
    }
}
