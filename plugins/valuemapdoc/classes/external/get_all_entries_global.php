<?php
namespace aitoolsub_valuemapdoc\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/group/lib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;
use context_module;

/**
 * External service for getting all entries global with proper group access
 */
class get_all_entries_global extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get all entries for user across all courses with group access respect
     * @param int $userid User ID
     * @return array
     */
    public static function execute($userid = 0) {
        global $DB, $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid
        ]);

        // Check context and permissions
        $context = context_system::instance();
        self::validate_context($context);

        // Use current user if not specified
        if ($params['userid'] == 0) {
            $params['userid'] = $USER->id;
        }

        // Security check - users can only see their own entries unless they're admin
        if ($params['userid'] != $USER->id && !has_capability('local/aitools:manage', $context)) {
            throw new \moodle_exception('nopermission');
        }

        // Check basic capability
        require_capability('local/aitools:view', $context);

        return self::get_user_entries_with_groups($params['userid']);
    }

    /**
     * Get all user's value map entries across all courses respecting group settings
     * @param int $userid User ID
     * @return array
     */
    private static function get_user_entries_with_groups($userid) {
        global $DB;

        // Step 1: Get all ValueMapDoc activities user has access to
        $accessible_activities = self::get_accessible_activities($userid);
        
        if (empty($accessible_activities)) {
            return [
                'entries' => [],
                'statistics' => [
                    'total_entries' => 0,
                    'unique_courses' => 0,
                    'unique_activities' => 0,
                    'courses_list' => [],
                    'activities_list' => []
                ]
            ];
        }

        // Step 2: For each activity, get entries based on group permissions
        $all_entries = [];
        $statistics = [
            'total_entries' => 0,
            'unique_courses' => [],
            'unique_activities' => []
        ];

        foreach ($accessible_activities as $activity) {
            $entries = self::get_entries_for_activity($activity, $userid);
            
            foreach ($entries as $entry) {
                // Format entry data
                $formatted_entry = self::format_entry($entry, $activity);
                $all_entries[] = $formatted_entry;
                
                // Update statistics
                $statistics['total_entries']++;
                $statistics['unique_courses'][$entry->course] = $activity['course_name'];
                $statistics['unique_activities'][$activity['cmid']] = $activity['activity_name'];
            }
        }

        // Sort entries by course name, activity name, then by modification time
        usort($all_entries, function($a, $b) {
            $course_cmp = strcmp($a['course_name'], $b['course_name']);
            if ($course_cmp !== 0) return $course_cmp;
            
            $activity_cmp = strcmp($a['activity_name'], $b['activity_name']);
            if ($activity_cmp !== 0) return $activity_cmp;
            
            return $b['timemodified'] - $a['timemodified']; // Newest first
        });

        return [
            'entries' => $all_entries,
            'statistics' => [
                'total_entries' => $statistics['total_entries'],
                'unique_courses' => count($statistics['unique_courses']),
                'unique_activities' => count($statistics['unique_activities']),
                'courses_list' => array_values($statistics['unique_courses']),
                'activities_list' => array_values($statistics['unique_activities'])
            ]
        ];
    }

    /**
     * Get all ValueMapDoc activities user has access to
     * @param int $userid User ID
     * @return array Array of activity info
     */
    private static function get_accessible_activities($userid) {
        global $DB;

        // Get all ValueMapDoc course modules where user is enrolled
        $sql = "
            SELECT cm.id as cmid,
                   cm.course,
                   cm.instance,
                   vm.name as activity_name,
                   c.fullname as course_name,
                   c.shortname as course_shortname,
                   cm.groupmode,
                   cm.groupingid
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module AND m.name = 'valuemapdoc'
            JOIN {valuemapdoc} vm ON vm.id = cm.instance
            JOIN {course} c ON c.id = cm.course
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :userid
            WHERE cm.visible = 1 
              AND vm.id IS NOT NULL
              AND ue.status = 0
              AND e.status = 0
            ORDER BY c.fullname, vm.name
        ";

        $activities = $DB->get_records_sql($sql, ['userid' => $userid]);
        
        $accessible = [];
        foreach ($activities as $activity) {
            // Convert to array and add to accessible list
            $accessible[] = [
                'cmid' => $activity->cmid,
                'course' => $activity->course,
                'instance' => $activity->instance,
                'activity_name' => $activity->activity_name,
                'course_name' => $activity->course_name,
                'course_shortname' => $activity->course_shortname,
                'groupmode' => $activity->groupmode,
                'groupingid' => $activity->groupingid
            ];
        }

        return $accessible;
    }

    /**
     * Get entries for specific activity based on group permissions
     * @param array $activity Activity info
     * @param int $userid User ID
     * @return array Entries
     */
    private static function get_entries_for_activity($activity, $userid) {
        global $DB;

        $cmid = $activity['cmid'];
        $courseid = $activity['course'];
        $groupmode = $activity['groupmode'];

        // Get context for this course module
        try {
            $context = context_module::instance($cmid);
        } catch (Exception $e) {
            // If context doesn't exist, skip this activity
            return [];
        }

        // Check if user can access this activity
        if (!has_capability('mod/valuemapdoc:view', $context, $userid)) {
            return [];
        }

        // Determine which entries user can see based on group mode
        $entry_userids = self::get_visible_userids($cmid, $courseid, $groupmode, $userid);

        if (empty($entry_userids)) {
            return [];
        }

        // Build SQL to get entries
        list($user_sql, $user_params) = $DB->get_in_or_equal($entry_userids, SQL_PARAMS_NAMED);
        
        $sql = "
            SELECT e.id, 
                   e.userid,
                   e.cid as cmid,
                   e.course,
                   e.timemodified,
                   e.market,
                   e.industry, 
                   e.role,
                   e.businessgoal,
                   e.strategy,
                   e.difficulty,
                   e.situation,
                   e.statusquo,
                   e.coi,
                   e.differentiator,
                   e.impact,
                   e.newstate,
                   e.successmetric,
                   e.impactstrategy,
                   e.impactbusinessgoal,
                   e.impactothers,
                   e.proof,
                   e.time2results,
                   e.quote,
                   e.clientname,
                   e.ismaster,
                   e.maturity,
                   e.groupid,
                   u.username,
                   u.firstname,
                   u.lastname
            FROM {valuemapdoc_entries} e
            JOIN {user} u ON u.id = e.userid
            WHERE e.cid = :cmid
              AND e.userid {$user_sql}
            ORDER BY e.timemodified DESC
        ";

        $params = array_merge($user_params, ['cmid' => $cmid]);
        
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get list of user IDs whose entries are visible to the given user
     * @param int $cmid Course module ID
     * @param int $courseid Course ID
     * @param int $groupmode Group mode (0=no groups, 1=separate, 2=visible)
     * @param int $userid User ID
     * @return array Array of visible user IDs
     */
    private static function get_visible_userids($cmid, $courseid, $groupmode, $userid) {
        global $DB;

        // If no groups or visible groups - user can see all entries
        if ($groupmode == NOGROUPS || $groupmode == VISIBLEGROUPS) {
            // Get all enrolled users in this course
            $sql = "
                SELECT DISTINCT ue.userid
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = :courseid
                  AND ue.status = 0
                  AND e.status = 0
            ";
            
            $enrolled_users = $DB->get_fieldset_sql($sql, ['courseid' => $courseid]);
            return $enrolled_users;
        }

        // Separate groups - user can only see entries from their groups
        if ($groupmode == SEPARATEGROUPS) {
            // Get user's groups in this course
            $user_groups = groups_get_user_groups($courseid, $userid);
            
            if (empty($user_groups[0])) {
                // User not in any group - can only see own entries
                return [$userid];
            }

            // Get all members of user's groups
            $group_members = [];
            foreach ($user_groups[0] as $groupid) {
                $members = groups_get_members($groupid, 'u.id');
                $group_members = array_merge($group_members, array_keys($members));
            }

            return array_unique($group_members);
        }

        // Fallback - only own entries
        return [$userid];
    }

    /**
     * Format entry data for output
     * @param stdClass $entry Raw entry from database
     * @param array $activity Activity info
     * @return array Formatted entry
     */
    private static function format_entry($entry, $activity) {
        // Create entry data array from all fields
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

        // Determine entry type and preview
        $entry_type = self::get_entry_type($entry_data);
        $entry_preview = self::get_entry_preview($entry_data);

        return [
            'id' => (int)$entry->id,
            'course_name' => $activity['course_name'],
            'course_shortname' => $activity['course_shortname'],
            'course_id' => (int)$activity['course'],
            'activity_name' => $activity['activity_name'],
            'activity_id' => (int)$activity['instance'],
            'cmid' => (int)$entry->cmid,
            'entry_type' => $entry_type,
            'entry_preview' => $entry_preview,
            'entry_data' => json_encode($entry_data),
            'timecreated' => (int)$entry->timemodified, // Using timemodified as timecreated
            'timecreated_formatted' => userdate($entry->timemodified, get_string('strftimedatefullshort')),
            'timecreated_relative' => self::get_relative_time($entry->timemodified),
            'timemodified' => (int)$entry->timemodified,
            'timemodified_formatted' => userdate($entry->timemodified, get_string('strftimedatefullshort')),
            'timemodified_relative' => self::get_relative_time($entry->timemodified),
            'edit_url' => new \moodle_url('/mod/valuemapdoc/edit.php', [
                'id' => $activity['cmid'],
                'entryid' => $entry->id ]),
            'view_url' => new \moodle_url('/mod/valuemapdoc/view.php', [
                'id' => $activity['cmid'],
                'entryid' => $entry->id
            ]),
            'username' => $entry->username,
            'user_fullname' => fullname($entry),
            'ismaster' => (int)$entry->ismaster
        ];
    }

    /**
     * Get preview text from entry data
     * @param array $entry_data Entry data
     * @return string
     */
    private static function get_entry_preview($entry_data) {
        if (empty($entry_data)) {
            return 'No content available';
        }

        // Try to extract meaningful preview from ValueMapDoc specific fields
        $preview_fields = ['clientname', 'market', 'industry', 'role', 'businessgoal', 'situation', 'difficulty'];
        
        foreach ($preview_fields as $field) {
            if (isset($entry_data[$field]) && !empty(trim($entry_data[$field]))) {
                $text = strip_tags($entry_data[$field]);
                return strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
            }
        }

        // Fallback - use first available non-empty value
        foreach ($entry_data as $key => $value) {
            if (is_string($value) && !empty(trim($value))) {
                $text = strip_tags($value);
                return strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
            }
        }

        return 'No content available';
    }

    /**
     * Determine entry type from data structure specific to ValueMapDoc
     * @param array $entry_data Entry data
     * @return string
     */
    private static function get_entry_type($entry_data) {
        if (empty($entry_data)) {
            return 'unknown';
        }

        // Analyze ValueMapDoc specific structure
        if (!empty($entry_data['clientname']) || !empty($entry_data['market'])) {
            return 'customer_profile';
        } elseif (!empty($entry_data['difficulty']) && !empty($entry_data['differentiator'])) {
            return 'value_proposition';
        } elseif (!empty($entry_data['situation']) && !empty($entry_data['statusquo'])) {
            return 'pain_analysis';
        } elseif (!empty($entry_data['businessgoal']) && !empty($entry_data['strategy'])) {
            return 'value_map';
        } else {
            return 'general';
        }
    }

    /**
     * Get relative time string
     * @param int $timestamp Timestamp
     * @return string
     */
    private static function get_relative_time($timestamp) {
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return 'Just now';
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return $minutes . ' minutes ago';
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return $hours . ' hours ago';
        } elseif ($time_diff < 604800) {
            $days = floor($time_diff / 86400);
            return $days . ' days ago';
        } else {
            return userdate($timestamp, get_string('strftimedatefullshort'));
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'entries' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Entry ID'),
                    'course_name' => new external_value(PARAM_TEXT, 'Course name'),
                    'course_shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                    'course_id' => new external_value(PARAM_INT, 'Course ID'),
                    'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),
                    'activity_id' => new external_value(PARAM_INT, 'Activity ID'),
                    'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                    'entry_type' => new external_value(PARAM_TEXT, 'Entry type'),
                    'entry_preview' => new external_value(PARAM_TEXT, 'Entry preview'),
                    'entry_data' => new external_value(PARAM_RAW, 'Entry data'),
                    'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                    'timecreated_formatted' => new external_value(PARAM_TEXT, 'Formatted creation time'),
                    'timecreated_relative' => new external_value(PARAM_TEXT, 'Relative creation time'),
                    'timemodified' => new external_value(PARAM_INT, 'Modification timestamp'),
                    'timemodified_formatted' => new external_value(PARAM_TEXT, 'Formatted modification time'),
                    'timemodified_relative' => new external_value(PARAM_TEXT, 'Relative modification time'),
                    'edit_url' => new external_value(PARAM_URL, 'Edit URL'),
                    'view_url' => new external_value(PARAM_URL, 'View URL'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'user_fullname' => new external_value(PARAM_TEXT, 'User full name'),
                    'ismaster' => new external_value(PARAM_INT, 'Is master entry')
                ])
            ),
            'statistics' => new external_single_structure([
                'total_entries' => new external_value(PARAM_INT, 'Total entries count'),
                'unique_courses' => new external_value(PARAM_INT, 'Unique courses count'),
                'unique_activities' => new external_value(PARAM_INT, 'Unique activities count'),
                'courses_list' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Course name')
                ),
                'activities_list' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Activity name')
                )
            ])
        ]);
    }
}