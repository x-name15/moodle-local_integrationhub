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
 * Queue AMD module for Integration Hub.
 *
 * Handles the payload viewer modal in the queue management page.
 *
 * @module     local_integrationhub/queue
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
        'core/modal_factory',
        'core/modal_events',
        'core/notification'
    ],
    function (
        $,
        ModalFactory,
        ModalEvents,
        Notification
    ) {
        return {
            init: function () {
                $('body')
                    .off('click.ihpayload')
                    .on(
                        'click.ihpayload',
                        '.ih-view-payload',
                        function (e) {
                            e.preventDefault();
                            var trigger = $(this);
                            var payload = trigger.attr('data-payload');
                            var title = trigger.attr('data-title') || 'Payload';
                            ModalFactory.create({
                                type: ModalFactory.types.CANCEL,
                                title: title,
                                body: $('<pre>')
                                    .css({
                                        'max-height': '500px',
                                        'overflow': 'auto'
                                    })
                                    .text(payload)
                            })
                                .then(function (modal) {

                                    modal.show();

                                    modal.getRoot().on(
                                        ModalEvents.hidden,
                                        function () {
                                            modal.destroy();
                                        }
                                    );
                                    return null;
                                })
                                .catch(Notification.exception);
                        }
                    );
            }
        };
    }
);
