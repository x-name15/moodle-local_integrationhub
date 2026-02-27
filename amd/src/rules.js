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
 * Rules AMD module for Integration Hub.
 *
 * Handles rule form interactions: service-type label toggle, add/cancel form,
 * and payload template preview via AJAX.
 *
 * @module     local_integrationhub/rules
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
        'core/ajax',
        'core/notification'
    ],
    function(
        $,
        Ajax,
        Notification
    ) {

        return {
            init: function(serviceTypes) {

                try {
                    var formContainer = $('#ih-rule-form');
                    var btnAdd = $('#ih-btn-add');
                    var btnCancel = $('#ih-btn-cancel');
                    var btnPreview = $('#ih-btn-preview');
                    var templateField = $('#ih-template');
                    var serviceField = $('#ih-serviceid');
                    var endpointField = $('#ih-endpoint');
                    var endpointLabel =
                        $('label[for="ih-endpoint"]');
                    var methodContainer =
                        $('#ih-method-container');

                    var updateEndpointLabel =
                        function() {

                            var svcId =
                                serviceField.val();

                            var type =
                                serviceTypes[svcId] ||
                                'rest';

                            if (type === 'amqp') {
                                endpointLabel.text(
                                    'Queue Name / Routing Key'
                                );

                                endpointField.attr(
                                    'placeholder',
                                    'e.g. user_sync_queue'
                                );

                                methodContainer.addClass(
                                    'd-none'
                                );
                            } else if (type === 'soap') {
                                endpointLabel.text(
                                    'SOAP Action / Method'
                                );

                                endpointField.attr(
                                    'placeholder',
                                    'e.g. CreateUser'
                                );

                                methodContainer.addClass(
                                    'd-none'
                                );
                            } else {
                                endpointLabel.text(
                                    'Endpoint Path'
                                );

                                endpointField.attr(
                                    'placeholder',
                                    'e.g. /api/v1/users'
                                );

                                methodContainer.removeClass(
                                    'd-none'
                                );
                            }
                        };

                    if (serviceField.length) {
                        serviceField.on(
                            'change',
                            updateEndpointLabel
                        );

                        updateEndpointLabel();
                    }

                    if (btnAdd.length) {
                        btnAdd.on('click', function() {

                            $('#ih-ruleid').val('0');
                            $('#ih-form')[0].reset();

                            formContainer.removeClass(
                                'd-none'
                            );

                            btnAdd.addClass('d-none');
                        });
                    }

                    if (btnCancel.length) {
                        btnCancel.on('click', function() {

                            formContainer.addClass(
                                'd-none'
                            );

                            btnAdd.removeClass('d-none');
                        });
                    }

                    if (btnPreview.length) {
                        btnPreview.on('click', function(e) {
                            e.preventDefault();

                            var template = templateField.val();
                            if (!template) {
                                Notification.alert('Error', 'Please enter a template first.', 'OK');
                                return;
                            }

                            var eventNameField = $('#ih-eventname');
                            var eventname = eventNameField.length ? eventNameField.val() : '';

                            var promises = Ajax.call([{
                                methodname: 'local_integrationhub_preview_payload',
                                args: {
                                    template: template,
                                    eventname: eventname
                                }
                            }]);

                            promises[0].done(function(response) {
                                if (response.success) {
                                    var preStyle = 'text-align: left; background: #f8f9fa; ' +
                                        'padding: 10px; border: 1px solid #ddd;';
                                    var content = '<pre style="' + preStyle + '">' +
                                        response.payload + '</pre>';
                                    Notification.alert('Payload Preview', content, 'OK');
                                } else {
                                    Notification.alert('Error', response.error || 'Failed to preview payload.', 'OK');
                                }
                            }).fail(Notification.exception);
                        });
                    }
                } catch (e) {
                    Notification.exception(e);
                }
            }
        };
    }
);
