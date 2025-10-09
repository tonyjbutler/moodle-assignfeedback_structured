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
 * Enables editing of feedback criteria by enabling the structured feedback plugin for the current assignment.
 *
 * @module     assignfeedback_structured/criteriaenable
 * @class      criteriaenable
 * @copyright  2025 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

/**
 * Toggles 'assignfeedback_structured_enabled' checkbox.
 */
export const toggle = () => {
    const toggleButtons = document.querySelectorAll('#id_assignfeedback_structured_enable, #id_assignfeedback_structured_disable'),
        enabledCheckbox = document.getElementById('id_assignfeedback_structured_enabled');
    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            enabledCheckbox.click();
        });
    });
};

/**
 * Disables 'assignfeedback_structured_enabled' checkbox.
 */
export const disable = () => {
    document.getElementById('id_assignfeedback_structured_enabled').disabled = true;
};
