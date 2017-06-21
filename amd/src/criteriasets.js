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
 *               assignfeedback_structured/criteriasets_footer
 *
 * @module     assignfeedback_structured/criteriasets
 * @class      criteriasets
 * @package    assignfeedback_structured
 * @copyright  2017 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */
define(
    [
        'jquery',
        'core/ajax',
        'core/notification',
        'core/str',
        'core/templates',
        'core/modal_factory',
        'core/modal_events'
    ],
    function($, ajax, notification, str, templates, ModalFactory, ModalEvents) {
        var criteriaSets = {
            /**
             * Init function.
             *
             * @param {number} contextId The context ID of the current assignment instance.
             * @param {boolean} manage Whether a full management interface is required (otherwise it's read only).
             * @param {boolean} canPublish Whether the current user can publish criteria sets.
             * @param {object[]} ownedSets An array of data objects for all saved criteria sets owned by the current user.
             * @param {object[]} sharedSets An array of data objects for any other available criteria sets.
             */
            init: function(contextId, manage, canPublish, ownedSets, sharedSets) {
                str.get_strings([
                    {key: 'criteriasetssaved', component: 'assignfeedback_structured'},
                    {key: 'criteriasetsmanage', component: 'assignfeedback_structured'}
                ]).done(function(s) {
                    var context = {
                        contextId: contextId,
                        manage: manage,
                        canPublish: canPublish,
                        ownedSets: ownedSets,
                        sharedSets: sharedSets
                    };
                    var footerContext = {
                        manage: manage
                    };
                    var title = s[0],
                        trigger = $('#id_assignfeedback_structured_critset');
                    if (manage) {
                        title = s[1];
                        trigger = $('#id_assignfeedback_structured_critsetsmanage');
                    }
                    templates.render('assignfeedback_structured/criteriasets_footer', footerContext).done(function(footer) {
                        ModalFactory.create({
                            title: title,
                            body: templates.render('assignfeedback_structured/criteriasets', context),
                            footer: footer,
                            large: false
                        }, trigger).done(function(modal) {
                            var refreshButton = modal.getFooter().find('[data-action="refresh"]');
                            refreshButton.on('click', function() {
                                refreshSets(modal, contextId, manage, canPublish);
                            });
                            // Refresh automatically when showing the modal.
                            modal.getRoot().on(ModalEvents.shown, function() {
                                refreshSets(modal, contextId, manage, canPublish);
                            });
                        }).fail(notification.exception);
                    }).fail(notification.exception);
                });

                // Add a hidden spinner after the 'Use a saved criteria set' button.
                if (!manage) {
                    var setButton = $('#id_assignfeedback_structured_critset');
                    templates.render('core/loading', {}).done(function(html, js) {
                        templates.appendNodeContents(setButton.parent(), html, js);
                        setButton.siblings('.loading-icon').hide();
                    }).fail(notification.exception);
                }
            }
        };

        /**
         * Function to call a web service method via AJAX to update the list of criteria sets.
         *
         * @param {object} modal The modal dialogue to be refreshed.
         * @param {number} contextId The context ID of the current assignment instance.
         * @param {boolean} manage Whether the modal provides a management interface.
         * @param {boolean} canPublish Whether the current user can publish criteria sets.
         */
        function refreshSets(modal, contextId, manage, canPublish) {
            var modalBody = modal.getBody(),
                modalFooter = modal.getFooter(),
                refreshButton = modalFooter.find('[data-action="refresh"]'),
                buttonWidth = refreshButton.width(),
                spinner = modalFooter.find('.loading-icon');

            refreshButton.hide();
            spinner.css({marginLeft: buttonWidth / 2 + 'px', marginRight: buttonWidth / 2 + 'px'});
            spinner.show();

            var request = ajax.call([{
                methodname: 'assignfeedback_structured_get_criteriasets',
                args: {
                    contextid: contextId,
                    includeshared: !manage
                }
            }]);

            request[0].done(function(response) {
                var context = {
                    contextId: contextId,
                    manage: manage,
                    canPublish: canPublish,
                    ownedSets: response.ownedsets,
                    sharedSets: response.sharedsets
                };
                templates.render('assignfeedback_structured/criteriasets', context).done(function(html, js) {
                    templates.replaceNodeContents(modalBody, html, js);
                    spinner.hide();
                    refreshButton.show();
                }).fail(notification.exception);
            }).fail(notification.exception);
        }

        return criteriaSets;
    }
);
