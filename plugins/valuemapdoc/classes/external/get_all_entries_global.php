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
 * External API for getting all ValueMap entries globally across courses
 * Optimized for 1-10 activities with 5-50 entries each (~500 max entries)
 *
 * @package    aitoolsub_valuemapdoc
 * @copyright  2024 Local AI Tools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace aitoolsub_valuemapdoc\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;
use context_course;
use context_module;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_all_entries_global extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID to filter entries', VALUE_DEFAULT, 0),
            'page' => new external_value(PARAM_INT, 'Page number for pagination', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Number of entries per page', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Get all entries with proper access control across multiple courses/activities
     * @param int $userid User ID
     * @param int $page Page number (0 = no pagination)
     * @param int $limit Entries per page (0 = no limit)
     * @return array
     */
    public static function execute($userid = 0, $page = 0, $limit = 0) {
        global $DB, $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'page' => $page,
            'limit' => $limit
        ]);

        // Validate context
        $context = context_system::instance();
        self::validate_context($context);

        // Check permission
        require_capability('local/aitools:view', $context);

        // Use current user if not specified
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Get user's field level configuration from ValueMapDoc module
        global $CFG;
        require_once($CFG->dirroot . '/mod/valuemapdoc/classes/local/field_levels.php');
        $user_fields = \mod_valuemapdoc\local\field_levels::get_user_fields($params['userid']);
        $user_level_config = \mod_valuemapdoc\local\field_levels::get_user_level_config($params['userid']);

        // Get accessible course modules for the user
        $accessible_cms = self::get_accessible_course_modules($params['userid']);
        
        if (empty($accessible_cms)) {
            return [
                'entries' => [],
                'statistics' => self::get_empty_statistics(),
                'user_fields' => $user_fields,
                'user_level_config' => $user_level_config,
                'pagination' => [
                    'page' => 0,
                    'limit' => 0,
                    'total' => 0,
                    'has_more' => false
                ]
            ];
        }

        // Build main query to get entries with all context information
        $entries = self::get_entries_with_context($params['userid'], $accessible_cms, $params['page'], $params['limit']);
        
        // Format entries for output
        $formatted_entries = [];
        $course_stats = [];
        $activity_stats = [];
        
        foreach ($entries as $entry) {
            $formatted_entry = self::format_entry($entry);
            $formatted_entries[] = $formatted_entry;
            
            // Collect statistics
            $course_stats[$entry->course_id] = $entry->course_name;
            $activity_stats[$entry->activity_id] = $entry->activity_name;
        }

        // Generate statistics
        $statistics = [
            'total_entries' => count($formatted_entries),
            'unique_courses' => count($course_stats),
            'unique_activities' => count($activity_stats),
            'courses_list' => array_values($course_stats),
            'activities_list' => array_values($activity_stats)
        ];

        // Pagination info
        $pagination = [
            'page' => $params['page'],
            'limit' => $params['limit'],
            'total' => count($formatted_entries),
            'has_more' => false // For future implementation
        ];

        return [
            'entries' => $formatted_entries,
            'statistics' => $statistics,
            'user_fields' => $user_fields,
            'user_level_config' => $user_level_config,
            'pagination' => $pagination
        ];
    }

    /**
     * Get accessible course modules for user
     * @param int $userid User ID
     * @return array Array of accessible course module IDs with context
     */
    private static function get_accessible_course_modules($userid) {
        global $DB;

        // Get all ValueMapDoc course modules where user has access
        $sql = "
            SELECT cm.id as cmid,
                   cm.instance,
                   c.id as course_id,
                   c.fullname as course_name,
                   c.shortname as course_shortname,
                   vm.name as activity_name
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {valuemapdoc} vm ON vm.id = cm.instance
            JOIN {course} c ON c.id = cm.course
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE m.name = 'valuemapdoc'
              AND ue.userid = :userid
              AND ue.status = 0
              AND e.status = 0
              AND cm.visible = 1
            ORDER BY c.fullname, vm.name
        ";

        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Get entries with full context information and proper group access control
     * @param int $userid User ID
     * @param array $accessible_cms Accessible course modules
     * @param int $page Page number
     * @param int $limit Entries per page
     * @return array Array of entries with context
     */
    private static function get_entries_with_context($userid, $accessible_cms, $page, $limit) {
        global $DB, $USER;

        if (empty($accessible_cms)) {
            return [];
        }

        $all_entries = [];

        // Process each accessible course module separately to handle group permissions
        foreach ($accessible_cms as $cm_info) {
            $cmid = $cm_info->cmid;
            $course_id = $cm_info->course_id;
            
            // Get course module info for group handling
            $cm = get_coursemodule_from_id('valuemapdoc', $cmid, 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            
            // Check group mode for this activity
            $groupmode = groups_get_activity_groupmode($cm);
            $hasallgroups = has_capability('moodle/site:accessallgroups', $context);
            
            // Build SQL with group filtering
            $sql_params = [
                'cid' => $cmid,
                'userid' => $userid
            ];

            $sql = "
                SELECT e.*,
                       u.username,
                       u.firstname,
                       u.lastname,
                       :cmid_const as cmid,
                       :course_id_const as course_id,
                       :course_name_const as course_name,
                       :course_shortname_const as course_shortname,
                       :activity_id_const as activity_id,
                       :activity_name_const as activity_name
                FROM {valuemapdoc_entries} e
                JOIN {user} u ON u.id = e.userid
                WHERE e.cid = :cid
            ";
            
            // Add context constants to params
            $sql_params['cmid_const'] = $cmid;
            $sql_params['course_id_const'] = $cm_info->course_id;
            $sql_params['course_name_const'] = $cm_info->course_name;
            $sql_params['course_shortname_const'] = $cm_info->course_shortname;
            $sql_params['activity_id_const'] = $cm_info->instance;
            $sql_params['activity_name_const'] = $cm_info->activity_name;

            // Apply group filtering based on group mode
            if ($groupmode == SEPARATEGROUPS && !$hasallgroups) {
                // Separate groups - user can only see entries from their groups
                $usergroups = groups_get_user_groups($course_id, $userid);
                if (!empty($usergroups[0])) {
                    $grouplist = implode(',', $usergroups[0]);
                    $sql .= " AND e.groupid IN ($grouplist)";
                } else {
                    // User not in any group - can only see own entries
                    $sql .= " AND e.userid = :userid_filter";
                    $sql_params['userid_filter'] = $userid;
                }
            }
            // For NOGROUPS or VISIBLEGROUPS - show ALL entries (no additional filtering)

            $sql .= " ORDER BY e.timemodified DESC";

            // Get entries for this course module
            $cm_entries = $DB->get_records_sql($sql, $sql_params);
            
            // Add to all entries
            $all_entries = array_merge($all_entries, array_values($cm_entries));
        }

        // Apply pagination if specified
        if ($limit > 0 && $page > 0) {
            $offset = ($page - 1) * $limit;
            $all_entries = array_slice($all_entries, $offset, $limit, true);
        }

        return $all_entries;
    }

    /**
     * Format entry data for optimal JavaScript consumption
     * @param stdClass $entry Raw entry from database with context
     * @return array Formatted entry with dual data format
     */
    private static function format_entry($entry) {
        // Create entry_data JSON from individual database fields
        $entry_data = [
            'market' => $entry->market ?? '',
            'industry' => $entry->industry ?? '',
            'role' => $entry->role ?? '',
            'businessgoal' => $entry->businessgoal ?? '',
            'strategy' => $entry->strategy ?? '',
            'difficulty' => $entry->difficulty ?? '',
            'situation' => $entry->situation ?? '',
            'statusquo' => $entry->statusquo ?? '',
            'coi' => $entry->coi ?? '',
            'differentiator' => $entry->differentiator ?? '',
            'impact' => $entry->impact ?? '',
            'newstate' => $entry->newstate ?? '',
            'successmetric' => $entry->successmetric ?? '',
            'impactstrategy' => $entry->impactstrategy ?? '',
            'impactbusinessgoal' => $entry->impactbusinessgoal ?? '',
            'impactothers' => $entry->impactothers ?? '',
            'proof' => $entry->proof ?? '',
            'time2results' => $entry->time2results ?? '',
            'quote' => $entry->quote ?? '',
            'clientname' => $entry->clientname ?? ''
        ];

        // Create action URLs
        $edit_url = new \moodle_url('/mod/valuemapdoc/edit.php', [
            'id' => $entry->cmid,
            'entryid' => $entry->id
        ]);
        
        $view_url = new \moodle_url('/mod/valuemapdoc/view.php', [
            'id' => $entry->cmid,
            'entryid' => $entry->id
        ]);

        return [
            // === IDENTIFIERS ===
            'id' => (int)$entry->id,
            'userid' => (int)$entry->userid,
            'cmid' => (int)$entry->cmid,

            // === COURSE/ACTIVITY CONTEXT ===
            'course_id' => (int)$entry->course_id,
            'course_name' => $entry->course_name,
            'course_shortname' => $entry->course_shortname,
            'activity_id' => (int)$entry->activity_id,
            'activity_name' => $entry->activity_name,

            // === ENTRY DATA (dual format for optimal JS performance) ===
            'entry_data' => json_encode($entry_data),
            
            // Direct field access (no JSON parsing needed in JS)
            'market' => $entry->market ?? '',
            'industry' => $entry->industry ?? '',
            'role' => $entry->role ?? '',
            'businessgoal' => $entry->businessgoal ?? '',
            'strategy' => $entry->strategy ?? '',
            'difficulty' => $entry->difficulty ?? '',
            'situation' => $entry->situation ?? '',
            'statusquo' => $entry->statusquo ?? '',
            'coi' => $entry->coi ?? '',
            'differentiator' => $entry->differentiator ?? '',
            'impact' => $entry->impact ?? '',
            'newstate' => $entry->newstate ?? '',
            'successmetric' => $entry->successmetric ?? '',
            'impactstrategy' => $entry->impactstrategy ?? '',
            'impactbusinessgoal' => $entry->impactbusinessgoal ?? '',
            'impactothers' => $entry->impactothers ?? '',
            'proof' => $entry->proof ?? '',
            'time2results' => $entry->time2results ?? '',
            'quote' => $entry->quote ?? '',
            'clientname' => $entry->clientname ?? '',

            // === USER METADATA ===
            'username' => $entry->username,
            'user_fullname' => fullname($entry),
            'ismaster' => (int)(isset($entry->ismaster) ? $entry->ismaster : 0),

            // === TIMESTAMPS ===
            'timemodified' => (int)($entry->timemodified ?? time()),
            'timemodified_formatted' => self::format_time_ago($entry->timemodified ?? time()),
            'timecreated' => (int)($entry->timemodified ?? time()), // Fallback since timecreated doesn't exist
            'timecreated_formatted' => self::format_time_ago($entry->timemodified ?? time()),

            // === ACTIONS ===
            'edit_url' => $edit_url->out(false),
            'view_url' => $view_url->out(false)
        ];
    }

    /**
     * Format time difference for human readable display
     * @param int $timestamp Unix timestamp
     * @return string Formatted time
     */
    private static function format_time_ago($timestamp) {
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return get_string('just_now', 'aitoolsub_valuemapdoc');
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return get_string('minutes_ago', 'aitoolsub_valuemapdoc', $minutes);
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return get_string('hours_ago', 'aitoolsub_valuemapdoc', $hours);
        } elseif ($time_diff < 604800) {
            $days = floor($time_diff / 86400);
            return get_string('days_ago', 'aitoolsub_valuemapdoc', $days);
        } else {
            return userdate($timestamp, get_string('strftimedatefullshort'));
        }
    }

    /**
     * Get empty statistics structure
     * @return array Empty statistics
     */
    private static function get_empty_statistics() {
        return [
            'total_entries' => 0,
            'unique_courses' => 0,
            'unique_activities' => 0,
            'courses_list' => [],
            'activities_list' => []
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'entries' => new external_multiple_structure(
                new external_single_structure([
                    // === IDENTIFIERS ===
                    'id' => new external_value(PARAM_INT, 'Entry ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'cmid' => new external_value(PARAM_INT, 'Course module ID'),

                    // === CONTEXT ===
                    'course_id' => new external_value(PARAM_INT, 'Course ID'),
                    'course_name' => new external_value(PARAM_TEXT, 'Course name'),
                    'course_shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                    'activity_id' => new external_value(PARAM_INT, 'Activity ID'),
                    'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),

                    // === DATA (dual format) ===
                    'entry_data' => new external_value(PARAM_RAW, 'Entry data JSON'),
                    
                    // Direct field access
                    'market' => new external_value(PARAM_TEXT, 'Market', VALUE_OPTIONAL),
                    'industry' => new external_value(PARAM_TEXT, 'Industry', VALUE_OPTIONAL),
                    'role' => new external_value(PARAM_TEXT, 'Role', VALUE_OPTIONAL),
                    'businessgoal' => new external_value(PARAM_TEXT, 'Business Goal', VALUE_OPTIONAL),
                    'strategy' => new external_value(PARAM_TEXT, 'Strategy', VALUE_OPTIONAL),
                    'difficulty' => new external_value(PARAM_TEXT, 'Difficulty', VALUE_OPTIONAL),
                    'situation' => new external_value(PARAM_TEXT, 'Situation', VALUE_OPTIONAL),
                    'statusquo' => new external_value(PARAM_TEXT, 'Status Quo', VALUE_OPTIONAL),
                    'coi' => new external_value(PARAM_TEXT, 'Cost of Inaction', VALUE_OPTIONAL),
                    'differentiator' => new external_value(PARAM_TEXT, 'Differentiator', VALUE_OPTIONAL),
                    'impact' => new external_value(PARAM_TEXT, 'Impact', VALUE_OPTIONAL),
                    'newstate' => new external_value(PARAM_TEXT, 'New State', VALUE_OPTIONAL),
                    'successmetric' => new external_value(PARAM_TEXT, 'Success Metric', VALUE_OPTIONAL),
                    'impactstrategy' => new external_value(PARAM_TEXT, 'Impact Strategy', VALUE_OPTIONAL),
                    'impactbusinessgoal' => new external_value(PARAM_TEXT, 'Impact Business Goal', VALUE_OPTIONAL),
                    'impactothers' => new external_value(PARAM_TEXT, 'Impact Others', VALUE_OPTIONAL),
                    'proof' => new external_value(PARAM_TEXT, 'Proof', VALUE_OPTIONAL),
                    'time2results' => new external_value(PARAM_TEXT, 'Time to Results', VALUE_OPTIONAL),
                    'quote' => new external_value(PARAM_TEXT, 'Quote', VALUE_OPTIONAL),
                    'clientname' => new external_value(PARAM_TEXT, 'Client Name', VALUE_OPTIONAL),

                    // === METADATA ===
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'user_fullname' => new external_value(PARAM_TEXT, 'User full name'),
                    'ismaster' => new external_value(PARAM_INT, 'Is master entry (1 or 0)'),

                    // === TIMESTAMPS ===
                    'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                    'timecreated_formatted' => new external_value(PARAM_TEXT, 'Formatted creation time'),
                    'timemodified' => new external_value(PARAM_INT, 'Modification timestamp'),
                    'timemodified_formatted' => new external_value(PARAM_TEXT, 'Formatted modification time'),

                    // === ACTIONS ===
                    'edit_url' => new external_value(PARAM_URL, 'Edit URL'),
                    'view_url' => new external_value(PARAM_URL, 'View URL')
                ])
            ),
            
            'statistics' => new external_single_structure([
                'total_entries' => new external_value(PARAM_INT, 'Total number of entries'),
                'unique_courses' => new external_value(PARAM_INT, 'Number of unique courses'),
                'unique_activities' => new external_value(PARAM_INT, 'Number of unique activities'),
                'courses_list' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Course name')
                ),
                'activities_list' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Activity name')
                )
            ]),
            
            'user_fields' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Field name'),
                'User visible fields based on level'
            ),
            
            'user_level_config' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Level name'),
                'description' => new external_value(PARAM_TEXT, 'Level description'),
                'fields_count' => new external_value(PARAM_INT, 'Number of fields'),
                'fields' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Field name')
                )
            ]),
            
            'pagination' => new external_single_structure([
                'page' => new external_value(PARAM_INT, 'Current page'),
                'limit' => new external_value(PARAM_INT, 'Entries per page'),
                'total' => new external_value(PARAM_INT, 'Total entries'),
                'has_more' => new external_value(PARAM_BOOL, 'Has more pages')
            ])
        ]);
    }
}