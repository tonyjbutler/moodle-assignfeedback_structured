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
        var criteriaSetSave = {
            /**
             * Init function.
             *
             * @param {number} contextId The context ID of the current assignment instance.
             * @param {boolean} canPublish Whether the current user can publish criteria sets.
             */
            init: function(contextId, canPublish) {
                str.get_string('criteriasetsave', 'assignfeedback_structured').done(function(title) {
                    var context = {
                        canPublish: canPublish
                    };
                    var trigger = $('#id_assignfeedback_structured_critsetsave');
                    ModalFactory.create({
                        title: title,
                        body: templates.render('assignfeedback_structured/criteriasetsave', context),
                        type: ModalFactory.types.SAVE_CANCEL,
                        large: false
                    }, trigger).done(function(modal) {
                        // Disable save button initially.
                        modal.getFooter().find('[data-action="save"]').prop('disabled', true);
                        modal.getRoot().on(ModalEvents.save, function(e) {
                            e.preventDefault();
                            saveSet(modal, contextId);
                        });
                        // Clear field values on hide.
                        modal.getRoot().on(ModalEvents.hidden, function() {
                            $(this).find('[name="criteriaset-name"]').val('');
                            $(this).find('[name="criteriaset-publish"]').prop('checked', false);
                            $(this).find('[data-action="save"]').prop('disabled', true);
                        });
                    }).fail(notification.exception);
                }).fail(notification.exception);
            }
        };

        /**
         * Function to gather the criteria data and call a web service method via AJAX to save as a named criteria set.
         *
         * @param {object} modal The modal dialogue containing the criteria set save data.
         * @param {number} contextId The context ID of the current assignment instance.
         */
        function saveSet(modal, contextId) {
            var modalNode = modal.getRoot(),
                name = modalNode.find('[name="criteriaset-name"]').val().trim(),
                public = modalNode.find('[name="criteriaset-publish"]').prop('checked'),
                spinner = modalNode.find('.loading-icon');

            spinner.show();

            var criteria = [];
            modalNode.parent().find('[id^="id_assignfeedback_structured_critname"]').each(function() {
                if ($(this).val().trim().length) {
                    var descNode = $(this).parent().parent().next().find('[id^="id_assignfeedback_structured_critdesc"]');
                    var descText;
                    if (descNode.val()) {
                        descText = descNode.val().trim();
                    } else {
                        descText = '';
                    }
                    criteria.push({
                        name: $(this).val().trim(),
                        description: descText
                    });
                }
            });

            var request = ajax.call([{
                methodname: 'assignfeedback_structured_save_criteriaset',
                args: {
                    contextid: contextId,
                    name: name,
                    criteria: criteria,
                    public: public
                }
            }]);

            request[0].done(function(response) {
                if (response['hide'] === true) {
                    modal.hide();
                }
                notification.alert(response['title'], response['body'], response['label']);
            }).fail(notification.exception).always(function() {
                spinner.hide();
            });
        }

        return criteriaSetSave;
    }
);
