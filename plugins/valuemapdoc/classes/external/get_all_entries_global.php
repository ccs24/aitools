<?php
namespace aitoolsub_valuemapdoc\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;

/**
 * External service for getting all entries global
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
     * Get all entries for user across all courses
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

        return self::get_user_entries($params['userid']);
    }

    /**
     * Get all user's value map entries across all courses
     * @param int $userid User ID
     * @return array
     */
    private static function get_user_entries($userid) {
        global $DB;

        // SQL using actual column names from valuemapdoc_entries table
        // Note: table uses 'cid' instead of 'cmid'
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
                   c.fullname as course_name, 
                   c.shortname as course_shortname,
                   v.name as activity_name, 
                   v.id as activity_id,
                   cm.id as cm_id
            FROM {valuemapdoc_entries} e
            LEFT JOIN {course_modules} cm ON cm.id = e.cid
            LEFT JOIN {valuemapdoc} v ON v.id = cm.instance
            LEFT JOIN {course} c ON c.id = e.course
            WHERE e.userid = :userid
            ORDER BY e.timemodified DESC, c.fullname, v.name
        ";

        try {
            $entries = $DB->get_records_sql($sql, ['userid' => $userid]);
        } catch (Exception $e) {
            error_log('SQL query failed: ' . $e->getMessage());
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

        $formatted_entries = [];
        $statistics = [
            'total_entries' => 0,
            'unique_courses' => [],
            'unique_activities' => []
        ];

        foreach ($entries as $entry) {
            // Build entry data from individual columns
            $entry_data = [
                'market' => $entry->market,
                'industry' => $entry->industry,
                'role' => $entry->role,
                'businessgoal' => $entry->businessgoal,
                'strategy' => $entry->strategy,
                'difficulty' => $entry->difficulty,
                'situation' => $entry->situation,
                'statusquo' => $entry->statusquo,
                'coi' => $entry->coi,
                'differentiator' => $entry->differentiator,
                'impact' => $entry->impact,
                'newstate' => $entry->newstate,
                'successmetric' => $entry->successmetric,
                'impactstrategy' => $entry->impactstrategy,
                'impactbusinessgoal' => $entry->impactbusinessgoal,
                'impactothers' => $entry->impactothers,
                'proof' => $entry->proof,
                'time2results' => $entry->time2results,
                'quote' => $entry->quote,
                'clientname' => $entry->clientname
            ];
            
            // Extract preview and type
            $entry_preview = self::get_entry_preview($entry_data);
            $entry_type = self::get_entry_type($entry_data);
            
            // Create creation time (use timemodified if timecreated doesn't exist)
            $timecreated = $entry->timemodified; // Table doesn't seem to have timecreated
            
            $formatted_entries[] = [
                'id' => (int)$entry->id,
                'course_name' => $entry->course_name ?? 'Unknown Course',
                'course_shortname' => $entry->course_shortname ?? 'unknown',
                'course_id' => (int)($entry->course ?? 0),
                'activity_name' => $entry->activity_name ?? 'Unknown Activity',
                'activity_id' => (int)($entry->activity_id ?? 0),
                'cmid' => (int)($entry->cmid ?? 0),
                'entry_type' => $entry_type,
                'entry_preview' => $entry_preview,
                'entry_data' => json_encode($entry_data), // <- NAPRAWKA: konwertuj array na JSON string
                'timecreated' => (int)$timecreated,
                'timecreated_formatted' => userdate($timecreated, get_string('strftimedatefullshort')),
                'timecreated_relative' => self::get_relative_time($timecreated),
                'timemodified' => (int)$entry->timemodified,
                'timemodified_formatted' => userdate($entry->timemodified, get_string('strftimedatefullshort')),
                'timemodified_relative' => self::get_relative_time($entry->timemodified),
                'edit_url' => (new \moodle_url('/mod/valuemapdoc/view.php', [
                    'id' => $entry->cmid
                ]))->out(false),
                'view_url' => (new \moodle_url('/mod/valuemapdoc/view.php', [
                    'id' => $entry->cmid,
                    'entryid' => $entry->id
                ]))->out(false)
            ];

            // Update statistics
            $statistics['total_entries']++;
            if ($entry->course) {
                $statistics['unique_courses'][$entry->course] = $entry->course_name ?? 'Unknown';
            }
            if ($entry->cmid) {
                $statistics['unique_activities'][$entry->cmid] = $entry->activity_name ?? 'Unknown';
            }
        }

        return [
            'entries' => $formatted_entries,
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
                    'view_url' => new external_value(PARAM_URL, 'View URL')
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