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
 * A Javascript module to handle the criteria set 'save' modal for the structured feedback plugin.
 *
 * @module     assignfeedback_structured/cssavemodal
 * @copyright  2024 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

import CustomEvents from 'core/custom_interaction_events';
import Modal from 'core/modal';
import {saveSet} from './criteriasetsave';

/**
 * @class
 * @extends module:core/modal
 */
export default class CSSaveModal extends Modal {
    static TYPE = 'assignfeedback_structured/cssavemodal';
    static TEMPLATE = 'core/modal_save_cancel';

    /**
     * Configure the criteria set 'save' modal.
     *
     * @param {Object} modalConfig The modal configuration.
     */
    configure(modalConfig) {
        super.configure({
            ...modalConfig,
            show: true,
            removeOnClose: true,
        });
    }

    /**
     * Handle click events within the criteria sets 'save' modal.
     */
    registerEventListeners() {
        // Call the parent registration.
        super.registerEventListeners();

        // Register to close on cancel.
        this.registerCloseOnCancel();

        // Save criteria set on save.
        this.getModal().on(CustomEvents.events.activate, this.getActionSelector('save'), async(e) => {
            e.preventDefault();
            await saveSet(this);
        });

        // Hide 'loading' spinner on cancel.
        this.getModal().on(CustomEvents.events.activate, this.getActionSelector('cancel'), async() => {
            const spinner = document.querySelector('#id_assignfeedback_structured_critsetsave')
                .parentNode.querySelector('.loading-icon');
            if (spinner) {
                spinner.style.display = 'none';
            }
        });
    }
}
