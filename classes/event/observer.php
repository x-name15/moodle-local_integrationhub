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

namespace local_integrationhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for Integration Hub.
 *
 * Listens to Moodle events and dispatches them to external services based on configured rules.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle observed events.
     *
     * @param \core\event\base $event The Moodle event.
     */
    public static function handle_event(\core\event\base $event) {
        global $DB;

        // 1. Get event name.
        $eventname = $event->eventname; // e.g., \core\event\user_created

        // 2. Check for active rules matching this event.
        // We use a simple query. In high-traffic sites, this should be cached.
        $rules = $DB->get_records('local_integrationhub_rules', [
            'eventname' => $eventname,
            'enabled'   => 1,
        ]);

        if (empty($rules)) {
            return;
        }

        // 3. Dispatch task for each rule.
        // We defer processing to Adhoc Tasks to avoid blocking the user action.
        foreach ($rules as $rule) {
            $task = new \local_integrationhub\task\dispatch_event_task();
            $task->set_custom_data([
                'ruleid'    => $rule->id,
                'eventdata' => $event->get_data(),
                'eventcontextid' => $event->contextid,
            ]);
            
            // Queue the task.
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}
