<?php
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

require_once(__DIR__ . '/classes/gateway.php');

/**
 * MIH - Public Facade for the Moodle Integration Hub.
 * 
 * This class provides a short, readable entry point for other plugins 
 * to use the Integration Hub services.
 */
class MIH {
    /**
     * Dispatch a manual request to a registered service.
     *
     * @param string $service  The service slug (e.g. 'judgeman').
     * @param string $endpoint The path or routing key (e.g. '/update').
     * @param array  $payload  Data to send.
     * @param string $method   HTTP Method (POST/GET) or AMQP action.
     * @return \local_integrationhub\gateway_response
     */
    public static function request(string $service, string $endpoint = '/', array $payload = [], string $method = '') {
        return \local_integrationhub\gateway::instance()->request($service, $endpoint, $payload, $method);
    }
}

/**
 * Library functions for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds Integration Hub link to the Moodle navigation.
 *
 * @param global_navigation $navigation The global navigation object.
 */
function local_integrationhub_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    $context = context_system::instance();
    if (has_capability('local/integrationhub:view', $context)) {
        $node = $navigation->add(
            get_string('pluginname', 'local_integrationhub'),
            new moodle_url('/local/integrationhub/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'integrationhub',
            new pix_icon('i/settings', '')
        );
        $node->showinflatnavigation = true;
    }
}
