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

/**
 * Privacy Subsystem for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrationhub\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy provider for the Integration Hub plugin.
 *
 * The Integration Hub acts as a gateway that dispatches Moodle events to
 * administrator-configured external services via HTTP/AMQP. Because event
 * payloads may contain personal data (e.g. user IDs, course IDs), the plugin
 * must declare that data can be exported to external locations whose URLs are
 * defined by the site administrator in the plugin settings.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider
{
    /**
     * Returns metadata about the data this plugin stores or transmits.
     *
     * The plugin does not store personal data itself but forwards Moodle event
     * data to external services configured by the site administrator. The exact
     * destination URLs are admin-defined and may vary per installation.
     *
     * @param collection $collection The metadata collection to populate.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'external_services',
            [
                'eventname'  => 'privacy:metadata:external_services:eventname',
                'objectid'   => 'privacy:metadata:external_services:objectid',
                'userid'     => 'privacy:metadata:external_services:userid',
                'courseid'   => 'privacy:metadata:external_services:courseid',
                'payload'    => 'privacy:metadata:external_services:payload',
            ],
            'privacy:metadata:external_services'
        );

        return $collection;
    }
}
