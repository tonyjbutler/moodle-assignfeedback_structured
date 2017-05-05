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
 *               assignfeedback_structured/criteriasetsmanage_footer
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
        'core/ajax',
        'core/notification',
        'core/str',
        'core/templates',
        'core/modal_factory'
    ],
    function($, ajax, notification, str, templates, ModalFactory) {
        var criteriaSetsManage = {
            /**
             * Init function.
             *
             * @param {number} contextId The context ID of the current assignment instance.
             * @param {object[]} criteriaSets An array of data objects for all saved criteria sets owned by the current user.
             */
            init: function(contextId, criteriaSets) {
                str.get_string('criteriasetsmanage', 'assignfeedback_structured').then(function(title) {
                    var context = {
                        contextId: contextId,
                        criteriaSets: criteriaSets
                    };
                    var trigger = $('#id_assignfeedback_structured_critsetsmanage');
                    templates.render('assignfeedback_structured/criteriasetsmanage_footer', []).then(function(footer) {
                        ModalFactory.create({
                            title: title,
                            body: templates.render('assignfeedback_structured/criteriasetsmanage', context),
                            footer: footer,
                            large: false
                        }, trigger).done(function(modal) {
                            var modalFooter = modal.getFooter(),
                                spinner = modalFooter.find('.loading-icon'),
                                refreshButton = modalFooter.find('[data-action="refresh"]');
                            spinner.hide();
                            refreshButton.on('click', function() {
                                refreshSets(modal, contextId);
                            });
                        });
                    });
                });
            }
        };

        /**
         * Function to call a web service method via AJAX to update the list of criteria sets.
         *
         * @param {object} modal The modal dialogue for managing the criteria sets.
         * @param {number} contextId The context ID of the current assignment instance.
         */
        function refreshSets(modal, contextId) {
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
                    includepublic: false
                }
            }]);

            request[0].done(function(response) {
                var context = {
                    contextId: contextId,
                    criteriaSets: response.ownedSets
                };
                templates.render('assignfeedback_structured/criteriasetsmanage', context).then(function(html, js) {
                    templates.replaceNodeContents(modalBody, html, js);
                    spinner.hide();
                    refreshButton.show();
                }).fail(notification.exception);
            }).fail(notification.exception);
        }

        return criteriaSetsManage;
    }
);
