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
 * Launches the modal dialogue that contains the criteria set selector for the structured feedback plugin.
 *
 * See templates: assignfeedback_structured/criteriaset
 *                assignfeedback_structured/criteriafields
 *
 * @module     assignfeedback_structured/criteriaset
 * @class      criteriaset
 * @package    assignfeedback_structured
 * @copyright  2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jun Pataleta <jun@moodle.com>
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */
define(
    [
        'jquery',
        'core/notification',
        'core/str',
        'core/templates',
        'core/modal_factory',
        'core/modal_events'
    ],
    function($, notification, str, templates, ModalFactory, ModalEvents) {
        var dialogue;
        var criteriaSet = {
            /**
             * Init function.
             *
             * @param {number} contextId The context ID of the current assignment instance.
             * @param {string} postUrl The URL to post the selected criteria set data to.
             * @param {object[]} ownedSets An array of data objects for all saved criteria sets owned by the current user.
             * @param {object[]} publicSets An array of data objects for any other available criteria sets.
             */
            init: function(contextId, postUrl, ownedSets, publicSets) {
                var dialogueTitle = '';
                str.get_string('criteriaset', 'assignfeedback_structured').then(function(title) {
                    dialogueTitle = title;
                    var context = {
                        contextId: contextId,
                        postUrl: postUrl,
                        ownedSets: ownedSets,
                        publicSets: publicSets
                    };

                    var body = templates.render('assignfeedback_structured/criteriaset', context);
                    if (dialogue) {
                        // Set dialogue body.
                        dialogue.setBody(body);
                        // Display the dialogue.
                        dialogue.show();
                    } else {
                        var trigger = $('#id_assignfeedback_structured_critset');
                        ModalFactory.create({
                            type: ModalFactory.types.CANCEL,
                            title: dialogueTitle,
                            body: body,
                            large: false
                        }, trigger).done(function(modal) {
                            dialogue = modal;

                            // Display the dialogue.
                            trigger.click(function() {
                                dialogue.show();
                            });

                            // On hide handler.
                            modal.getRoot().on(ModalEvents.hidden, function() {
                                // Fetch notifications.
                                notification.fetchNotifications();
                            });
                        });
                    }
                });
            }
        };

        /**
         * Window function that can be called from assignfeedback_structured/criteriaconfig to process the config data.
         *
         * @param {object[]} configData An array of criterion config data objects for the selected criteria set.
         */
        window.processCriteriaConfigData = function(configData) {
            if (dialogue) {
                dialogue.hide();
            }

            // Function to populate fields for each criterion from config data.
            var populateFields = function() {
                $.each(configData, function(index, criterion) {
                    $.each(criterion, function(fieldName, value) {
                        var selector = '#id_' + fieldName + '_' + index;
                        $(selector).val(value);
                    });
                });
            };

            // First, append any additional criteria config fields as necessary.
            var critFields = parseInt($('[name="assignfeedback_structured_repeats"]').val(), 10);
            if (configData.length > critFields) {
                var newIndexes = [];
                for (var i = critFields; i < configData.length; i++) {
                    // Clone criterion ID hidden field and set its value to 0.
                    var idField = $('[name="assignfeedback_structured_critid[' + (i - 1) + ']"]');
                    var name = 'assignfeedback_structured_critid[' + i + ']';
                    idField.clone().attr({name: name, value: '0'}).insertAfter(idField);
                    newIndexes.push({'fieldIndex': i, 'critIndex': i + 1});
                }
                // Use a template to deal with name and description fields.
                var context = {
                    lastFieldIndex: critFields - 1,
                    lastCritIndex: critFields,
                    newIndexes: newIndexes
                };
                var newFields = templates.render('assignfeedback_structured/criteriafields', context);
                var lastField = '#fitem_id_assignfeedback_structured_critdesc_' + (critFields - 1);
                newFields.done(function(html, js) {
                    templates.replaceNode(lastField, html, js);
                    populateFields();
                });
                // Set number of repeats to new value.
                $('[name="assignfeedback_structured_repeats"]').val(configData.length);
            } else {
                for (var i = 0; i < critFields; i++) {
                    // Clear any existing data.
                    $('#id_assignfeedback_structured_critname_' + i).val('');
                    $('#id_assignfeedback_structured_critdesc_' + i).val('');
                }
                populateFields();
                // Freeze 'add criteria fields' button.
                $('#id_assignfeedback_structured_critfieldsadd').prop('disabled', true);
            }
            if (configData.length >= critFields) {
                // Unfreeze 'add criteria fields' button.
                $('#id_assignfeedback_structured_critfieldsadd').prop('disabled', false);
            }
            // Unfreeze 'save criteria set' checkbox.
            $('#id_assignfeedback_structured_saveset').prop('disabled', false);
        };

        return criteriaSet;
    }
);
