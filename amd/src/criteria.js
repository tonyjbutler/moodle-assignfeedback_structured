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
 * Displays the criteria data for the selected criteria set in the structured feedback criteria set selector modal.
 *
 * See template: assignfeedback_structured/criteria
 *
 * @module     assignfeedback_structured/criteria
 * @class      criteria
 * @copyright  2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

import Templates from 'core/templates';

/**
 * Init function.
 *
 * @param {Object[]} criteriaData An array of data objects for the criteria returned.
 */
export const init = async(criteriaData) => {
    const context = {
        criteriaData: criteriaData
    };

    await Templates.render('assignfeedback_structured/criteria', context);
};
