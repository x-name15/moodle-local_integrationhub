<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_integrationhub\task;

/**
 * Helper class to manage the Integration Hub task queue.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_manager
{
    /**
     * Get all pending dispatch_event_tasks.
     *
     * @return array List of task objects with expanded custom data.
     */
    public static function get_pending_tasks() {
        global $DB;

        // Fetch adhoc tasks for our specific class.
        $tasks = $DB->get_records(
            'task_adhoc',
            ['classname' => '\local_integrationhub\task\dispatch_event_task'],
            'nextruntime ASC'
        );

        if (empty($tasks)) {
            return [];
        }

        // Collect all rule IDs from task custom data (bulk preload — avoids N+1).
        $ruleids = [];
        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);
            $rid = $data->ruleid ?? 0;
            if ($rid > 0) {
                $ruleids[$rid] = $rid;
            }
        }

        // Bulk-fetch all referenced rules.
        $rules = [];
        if (!empty($ruleids)) {
            $rules = $DB->get_records_list('local_integrationhub_rules', 'id', $ruleids);
        }

        // Bulk-fetch all referenced services.
        $serviceids = [];
        foreach ($rules as $rule) {
            $serviceids[$rule->serviceid] = $rule->serviceid;
        }
        $services = [];
        if (!empty($serviceids)) {
            $services = $DB->get_records_list('local_integrationhub_svc', 'id', $serviceids);
        }

        // Enrich tasks from in-memory maps — zero additional DB queries.
        $results = [];
        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);
            $rid = $data->ruleid ?? 0;
            $rule = $rules[$rid] ?? null;
            $svc = ($rule && isset($services[$rule->serviceid])) ? $services[$rule->serviceid] : null;

            $task->eventname   = $rule->eventname ?? get_string('col_event', 'local_integrationhub');
            $task->servicename = $svc->name ?? 'Unknown';
            $task->ruleid      = $rid;

            $results[] = $task;
        }

        return $results;
    }

    /**
     * Force a retry of a failed/pending task by resetting its faildelay and nextruntime.
     *
     * @param int $taskid The adhoc task ID.
     * @return bool True on success.
     */
    public static function retry_task($taskid) {
        global $DB;

        $task = $DB->get_record('task_adhoc', [
            'id' => $taskid,
            'classname' => '\local_integrationhub\task\dispatch_event_task',
        ]);

        if (!$task) {
            return false;
        }

        $update = new \stdClass();
        $update->id = $taskid;
        $update->faildelay = 0;
        $update->nextruntime = time() - 1; // Run immediately.

        return $DB->update_record('task_adhoc', $update);
    }

    /**
     * Delete a single adhoc task by ID.
     *
     * @param int $taskid The adhoc task ID.
     * @return bool True on success.
     */
    public static function delete_task(int $taskid): bool {
        global $DB;
        return $DB->delete_records('task_adhoc', [
            'id' => $taskid,
            'classname' => '\local_integrationhub\task\dispatch_event_task',
        ]);
    }

    /**
     * Purge all orphan tasks (tasks whose rule no longer exists).
     *
     * @return int Number of tasks purged.
     */
    public static function purge_orphan_tasks(): int {
        global $DB;

        $tasks = $DB->get_records('task_adhoc', [
            'classname' => '\local_integrationhub\task\dispatch_event_task',
        ]);

        if (empty($tasks)) {
            return 0;
        }

        // Collect all rule IDs referenced by tasks (bulk preload — avoids N+1).
        $ruleids = [];
        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);
            $rid = $data->ruleid ?? 0;
            if ($rid > 0) {
                $ruleids[$rid] = $rid;
            }
        }

        // Bulk-fetch existing rules and their service IDs.
        $existingrules = [];
        $serviceids = [];
        if (!empty($ruleids)) {
            $existingrules = $DB->get_records_list('local_integrationhub_rules', 'id', $ruleids, '', 'id,serviceid');
            foreach ($existingrules as $r) {
                $serviceids[$r->serviceid] = $r->serviceid;
            }
        }

        // Bulk-fetch existing services.
        $existingservices = [];
        if (!empty($serviceids)) {
            $existingservices = $DB->get_records_list('local_integrationhub_svc', 'id', $serviceids, '', 'id');
        }

        // Identify orphan task IDs from in-memory maps — zero additional DB queries.
        $orphanids = [];
        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);
            $rid = $data->ruleid ?? 0;

            $isorphan = false;
            if ($rid <= 0) {
                $isorphan = true;
            } else if (!isset($existingrules[$rid])) {
                $isorphan = true;
            } else if (!isset($existingservices[$existingrules[$rid]->serviceid])) {
                // Rule exists but its service is gone.
                $isorphan = true;
            }

            if ($isorphan) {
                $orphanids[] = $task->id;
            }
        }

        if (empty($orphanids)) {
            return 0;
        }

        // Single bulk DELETE for all orphan task IDs.
        [$insql, $inparams] = $DB->get_in_or_equal($orphanids);
        $DB->delete_records_select('task_adhoc', "id {$insql}", $inparams);

        return count($orphanids);
    }
}
