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
 * See template: assignfeedback_structured/criteriasetsmanage
 *
 * @module     assignfeedback_structured/criteriasetsmanage
 * @class      criteriasetsmanage
 * @package    assignfeedback_structured
 * @copyright  2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        var criteriaSetsManage = {
            /**
             * Init function.
             *
             * @param {number} contextId The context ID of the current assignment instance.
             * @param {object[]} criteriaSets An array of data objects for all saved criteria sets owned by the current user.
             */
            init: function(contextId, criteriaSets) {
                var dialogueTitle = '';
                str.get_string('criteriasetsmanage', 'assignfeedback_structured').then(function(title) {
                    dialogueTitle = title;
                    var context = {
                        contextId: contextId,
                        criteriaSets: criteriaSets
                    };

                    var body = templates.render('assignfeedback_structured/criteriasetsmanage', context);
                    if (dialogue) {
                        // Set dialogue body.
                        dialogue.setBody(body);
                        // Display the dialogue.
                        dialogue.show();
                    } else {
                        var trigger = $('#id_assignfeedback_structured_critsetsmanage');
                        ModalFactory.create({
                            title: dialogueTitle,
                            body: body,
                            type: ModalFactory.types.CANCEL,
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

        return criteriaSetsManage;
    }
);
