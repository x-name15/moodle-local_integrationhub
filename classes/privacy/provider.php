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

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the Integration Hub plugin.
 */
class provider implements \core_privacy\local\metadata\provider
{

    /**
     * Returns meta data about this system.
     *
     * @param   \core_privacy\local\metadata\collection $collection The initialised collection to add items to.
     * @return  \core_privacy\local\metadata\collection     A listing of user data stored through this system.
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection
    {
        // The integrationhub does not store personal personal user data directly.
        // It acts as a gateway and only logs technical data required for monitoring integrations.
        // The administrator is responsible for configuring compliant external integrations.
        return $collection->add_plugin_metadata(
            'local_integrationhub',
            'privacy:metadata',
            null
        );
    }
}