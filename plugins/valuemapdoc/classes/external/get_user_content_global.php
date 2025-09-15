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

class get_user_content_global extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute($userid = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        // Use current user if not specified
        if ($userid == 0) {
            $userid = $USER->id;
        }

        // Security check - users can only see their own content unless they're admin
        if ($userid != $USER->id && !has_capability('local/aitools:manage', $context)) {
            throw new \moodle_exception('nopermission');
        }

        return self::get_user_content_grouped($userid);
    }

    /**
     * Get user's content grouped by courses and activities
     */
    public static function get_user_content_grouped($userid = 0) {
        global $DB, $USER;

        if ($userid == 0) {
            $userid = $USER->id;
        }

        // Get all user's content with course and activity information
        $sql = "
            SELECT c.id, c.name, c.content, c.timecreated, c.effectiveness,
                   c.templateid, t.templatetype, t.name as template_name,
                   co.id as courseid, co.fullname as course_name, co.shortname as course_shortname,
                   v.name as activity_name, cm.id as cmid,
                   c.customprompt, c.status
            FROM {valuemapdoc_content} c
            JOIN {course_modules} cm ON cm.id = c.cmid  
            JOIN {valuemapdoc} v ON v.id = cm.instance
            JOIN {course} co ON co.id = cm.course
            LEFT JOIN {valuemapdoc_templates} t ON t.id = c.templateid
            WHERE c.userid = :userid
            ORDER BY co.fullname, v.name, c.timecreated DESC
        ";

        $content_records = $DB->get_records_sql($sql, ['userid' => $userid]);

        // Group by courses and activities
        $courses = [];
        $statistics = [
            'total_content' => 0,
            'total_courses' => 0,
            'total_activities' => 0
        ];

        foreach ($content_records as $record) {
            $courseid = $record->courseid;
            $cmid = $record->cmid;

            // Initialize course if not exists
            if (!isset($courses[$courseid])) {
                $courses[$courseid] = [
                    'id' => $courseid,
                    'name' => $record->course_name,
                    'shortname' => $record->course_shortname,
                    'activities' => [],
                    'content_count' => 0
                ];
                $statistics['total_courses']++;
            }

            // Initialize activity if not exists
            if (!isset($courses[$courseid]['activities'][$cmid])) {
                $courses[$courseid]['activities'][$cmid] = [
                    'id' => $cmid,
                    'name' => $record->activity_name,
                    'course_id' => $courseid,
                    'content' => [],
                    'content_count' => 0
                ];
                $statistics['total_activities']++;
            }

            // Add content to activity
            $content_item = [
                'id' => $record->id,
                'name' => $record->name,
                'template_type' => $record->templatetype ?? 'custom',
                'template_name' => $record->template_name ?? get_string('custom_prompt', 'aitoolsub_valuemapdoc'),
                'content_preview' => self::get_content_preview($record->content),
                'timecreated' => userdate($record->timecreated),
                'timecreated_relative' => self::get_relative_time($record->timecreated),
                'effectiveness' => $record->effectiveness ?? 0,
                'status' => $record->status ?? 'ready',
                'edit_url' => new \moodle_url('/mod/valuemapdoc/edit_content.php', [
                    'id' => $cmid,
                    'docid' => $record->id
                ]),
                'view_url' => new \moodle_url('/mod/valuemapdoc/rate_content.php', [
                    'id' => $cmid,
                    'docid' => $record->id
                ])
            ];

            $courses[$courseid]['activities'][$cmid]['content'][] = $content_item;
            $courses[$courseid]['activities'][$cmid]['content_count']++;
            $courses[$courseid]['content_count']++;
            $statistics['total_content']++;
        }

        // Convert associative arrays to indexed arrays for Mustache
        foreach ($courses as &$course) {
            $course['activities'] = array_values($course['activities']);
        }
        $courses = array_values($courses);

        // Get recent content (last 10 items)
        $recent_content = array_slice($content_records, 0, 10);
        $recent_formatted = [];
        foreach ($recent_content as $recent) {
            $recent_formatted[] = [
                'id' => $recent->id,
                'name' => $recent->name,
                'course_name' => $recent->course_name,
                'activity_name' => $recent->activity_name,
                'timecreated_relative' => self::get_relative_time($recent->timecreated),
                'view_url' => new \moodle_url('/mod/valuemapdoc/rate_content.php', [
                    'id' => $recent->cmid,
                    'docid' => $recent->id
                ])
            ];
        }

        return [
            'courses' => $courses,
            'statistics' => $statistics,
            'recent_content' => $recent_formatted
        ];
    }

    /**
     * Get content preview (first 200 characters)
     */
    private static function get_content_preview($content) {
        if (empty($content)) {
            return get_string('no_content', 'aitoolsub_valuemapdoc');
        }
        
        $plain_text = strip_tags($content);
        return strlen($plain_text) > 200 ? substr($plain_text, 0, 200) . '...' : $plain_text;
    }

    /**
     * Get relative time string
     */
    private static function get_relative_time($timestamp) {
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

    public static function execute_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'name' => new external_value(PARAM_TEXT, 'Course name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                    'content_count' => new external_value(PARAM_INT, 'Total content count'),
                    'activities' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Activity CM ID'),
                            'name' => new external_value(PARAM_TEXT, 'Activity name'),
                            'content_count' => new external_value(PARAM_INT, 'Content count'),
                            'content' => new external_multiple_structure(
                                new external_single_structure([
                                    'id' => new external_value(PARAM_INT, 'Content ID'),
                                    'name' => new external_value(PARAM_TEXT, 'Content name'),
                                    'template_type' => new external_value(PARAM_TEXT, 'Template type'),
                                    'template_name' => new external_value(PARAM_TEXT, 'Template name'),
                                    'content_preview' => new external_value(PARAM_TEXT, 'Content preview'),
                                    'timecreated' => new external_value(PARAM_TEXT, 'Creation time'),
                                    'timecreated_relative' => new external_value(PARAM_TEXT, 'Relative time'),
                                    'effectiveness' => new external_value(PARAM_INT, 'Effectiveness score'),
                                    'status' => new external_value(PARAM_TEXT, 'Content status'),
                                    'edit_url' => new external_value(PARAM_URL, 'Edit URL'),
                                    'view_url' => new external_value(PARAM_URL, 'View URL')
                                ])
                            )
                        ])
                    )
                ])
            ),
            'statistics' => new external_single_structure([
                'total_content' => new external_value(PARAM_INT, 'Total content count'),
                'total_courses' => new external_value(PARAM_INT, 'Total courses count'),
                'total_activities' => new external_value(PARAM_INT, 'Total activities count')
            ]),
            'recent_content' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Content ID'),
                    'name' => new external_value(PARAM_TEXT, 'Content name'),
                    'course_name' => new external_value(PARAM_TEXT, 'Course name'),
                    'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),
                    'timecreated_relative' => new external_value(PARAM_TEXT, 'Relative time'),
                    'view_url' => new external_value(PARAM_URL, 'View URL')
                ])
            )
        ]);
    }
}