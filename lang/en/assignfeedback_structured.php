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
 * Strings for component 'assignfeedback_structured', language 'en'.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */

$string['criteria'] = 'Feedback criteria';
$string['criteria_help'] = 'Enter a name and description for each criterion you want to provide feedback for. Any unnamed criteria will be ignored. Fields will be locked once there is feedback for a particular criterion.';
$string['criteriafieldsadd'] = 'Add more criteria fields';
$string['criteriaused'] = 'This criteria set cannot be edited because one of more of its criteria already have feedback';
$string['criteriaset'] = 'Criteria set';
$string['criteriaset_help'] = 'You may either click this button to select and copy a previously saved criteria set, or define a custom set of criteria below. If you copy a saved set, any criteria already defined below will be overwritten.';
$string['criteriasetconfirmdelete'] = 'Are you sure you want to permanently delete the saved criteria set "{$a}"?';
$string['criteriasetlocked'] = 'You cannot edit this criteria set because it was defined and saved by another user';
$string['criteriasetname'] = 'Criteria set name';
$string['criteriasetname_help'] = 'The name of a criteria set must be unique across the whole site, so please choose it carefully.';
$string['criteriasetnameused'] = 'Unfortunately this name is already used for another criteria set, and must be unique across the whole site. Please try a different name.';
$string['criteriasetnotdeleted'] = 'Unfortunately the criteria set "{$a}" could not be deleted. Please try again or report this error to your administrator.';
$string['criteriasetnotowned'] = 'You cannot save this criteria set because it was defined by another user';
$string['criteriasetpublic'] = 'Make available to other users';
$string['criteriasetpublic_help'] = 'Tick this box to enable anyone to make a copy of this criteria set. By default it is available only to the user who created it.';
$string['criteriasetsave'] = 'Save criteria set';
$string['criteriasetsave_help'] = 'Tick this box and provide a unique name for your criteria set to enable it to be copied easily into other assignments. Once it is saved, other users will be unable to edit its criteria.<br><br>Please note that you can only save your own criteria sets (unless you have been granted a special permission).';
$string['criteriasetselect'] = 'Select a criteria set ...';
$string['criteriasetsmanage'] = 'Manage criteria sets';
$string['criteriasetsmanage_help'] = 'Click this button to view and manage your own saved criteria sets.';
$string['criteriasetsowned'] = 'Your criteria sets';
$string['criteriasetspublic'] = 'Shared criteria sets';
$string['criteriasetuse'] = 'Use this criteria set';
$string['criteriasetusesaved'] = 'Use a saved criteria set';
$string['criteriondesc'] = 'Criterion {$a} description';
$string['criterionname'] = 'Criterion {$a} name';
$string['criteriontitle'] = '{$a->name}: <span style="font-style: italic;">{$a->desc}</span>';
$string['criterionused'] = 'This criterion cannot be edited because it already has feedback';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this feedback method will be enabled by default for all new assignments.';
$string['enabled'] = 'Structured feedback';
$string['enabled_help'] = 'If enabled, the teacher will be able to define any number of criteria on which to provide specific feedback.';
$string['nopermissionstodelete'] = 'Sorry, but you do not currently have permissions to delete your saved criteria sets.';
$string['noownedsets'] = 'You don\'t have any saved criteria sets';
$string['nopublicsets'] = 'There are no shared criteria sets';
$string['pluginname'] = 'Structured feedback';
$string['structured:editanycriteriaset'] = 'Edit/save criteria sets owned by other users';
$string['structured:manageowncriteriasets'] = 'Manage own saved criteria sets';
$string['structured:publishcriteriasets'] = 'Make saved criteria sets available to other users';
$string['structuredfilename'] = 'structured.html';
