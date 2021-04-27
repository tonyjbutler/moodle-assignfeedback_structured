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
 * This file contains the backup class for the structured feedback subplugin.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup an assignfeedback_structured subplugin instance.
 *
 * This just records the text and format of structured feedback comments.
 *
 * @package   assignfeedback_structured
 * @copyright 2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tony Butler <a.butler4@lancaster.ac.uk>
 */
class backup_assignfeedback_structured_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to feedback element.
     * @return backup_subplugin_element
     */
    protected function define_grade_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginelementcomments = new backup_nested_element('feedback_structured');
        $subpluginelementcomment = new backup_nested_element('structured_comment', array('id'),
                array('grade', 'criterion', 'commenttext', 'commentformat'));

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginelementcomments);
        $subpluginelementcomments->add_child($subpluginelementcomment);

        // Set source to populate the data.
        $subpluginelementcomment->set_source_table('assignfeedback_structured', array('grade' => backup::VAR_PARENTID));

        $subpluginelementcomment->annotate_files(
            'assignfeedback_structured',
            'feedback',
            'grade'
        );

        return $subplugin;
    }
}
