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
 * Processes the data submitted by the structured feedback plugin's criteria set selector modal.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/feedback/structured/locallib.php');

require_login($COURSE);
$pageurl = new moodle_url('/mod/assign/feedback/structured/criteriaset.php');
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('embedded');

$context = required_param('context', PARAM_INT);
$criteriaset = required_param('criteriaset', PARAM_INT);

$assignment = new assign($context, null, null);
$feedback = new assign_feedback_structured($assignment, 'structured');

// Get the criteria data for the selected criteria set.
if ($criteriaset) {
    $criteria = $feedback->get_criteria($criteriaset);
}

if (!empty($criteria)) {
    echo $OUTPUT->header();

    // Call the module that renders the template to display the criteria data.
    $PAGE->requires->js_call_amd('assignfeedback_structured/criteria', 'init', array(array_values($criteria)));

    // Build the config data to populate the criteria fields, then call the config module.
    $config = $feedback->build_criteria_config($criteria);
    $PAGE->requires->js_call_amd('assignfeedback_structured/criteriaconfig', 'init', array($config));

    echo $OUTPUT->footer();
}
