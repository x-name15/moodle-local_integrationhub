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
 * External function: preview_payload
 *
 * Interpolates a JSON payload template with mock event data for preview purposes.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrationhub\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_system;

/**
 * External API class for payload template preview.
 */
class preview_payload extends external_api {

    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'template'  => new external_value(PARAM_RAW, 'JSON payload template with {{placeholder}} syntax'),
            'eventname' => new external_value(PARAM_TEXT, 'Event class name for context', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Preview a payload template by interpolating mock event data.
     *
     * @param string $template  The JSON template string.
     * @param string $eventname Optional event name for context.
     * @return array Result with success flag, interpolated payload, and raw string.
     */
    public static function execute(string $template, string $eventname = ''): array {
        global $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'template'  => $template,
            'eventname' => $eventname,
        ]);

        // Require manage capability.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/integrationhub:manage', $context);

        // Mock data for interpolation (same as the old ajax.php logic).
        $mockdata = [
            'eventname'  => $params['eventname'] ?: '\core\event\course_created',
            'objectid'   => 123,
            'userid'     => 5,
            'courseid'   => 10,
            'contextid'  => 1,
            'timecreated' => time(),
            'ip'         => '127.0.0.1',
        ];

        $json = $params['template'];
        foreach ($mockdata as $key => $value) {
            $replacement = $value;
            if (is_string($value)) {
                $replacement = substr(json_encode($value), 1, -1);
            }
            $json = str_replace('{{' . $key . '}}', $replacement, $json);
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'payload' => '',
                'raw'     => $json,
                'error'   => json_last_error_msg(),
            ];
        }

        return [
            'success' => true,
            'payload' => json_encode($decoded, JSON_PRETTY_PRINT),
            'raw'     => $json,
            'error'   => '',
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the interpolation succeeded'),
            'payload' => new external_value(PARAM_RAW, 'Pretty-printed interpolated JSON'),
            'raw'     => new external_value(PARAM_RAW, 'Raw interpolated string before JSON decode'),
            'error'   => new external_value(PARAM_TEXT, 'Error message if failed, empty otherwise'),
        ]);
    }
}
