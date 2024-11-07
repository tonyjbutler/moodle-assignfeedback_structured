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
 * Launches a modal dialogue that enables users to save the configured criteria set for the structured feedback plugin.
 *
 * See template: assignfeedback_structured/criteriasetsave
 *
 * @module     assignfeedback_structured/criteriasetsave
 * @class      criteriasetsave
 * @copyright  2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';
import Templates from 'core/templates';
import {modalActive} from './criteriasets';
import CSSaveModal from './cssavemodal';

/**
 * Init function.
 *
 * @param {number} contextId The context ID of the current assignment instance.
 * @param {boolean} canPublish Whether the current user can publish criteria sets.
 * @return {Promise} A promise.
 */
export const init = async(contextId, canPublish) => {
    const title = await getString('criteriasetsave', 'assignfeedback_structured'),
        button = document.querySelector('#id_assignfeedback_structured_critsetsave');
    button.addEventListener('click', async() => {
        const templateContext = {
            contextId: contextId,
            canPublish: canPublish,
        };
        const body = await Templates.render('assignfeedback_structured/criteriasetsave', templateContext);
        const modal = await CSSaveModal.create({
            title: title,
            body: body,
        });

        // Display a spinner while the modal is active.
        await modalActive(button);

        const modalFooter = await modal.getFooter(),
            modalBody = await modal.getBody(),
            nameInput = modalBody.find('[name="criteriaset-name"]'),
            shareBox = modalBody.find('[name="criteriaset-publish"]'),
            saveButton = modalFooter.find(modal.getActionSelector('save'));

        // Disable save button initially, and hide the loading icon.
        modalFooter.find(modal.getActionSelector('save')).prop('disabled', true);
        modalBody.find('.loading-icon').hide();

        // Enable save button only if the name field contains some text.
        nameInput.on('keyup blur', () => {
            if (nameInput.val().trim()) {
                saveButton.prop('disabled', false);
            } else {
                saveButton.prop('disabled', true);
            }
        });

        // Fix a weird bug when hitting Enter on the name input field.
        nameInput.on('keydown', (e) => {
            if (e.keyCode === 13) {
                e.preventDefault();
                shareBox.focus();
            }
        });

        // Fix same weird bug when hitting Enter on the 'share' checkbox.
        shareBox.on('keydown', (e) => {
            if (e.keyCode === 13) {
                e.preventDefault();
                saveButton.focus();
            }
        });

        // Fix duplicate submission when hitting Enter on the save button.
        saveButton.on('keydown', (e) => {
            if (e.keyCode === 13) {
                e.preventDefault();
            }
        });
    });
};

/**
 * Function to gather the criteria data and call a web service method via AJAX to save as a named criteria set.
 *
 * @param {Object} modal The modal dialogue containing the criteria set save data.
 * @return {Promise} A promise.
 */
export const saveSet = async(modal) => {
    const modalRoot = await modal.getRoot(),
        contextId = modalRoot.find('.criteriasetsave-page').data('context'),
        nameInput = modalRoot.find('[name="criteriaset-name"]'),
        rawName = nameInput.val().trim(),
        spinner = modalRoot.find('.loading-icon');
    let shared = false;

    const name = rawName.charAt(0).toUpperCase() + rawName.slice(1);
    nameInput.val(name);
    if (modalRoot.find('[name="criteriaset-publish"]').prop('checked')) {
        shared = true;
    }
    spinner.show();

    const criteria = [];
    for (const name of document.querySelectorAll('input[id^="id_assignfeedback_structured_critname"]')) {
        if (name.value.trim().length) {
            const descField = name.parentElement.parentElement.nextElementSibling
                .querySelector('[name^="assignfeedback_structured_critdesc"]');
            let descText = '';
            if (descField.value) {
                descText = descField.value.trim();
            } else if (descField.textContent) {
                descText = descField.textContent.trim();
            }
            criteria.push({
                name: name.value.trim(),
                description: descText,
            });
        }
    }

    const request = Ajax.call([{
        methodname: 'assignfeedback_structured_save_criteriaset',
        args: {
            contextid: contextId,
            name: name,
            criteria: criteria,
            shared: shared,
        },
    }]);

    const response = await request[0];
    if (response.hide === true) {
        modal.destroy();
        document.querySelector('#id_assignfeedback_structured_critsetsmanage').disabled = false;
    }
    await Notification.alert(response.title, response.body, response.label);
    spinner.hide();
};
