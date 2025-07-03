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
 * Launches a modal dialogue that enables users to manage their criteria sets for the structured feedback plugin.
 *
 * See template: assignfeedback_structured/criteriasets
 *
 * @module     assignfeedback_structured/criteriasets
 * @class      criteriasets
 * @copyright  2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

import Ajax from 'core/ajax';
import CSModal from './csmodal';
import Notification from 'core/notification';
import {getStrings} from 'core/str';
import Templates from 'core/templates';

/**
 * Init function.
 *
 * @param {number} contextId The context ID of the current assignment instance.
 * @param {boolean} manage Whether a full management interface is required (otherwise it's read only).
 * @param {boolean} canPublish Whether the current user can publish criteria sets.
 * @param {Object[]} ownedSets An array of data objects for all saved criteria sets owned by the current user.
 * @param {Object[]} sharedSets An array of data objects for any other available criteria sets.
 * @return {Promise} A promise.
 */
export const init = async(contextId, manage, canPublish, ownedSets, sharedSets) => {
    const strings = await getStrings([
        {key: 'criteriasetssaved', component: 'assignfeedback_structured'},
        {key: 'criteriasetsmanage', component: 'assignfeedback_structured'}
    ]);
    let title = strings[0],
        button = document.querySelector('#id_assignfeedback_structured_critset');
    if (manage) {
        title = strings[1];
        button = document.querySelector('#id_assignfeedback_structured_critsetsmanage');
    }
    button.addEventListener('click', async() => {
        const templateContext = {
            contextId: contextId,
            manage: manage,
            canPublish: canPublish,
            ownedSets: ownedSets,
            sharedSets: sharedSets,
        };
        const modal = await CSModal.create({
            title: title,
            templateContext: templateContext,
        });
        const modalFooter = await modal.getFooter();
        const refreshButton = modalFooter.find(modal.getActionSelector('refresh'));
        refreshButton.on('click', async() => {
            await refreshSets(modal, contextId, manage, canPublish);
        });
        // Refresh automatically when showing the modal.
        await refreshSets(modal, contextId, manage, canPublish);

        // Display a spinner while the modal is active.
        if (!manage) {
            await modalActive(button);
        }
    });
};

/**
 * Function to call a web service method via AJAX to update the list of criteria sets.
 *
 * @param {Object} modal The modal dialogue to be refreshed.
 * @param {number} contextId The context ID of the current assignment instance.
 * @param {boolean} manage Whether the modal provides a management interface.
 * @param {boolean} canPublish Whether the current user can publish criteria sets.
 * @return {Promise} A promise.
 */
const refreshSets = async(modal, contextId, manage, canPublish) => {
    const modalBody = await modal.getBody(),
        modalFooter = await modal.getFooter(),
        refreshButton = modalFooter.find(modal.getActionSelector('refresh')),
        buttonWidth = refreshButton.width(),
        spinner = modalFooter.find('.loading-icon');

    refreshButton.hide();
    spinner.css({marginLeft: buttonWidth / 2 + 'px', marginRight: buttonWidth / 2 + 'px'});
    spinner.show();

    const request = Ajax.call([{
        methodname: 'assignfeedback_structured_get_criteriasets',
        args: {
            contextid: contextId,
            includeshared: !manage,
        },
    }]);
    const response = await request[0];
    const context = {
        contextId: contextId,
        manage: manage,
        canPublish: canPublish,
        ownedSets: response.ownedsets,
        sharedSets: response.sharedsets,
    };
    const {html, js} = await Templates.renderForPromise('assignfeedback_structured/criteriasets_body', context);
    Templates.replaceNodeContents(modalBody, html, js);
    modalBody.find('.loading-icon').hide();
    spinner.hide();
    refreshButton.show();

    // Set initial display of shared criteria sets.
    const showShared = modalFooter.find(modal.getActionSelector('showshared')),
        sharedSets = modalBody.find('.criteriasets-shared');
    toggleShared(showShared, sharedSets);

    // Toggle display of shared sets on checkbox click.
    showShared.on('click', () => {
        toggleShared(showShared, sharedSets);
    });

    // Get criteria data when expanded for the first time.
    const criteriaToggle = modalBody.find('[data-bs-toggle="collapse"][href^="#criteria-data-"]');
    criteriaToggle.on('click', async function() {
        if (!this.classList.contains('collapsed')) {
            const set = this.parentNode.parentNode;
            const spinner = this.parentNode.querySelector('.loading-icon');
            spinner.style.display = 'initial';
            await getCriteria(set);
            spinner.style.display = 'none';
        }
    });

    // Edit criteria set name on button click.
    modalBody.find('[name="criteriaset-newname"]').hide();
    const editButton = modalBody.find('[data-set-action="editname"]');
    editButton.on('click keydown', function(e) {
        if (e.type === 'click' || e.keyCode === 32) {
            e.preventDefault();
            const set = this.parentNode.parentNode,
                setName = set.dataset.criteriasetName,
                nameElement = this.parentNode.querySelector('[data-bs-toggle="collapse"]'),
                nameInput = this.parentNode.querySelector('[name="criteriaset-newname"]');

            // Hide name and button and show input field.
            this.style.display = 'none';
            nameElement.style.display = 'none';
            nameInput.value = setName;
            nameInput.style.display = 'initial';
            nameInput.focus();
            nameInput.select();

            // On blur or enter, hide input field and set the new name (if changed); abort name change on escape.
            ['blur', 'keydown'].forEach((type) => {
                nameInput.addEventListener(type, nameChangeHandler);
            });
        }
    });

    // Change shared visibility of a criteria set.
    const sharedBox = modalBody.find('input[data-set-action="shared"]');
    sharedBox.on('change', async function() {
        const spinner = this.parentNode.parentNode.querySelector('.loading-icon'),
            set = this.parentNode.parentNode.parentNode,
            updates = {
                shared: this.checked,
            };
        spinner.style.display = 'initial';
        await updateCriteriaSet(set, updates);
        spinner.style.display = 'none';
    });

    // Delete criteria set.
    const deleteButton = modalBody.find('[data-set-action="delete"]');
    deleteButton.on('click', async function() {
        const set = this.parentNode.parentNode,
            contextId = set.parentNode.parentNode.dataset.context,
            setId = set.dataset.criteriasetId,
            setName = set.dataset.criteriasetName,
            spinner = this.parentNode.querySelector('.loading-icon');
        const strings = await getStrings([
            {key: 'criteriasetdelete', component: 'assignfeedback_structured'},
            {key: 'criteriasetconfirmdelete', component: 'assignfeedback_structured', param: setName},
            {key: 'yes'},
            {key: 'no'},
            {key: 'error'},
            {key: 'criteriasetnotdeleted', component: 'assignfeedback_structured', param: setName},
            {key: 'continue'},
        ]);
        await Notification.confirm(strings[0], strings[1], strings[2], strings[3],
            async() => {
                spinner.style.display = 'initial';
                const request = Ajax.call([{
                    methodname: 'assignfeedback_structured_delete_criteriaset',
                    args: {
                        contextid: contextId,
                        criteriasetid: setId,
                    },
                }]);
                const response = await request[0];
                if (response === true) {
                    set.style.display = 'none';
                } else {
                    await Notification.alert(strings[4], strings[5], strings[6]);
                }
                spinner.style.display = 'none';
            }
        );
    });

    // Use criteria set.
    const useButton = modalBody.find('[data-set-action="use"]');
    useButton.on('click', async function() {
        const set = this.parentNode.parentNode,
            spinner = this.parentNode.querySelector('.loading-icon');
        spinner.style.display = 'initial';

        // Check each criterion name field for a value.
        let use = true;
        for (const name of document.querySelectorAll('input[id^="id_assignfeedback_structured_critname"]')) {
            if (name.value.trim()) {
                use = false;
                const strings = await getStrings([
                    {key: 'criteriasetuse', component: 'assignfeedback_structured'},
                    {key: 'criteriasetconfirmuse', component: 'assignfeedback_structured'},
                    {key: 'yes'},
                    {key: 'no'},
                ]);
                await Notification.confirm(strings[0], strings[1], strings[2], strings[3],
                    async() => {
                        await useSet(set, spinner);
                        modal.destroy();
                    },
                    () => {
                        spinner.style.display = 'none';
                    }
                );
                // Break out of loop.
                break;
            }
        }
        if (use === true) {
            await useSet(set, spinner);
            modal.destroy();
        }
    });
};

/**
 * Function to show/hide a loading icon next to the 'Use/Save criteria set' buttons.
 *
 * @param {Object} button The button to launch the modal.
 * @return {Promise} A promise.
 */
export const modalActive = async(button) => {
    const spinner = button.parentNode.querySelector('.loading-icon');
    if (spinner) {
        spinner.style.display = 'initial';
    } else {
        const {html, js} = await Templates.renderForPromise('core/loading', {});
        Templates.appendNodeContents(button.parentNode, html, js);
    }
    // Hide the spinner when the button regains focus.
    button.addEventListener('focus', () => {
        const spinner = button.parentNode.querySelector('.loading-icon');
        if (spinner) {
            spinner.style.display = 'none';
        }
    });
};

/**
 * Function to determine and set the preferred display of shared criteria sets.
 *
 * @param {boolean} showShared Whether or not to show shared sets.
 * @param {Object} sharedSets The node containing the shared sets.
 */
const toggleShared = (showShared, sharedSets) => {
    if (showShared.prop('checked')) {
        sharedSets.show();
    } else {
        sharedSets.hide();
    }
};

/**
 * Function to call a web service method via AJAX to fetch the criteria data for the given set.
 *
 * @param {Object} set The node containing the details of the criteria set to be fetched.
 * @return {Promise} A promise.
 */
const getCriteria = async(set) => {
    const contextId = set.parentNode.parentNode.dataset.context,
        setId = set.dataset.criteriasetId,
        criteriaNode = set.querySelector('.criteria-data');

    if (!criteriaNode.querySelectorAll('.assignfeedback_structured_criteria').length) {
        const request = Ajax.call([{
            methodname: 'assignfeedback_structured_get_criteria',
            args: {
                contextid: contextId,
                criteriasetid: setId,
            },
        }]);
        const response = await request[0];
        const context = {
            criteriaData: response
        };
        const {html, js} = await Templates.renderForPromise('assignfeedback_structured/criteria', context);
        if (!criteriaNode.querySelectorAll('.assignfeedback_structured_criteria').length) {
            Templates.appendNodeContents(criteriaNode, html, js);
        }
    }
};

/**
 * Event listener to handle the editing of a criteria set name in the 'manage' dialogue.
 * On blur or enter, it hides the input field and sets the new name (if changed).
 * On escape, it hides the input field and aborts the name change.
 *
 * @return {Promise} A promise.
 */
 const nameChangeHandler = async function() {
    const nameElement = this.parentNode.querySelector('[data-bs-toggle="collapse"]'),
        editButton = this.parentNode.querySelector('[data-set-action="editname"]'),
        set = this.parentNode.parentNode,
        setName = set.dataset.criteriasetName,
        rawName = this.value.trim(),
        spinner = this.parentNode.querySelector('.loading-icon');

    // Prevent multiple blur triggers with key presses.
    if (event.type === 'keydown' && (event.keyCode === 13 || event.keyCode === 27)) {
        this.removeEventListener('blur', nameChangeHandler);
    }
    if (event.keyCode === 27) {
        // Cancel on escape.
        event.stopPropagation();
        this.style.display = 'none';
        nameElement.style.display = 'initial';
        editButton.style.display = 'initial';
    } else if (event.type === 'blur' || event.keyCode === 13) {
        // Save on enter (or any other loss of focus).
        const newName = rawName.charAt(0).toUpperCase() + rawName.slice(1);
        this.value = newName;
        if (newName && newName !== setName) {
            const updates = {
                name: newName
            };
            spinner.style.display = 'initial';
            const response = await updateCriteriaSet(set, updates);
            if (response.success === true) {
                this.style.display = 'none';
                set.dataset.criteriasetName = newName;
                nameElement.text = newName;
                nameElement.style.display = 'initial';
                editButton.style.display = 'initial';
            } else {
                await Notification.alert(response.title, response.body, response.label);
                this.style.display = 'none';
                nameElement.style.display = 'initial';
                editButton.style.display = 'initial';
            }
            spinner.style.display = 'none';
        } else {
            this.style.display = 'none';
            nameElement.style.display = 'initial';
            editButton.style.display = 'initial';
        }
    }
};

/**
 * Function to call a web service method via AJAX to update a criteria set with the data provided.
 *
 * @param {Object} set The node containing the details of the criteria set to be updated.
 * @param {Object} updates A set of key/value pairs of data fields to be updated.
 * @return {Promise} A promise.
 */
const updateCriteriaSet = async(set, updates) => {
    const contextId = set.parentNode.parentNode.dataset.context,
        setId = set.dataset.criteriasetId;

    const request = Ajax.call([{
        methodname: 'assignfeedback_structured_update_criteriaset',
        args: {
            contextid: contextId,
            criteriasetid: setId,
            updates: updates,
        },
    }]);

    return await request[0];
};

/**
 * Function to configure the current assignment to use the selected saved criteria set.
 *
 * @param {Object} set The node containing the details of the criteria set to be used.
 * @param {Object} spinner The node of a javascript loading icon.
 * @return {Promise} A promise.
 */
const useSet = async(set, spinner) => {
    const criteriaNode = set.querySelector('.criteria-data');
    if (!criteriaNode.querySelectorAll('.assignfeedback_structured_criteria').length) {
        await getCriteria(set);
    }
    await processCriteriaData(criteriaNode);
    spinner.style.display = 'none';
};

/**
 * Function to process the criteria data for a chosen criteria set and populate the config form fields.
 *
 * @param {Object} criteriaNode The node containing the criteria data to be processed.
 * @return {Promise} A promise.
 */
const processCriteriaData = async(criteriaNode) => {
    const criteriaList = criteriaNode.querySelector('.criteria-list').children,
        configData = [];

    // Prepare the criteria data.
    for (const criterion of criteriaList) {
        const name = criterion.dataset.criterionName,
            desc = criterion.dataset.criterionDesc;
        configData.push({
            name: name,
            desc: desc,
        });
    }

    // Append any additional criteria config fields as necessary.
    const repeats = document.querySelector('[name="assignfeedback_structured_repeats"]');
    const critFields = parseInt(repeats.value);
    if (configData.length > critFields) {
        const newIndexes = [];
        for (let i = critFields; i < configData.length; i++) {
            newIndexes.push({
                nodeIndex: i,
                critIndex: i + 1,
            });
        }
        // Use a template to add name and description fields.
        const context = {
            lastNodeIndex: critFields - 1,
            lastCritIndex: critFields,
            newIndexes: newIndexes,
        };
        const lastFieldId = 'id_assignfeedback_structured_critdesc_' + (critFields - 1);
        const lastNode = document.querySelector('#fitem_' + lastFieldId);
        const templateName = 'assignfeedback_structured/criterianodes';
        const {html, js} = await Templates.renderForPromise(templateName, context);
        Templates.replaceNode(lastNode, html, js);
        await populateFields(configData);
        // Set number of repeats to new value.
        repeats.value = configData.length;
    } else {
        for (let j = 0; j < critFields; j++) {
            // Clear any existing data.
            document.querySelector('#id_assignfeedback_structured_critname_' + j).value = '';
            document.querySelector('#id_assignfeedback_structured_critdesc_' + j).value = '';
        }
        await populateFields(configData);
        // Freeze 'add criteria fields' button.
        document.querySelector('#id_assignfeedback_structured_critfieldsadd').disabled = true;
    }
    if (configData.length >= critFields) {
        // Unfreeze 'add criteria fields' button.
        document.querySelector('#id_assignfeedback_structured_critfieldsadd').disabled = false;
    }
    // Unfreeze 'save criteria set' checkbox.
    document.querySelector('#id_assignfeedback_structured_critsetsave').disabled = false;
};

/**
 * Function to populate fields for each criterion from config data.
 *
 * @param {Object[]} configData The config data to be inserted.
 * @return {Promise} A promise.
 */
const populateFields = async(configData) => {
    configData.forEach((criterion, index) => {
        Object.keys(criterion).forEach((fieldName) => {
            const field = '#id_assignfeedback_structured_crit' + fieldName + '_' + index;
            document.querySelector(field).value = criterion[fieldName];
        });
    });
};
