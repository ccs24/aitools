<?php
namespace aitoolsub_valuemapdoc;

defined('MOODLE_INTERNAL') || die();

/**
 * Data provider for ValueMapDoc AI Tools
 * Handles access control based on course enrollment, capabilities, groups and visibility settings
 */
class data_provider {
    
    /**
     * Get accessible value map entries for user
     * 
     * @param int $userid User ID (0 = current user)
     * @return array Array of entry objects with course and activity info
     */
    public static function get_accessible_entries($userid = 0) {
        global $DB, $USER;
        
        if ($userid == 0) {
            $userid = $USER->id;
        }
        
        // Get user's group memberships for filtering
        $user_groups = self::get_user_groups($userid);
        
        // Base SQL - get entries with course and activity context
        $sql = "
            SELECT e.*, 
                   cm.id as cmid,
                   cm.course as courseid,
                   cm.groupmode,
                   v.name as activity_name,
                   c.fullname as course_name,
                   c.shortname as course_shortname
            FROM {valuemapdoc_entries} e
            JOIN {course_modules} cm ON cm.id = e.cid
            JOIN {valuemapdoc} v ON v.id = cm.instance
            JOIN {course} c ON c.id = cm.course
            WHERE 1=1
        ";
        
        $params = [];
        $where_conditions = [];
        
        // Apply group-based filtering
        $group_sql = self::build_group_filter_sql('e', $user_groups, 'entries');
        if ($group_sql['sql']) {
            $where_conditions[] = $group_sql['sql'];
            $params = array_merge($params, $group_sql['params']);
        }
        
        // Add WHERE conditions
        if (!empty($where_conditions)) {
            $sql .= " AND (" . implode(' OR ', $where_conditions) . ")";
        }
        
        $sql .= " ORDER BY c.fullname, v.name, e.timemodified DESC";
        
        try {
            $raw_entries = $DB->get_records_sql($sql, $params);
            
            // Filter by course access and capabilities
            $accessible_entries = [];
            foreach ($raw_entries as $entry) {
                if (self::can_access_course_module($entry->cmid, $entry->courseid, $userid, 'mod/valuemapdoc:manageentries')) {
                    $accessible_entries[$entry->id] = $entry;
                }
            }
            
            return $accessible_entries;
            
        } catch (\Exception $e) {
            error_log('ValueMapDoc entries access error: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Get all entries that user has access to, grouped by course and activity
     * 
     * @param int $userid User ID (0 for current user)
     * @param bool $include_separators Whether to include separator rows
     * @return array Array of entries with separator rows
     */
    public static function get_user_accessible_entries($userid = 0, $include_separators = true) {
        global $DB, $USER;
        
        if ($userid == 0) {
            $userid = $USER->id;
        }

        // Get all courses where user has access to ValueMapDoc activities
        $accessible_cms = self::get_accessible_course_modules($userid);
        
        if (empty($accessible_cms)) {
            return [];
        }

        $cmids = array_keys($accessible_cms);
        
        // Build SQL to get entries with user and course information
        list($incmids, $params) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        
        $sql = "
            SELECT e.*, 
                   u.firstname, u.lastname, u.username,
                   c.shortname as coursename, c.fullname as coursefullname,
                   v.name as activityname,
                   cm.id as cmid
            FROM {valuemapdoc_entries} e
            JOIN {user} u ON e.userid = u.id
            JOIN {course_modules} cm ON e.cid = cm.id
            JOIN {valuemapdoc} v ON cm.instance = v.id
            JOIN {course} c ON cm.course = c.id
            WHERE cm.id $incmids
            ORDER BY c.shortname, v.name, e.timemodified DESC
        ";
        
        $entries = $DB->get_records_sql($sql, $params);
        
        if (empty($entries)) {
            return [];
        }

        // Filter entries by group permissions
        $filtered_entries = self::filter_entries_by_groups($entries, $userid);
        
        if ($include_separators) {
            return self::add_separator_rows($filtered_entries);
        }
        
        return array_values($filtered_entries);
    }

    /**
     * Get course modules that user has access to
     * 
     * @param int $userid User ID
     * @return array Array of course modules indexed by cmid
     */
    private static function get_accessible_course_modules($userid) {
        global $DB;
        
        // Get all ValueMapDoc course modules
        $sql = "
            SELECT cm.id, cm.course, cm.instance, cm.groupmode,
                   c.shortname, c.fullname,
                   v.name as activityname
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id
            JOIN {course} c ON cm.course = c.id
            JOIN {valuemapdoc} v ON cm.instance = v.id
            WHERE m.name = 'valuemapdoc'
              AND cm.visible = 1
              AND c.visible = 1
        ";
        
        $cms = $DB->get_records_sql($sql);
        $accessible = [];
        
        foreach ($cms as $cm) {
            // Check if user can access this course module
            try {
                $context = \context_module::instance($cm->id);
                $course_context = \context_course::instance($cm->course);
                
                // Check basic course access
                if (!is_enrolled($course_context, $userid) && 
                    !has_capability('moodle/course:view', $course_context, $userid)) {
                    continue;
                }
                
                // Check ValueMapDoc view capability
                if (has_capability('mod/valuemapdoc:view', $context, $userid)) {
                    $accessible[$cm->id] = $cm;
                }
            } catch (\Exception $e) {
                // Skip inaccessible course modules
                continue;
            }
        }
        
        return $accessible;
    }

    /**
     * Filter entries based on group permissions
     * 
     * @param array $entries Raw entries from database
     * @param int $userid User ID
     * @return array Filtered entries
     */
    private static function filter_entries_by_groups($entries, $userid) {
        global $DB;
        
        $filtered = [];
        $group_cache = [];
        
        foreach ($entries as $entry) {
            $cmid = $entry->cmid;
            
            // Get group mode for this course module (cache it)
            if (!isset($group_cache[$cmid])) {
                $cm = get_coursemodule_from_id('valuemapdoc', $cmid);
                $group_cache[$cmid] = [
                    'groupmode' => groups_get_activity_groupmode($cm),
                    'cm' => $cm
                ];
            }
            
            $groupmode = $group_cache[$cmid]['groupmode'];
            $cm = $group_cache[$cmid]['cm'];
            
            // No groups or visible groups - show all entries
            if ($groupmode == NOGROUPS || $groupmode == VISIBLEGROUPS) {
                $filtered[] = $entry;
                continue;
            }
            
            // Separate groups - check permissions
            if ($groupmode == SEPARATEGROUPS) {
                $context = \context_module::instance($cmid);
                
                // Users with accessallgroups can see everything
                if (has_capability('moodle/site:accessallgroups', $context, $userid)) {
                    $filtered[] = $entry;
                    continue;
                }
                
                // Check if user is in the same group as the entry
                $user_groups = groups_get_user_groups($cm->course, $userid);
                if (!empty($user_groups[0]) && in_array($entry->groupid, $user_groups[0])) {
                    $filtered[] = $entry;
                } elseif ($entry->groupid == 0) {
                    // Entry not assigned to any group - show it
                    $filtered[] = $entry;
                }
            }
        }
        
        return $filtered;
    }

    

    /**
     * Add separator rows between different courses/activities
     * 
     * @param array $entries Filtered entries
     * @return array Entries with separator rows
     */
    private static function add_separator_rows($entries) {
        if (empty($entries)) {
            return [];
        }
        
        $result = [];
        $last_course = null;
        $last_activity = null;
        
        foreach ($entries as $entry) {
            $current_course = $entry->coursename;
            $current_activity = $entry->activityname;
            
            // Add separator if course or activity changed
            if ($last_course !== $current_course || $last_activity !== $current_activity) {
                $separator = (object)[
                    'id' => 'separator_' . $entry->cmid,
                    'is_separator' => true,
                    'separator_text' => get_string('separator_row', 'local_valuemapdoc_viewer', [
                        'course' => $current_course,
                        'activity' => $current_activity
                    ]),
                    'coursename' => $current_course,
                    'activityname' => $current_activity,
                    'cmid' => $entry->cmid
                ];
                
                // Add empty fields for consistency with regular entries
                $fields = ['market', 'industry', 'role', 'businessgoal', 'strategy', 'difficulty',
                          'situation', 'statusquo', 'coi', 'differentiator', 'impact', 'newstate',
                          'successmetric', 'impactstrategy', 'impactbusinessgoal', 'impactothers',
                          'proof', 'time2results', 'quote', 'clientname', 'username'];
                
                foreach ($fields as $field) {
                    $separator->$field = '';
                }
                
                $result[] = $separator;
                
                $last_course = $current_course;
                $last_activity = $current_activity;
            }
            
            $result[] = $entry;
        }
        
        return $result;
    }

        /**
     * Get ValueMapDoc field columns for display
     * 
     * @return array Array of field names
     */
    public static function get_display_columns() {
        // Get the same columns as used in the main module
        $columns = ['market', 'industry', 'role', 'businessgoal', 'strategy', 'difficulty',
                   'situation', 'statusquo', 'coi', 'differentiator', 'impact', 'newstate',
                   'successmetric', 'impactstrategy', 'impactbusinessgoal', 'impactothers',
                   'proof', 'time2results', 'quote', 'clientname'];
        
        return $columns;
    }

    /**
     * Get enhanced columns including meta information
     * 
     * @return array Array of column definitions
     */
    public static function get_enhanced_columns() {
        $base_columns = self::get_display_columns();
        $enhanced = [];
        
        // Add meta columns first
        $enhanced[] = [
            'title' => get_string('author', 'local_valuemapdoc_viewer'),
            'field' => 'username',
            'hozAlign' => 'left',
            'headerSort' => true,
            'width' => 120,
            'headerFilter' => 'input',
            'editable' => false
        ];
        
        // Add ValueMap columns
        foreach ($base_columns as $column) {
            $enhanced[] = [
                'title' => get_string($column, 'mod_valuemapdoc'),
                'field' => $column,
                'hozAlign' => 'left',
                'headerSort' => true,
                'width' => 150,
                'headerFilter' => 'input',
                'editable' => false
            ];
        }
        
        return $enhanced;
    }



    
    /**
     * Get accessible content for user
     * 
     * @param int $userid User ID (0 = current user)
     * @return array Array of content objects with course and activity info
     */
    public static function get_accessible_content($userid = 0) {
        global $DB, $USER;
        
        if ($userid == 0) {
            $userid = $USER->id;
        }
        
        // Get user's group memberships for filtering
        $user_groups = self::get_user_groups($userid);
        
        // Base SQL - get content with course and activity context
        $sql = "
            SELECT co.*, 
                   cm.id as cmid,
                   cm.course as courseid,
                   cm.groupmode,
                   v.name as activity_name,
                   c.fullname as course_name,
                   c.shortname as course_shortname,
                   t.name as template_name,
                   t.templatetype
            FROM {valuemapdoc_content} co
            JOIN {course_modules} cm ON cm.id = co.cmid
            JOIN {valuemapdoc} v ON v.id = cm.instance
            JOIN {course} c ON c.id = cm.course
            LEFT JOIN {valuemapdoc_templates} t ON t.id = co.templateid
            WHERE 1=1
        ";
        
        $params = [];
        $where_conditions = [];
        
        // Apply visibility filtering
        // visibility = 1: only own content
        // visibility = 0: content based on group rules
        $visibility_sql = "
            (co.visibility = 1 AND co.userid = :vis_userid)
            OR 
            (co.visibility = 0 AND (" . self::build_group_filter_sql('co', $user_groups, 'content')['sql'] . "))
        ";
        
        $where_conditions[] = $visibility_sql;
        $params['vis_userid'] = $userid;
        
        // Add group filter params
        $group_sql = self::build_group_filter_sql('co', $user_groups, 'content');
        $params = array_merge($params, $group_sql['params']);
        
        // Add WHERE conditions
        if (!empty($where_conditions)) {
            $sql .= " AND (" . implode(' OR ', $where_conditions) . ")";
        }
        
        $sql .= " ORDER BY c.fullname, v.name, co.timecreated DESC";
        
        try {
            $raw_content = $DB->get_records_sql($sql, $params);
            
            // Filter by course access and capabilities
            $accessible_content = [];
            foreach ($raw_content as $content) {
                if (self::can_access_course_module($content->cmid, $content->courseid, $userid, 'mod/valuemapdoc:view')) {
                    $accessible_content[$content->id] = $content;
                }
            }
            
            return $accessible_content;
            
        } catch (\Exception $e) {
            error_log('ValueMapDoc content access error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get accessible templates for user
     * 
     * @param int $userid User ID (0 = current user)
     * @param int $courseid Course ID for filtering (0 = all courses)
     * @return array Array of template objects
     */
    public static function get_accessible_templates($userid = 0, $courseid = 0) {
        global $DB, $USER;
        
        if ($userid == 0) {
            $userid = $USER->id;
        }
        
        $user_groups = self::get_user_groups($userid);
        
        $sql = "
            SELECT t.*,
                   c.fullname as course_name,
                   c.shortname as course_shortname
            FROM {valuemapdoc_templates} t
            LEFT JOIN {course} c ON c.id = t.courseid
            WHERE t.isactive = 1 AND (
                t.scope = 'system'
                OR (t.scope = 'course' AND t.courseid = :course_filter)
                OR (t.scope = 'user' AND t.userid = :user_filter)
                OR (t.scope = 'group' AND t.groupid IN (" . implode(',', array_merge(array_keys($user_groups), [0])) . "))
            )
        ";
        
        $params = [
            'course_filter' => $courseid,
            'user_filter' => $userid
        ];
        
        // Filter by course if specified
        if ($courseid > 0) {
            $sql .= " AND (t.courseid = 0 OR t.courseid = :courseid)";
            $params['courseid'] = $courseid;
        }
        
        $sql .= " ORDER BY t.scope, t.name";
        
        try {
            $raw_templates = $DB->get_records_sql($sql, $params);
            
            // Filter by course access for course/group scoped templates
            $accessible_templates = [];
            foreach ($raw_templates as $template) {
                $has_access = false;
                
                switch ($template->scope) {
                    case 'system':
                        $has_access = true; // System templates accessible to all
                        break;
                        
                    case 'user':
                        $has_access = ($template->userid == $userid);
                        break;
                        
                    case 'course':
                    case 'group':
                        if ($template->courseid > 0) {
                            // Check course enrollment and capabilities
                            $context_course = \context_course::instance($template->courseid);
                            $has_access = (is_enrolled($context_course, $userid) || 
                                         has_capability('moodle/course:view', $context_course, $userid));
                            
                            // Additional check for template management capabilities
                            if ($has_access && $template->scope == 'course') {
                                $has_access = has_capability('mod/valuemapdoc:managecoursetemplates', $context_course, $userid);
                            }
                        } else {
                            $has_access = true; // Templates without specific course
                        }
                        break;
                }
                
                if ($has_access) {
                    $accessible_templates[$template->id] = $template;
                }
            }
            
            return $accessible_templates;
            
        } catch (\Exception $e) {
            error_log('ValueMapDoc templates access error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user can access specific course module
     * 
     * @param int $cmid Course module ID
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param string $capability Required capability
     * @return bool
     */
    private static function can_access_course_module($cmid, $courseid, $userid, $capability) {
        try {
            // Check course enrollment first
            $context_course = \context_course::instance($courseid);
            if (!is_enrolled($context_course, $userid) && !has_capability('moodle/course:view', $context_course, $userid)) {
                return false;
            }
            
            // Check course module visibility and access
            $modinfo = get_fast_modinfo($courseid, $userid);
            if (!isset($modinfo->cms[$cmid])) {
                return false; // Module doesn't exist or not visible
            }
            
            $cm = $modinfo->cms[$cmid];
            if (!$cm->uservisible) {
                return false; // Module not visible to this user
            }
            
            // Check specific capability for the module
            $context_module = \context_module::instance($cmid);
            if (!has_capability($capability, $context_module, $userid)) {
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Error checking course module access: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's group memberships across all courses
     * 
     * @param int $userid User ID
     * @return array Array of group IDs with course context
     */
    private static function get_user_groups($userid) {
        global $DB;
        
        try {
            $sql = "
                SELECT gm.groupid, g.courseid
                FROM {groups_members} gm
                JOIN {groups} g ON g.id = gm.groupid
                WHERE gm.userid = :userid
            ";
            
            $groups = $DB->get_records_sql($sql, ['userid' => $userid]);
            
            // Convert to simple array for easier processing
            $user_groups = [];
            foreach ($groups as $group) {
                $user_groups[$group->groupid] = $group->courseid;
            }
            
            return $user_groups;
        } catch (\Exception $e) {
            error_log('Error getting user groups: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build SQL filter for group-based access control
     * 
     * @param string $table_alias Table alias (e.g., 'e', 'co')
     * @param array $user_groups User's group memberships
     * @param string $context Context ('entries' or 'content')
     * @return array SQL string and parameters
     */
    private static function build_group_filter_sql($table_alias, $user_groups, $context) {
        $conditions = [];
        $params = [];
        
        if (empty($user_groups)) {
            // User not in any groups - can only see content from activities with no group mode
            $conditions[] = "cm.groupmode = 0";
        } else {
            // User in groups - apply group logic
            $group_ids = array_keys($user_groups);
            $group_placeholders = [];
            
            foreach ($group_ids as $i => $groupid) {
                $param_name = $context . '_groupid_' . $i;
                $group_placeholders[] = ':' . $param_name;
                $params[$param_name] = $groupid;
            }
            
            $conditions[] = "cm.groupmode = 0"; // No groups = visible to all
            $conditions[] = "cm.groupmode = 1"; // Visible groups = visible to all
            $conditions[] = "(cm.groupmode = 2 AND {$table_alias}.groupid IN (" . implode(',', $group_placeholders) . "))"; // Separate groups = only my groups
        }
        
        return [
            'sql' => implode(' OR ', $conditions),
            'params' => $params
        ];
    }
    
    /**
     * Get statistics for dashboard
     * 
     * @param int $userid User ID (0 = current user)
     * @return array Statistics array
     */
    public static function get_user_statistics($userid = 0) {
        global $USER;
        
        if ($userid == 0) {
            $userid = $USER->id;
        }
        
        $entries = self::get_accessible_entries($userid);
        $content = self::get_accessible_content($userid);
        $templates = self::get_accessible_templates($userid);
        
        // Calculate weekly stats
        $week_ago = time() - (7 * 24 * 60 * 60);
        $entries_this_week = 0;
        $content_this_week = 0;
        
        foreach ($entries as $entry) {
            if ($entry->timemodified >= $week_ago) {
                $entries_this_week++;
            }
        }
        
        foreach ($content as $item) {
            if ($item->timecreated >= $week_ago) {
                $content_this_week++;
            }
        }
        
        return [
            'total_entries' => count($entries),
            'total_content' => count($content),
            'total_templates' => count($templates),
            'entries_this_week' => $entries_this_week,
            'content_this_week' => $content_this_week,
            'unique_courses' => count(array_unique(array_column($content, 'courseid'))),
            'unique_activities' => count(array_unique(array_column($content, 'cmid')))
        ];
    }
    
    /**
     * Get grouped content for display (used by my_content.php)
     * 
     * @param int $userid User ID (0 = current user)
     * @return array Grouped content by course and activity
     */
    public static function get_grouped_content($userid = 0) {
        $content = self::get_accessible_content($userid);
        
        $grouped = [];
        
        foreach ($content as $item) {
            $courseid = $item->courseid;
            $cmid = $item->cmid;
            
            // Initialize course if not exists
            if (!isset($grouped[$courseid])) {
                $grouped[$courseid] = [
                    'id' => $courseid,
                    'name' => $item->course_name,
                    'shortname' => $item->course_shortname,
                    'activities' => [],
                    'content_count' => 0
                ];
            }
            
            // Initialize activity if not exists
            if (!isset($grouped[$courseid]['activities'][$cmid])) {
                $grouped[$courseid]['activities'][$cmid] = [
                    'id' => $cmid,
                    'name' => $item->activity_name,
                    'course_id' => $courseid,
                    'content' => [],
                    'content_count' => 0
                ];
            }
            
            // Add content item
            $grouped[$courseid]['activities'][$cmid]['content'][] = $item;
            $grouped[$courseid]['activities'][$cmid]['content_count']++;
            $grouped[$courseid]['content_count']++;
        }
        
        // Convert to indexed arrays for Mustache
        foreach ($grouped as &$course) {
            $course['activities'] = array_values($course['activities']);
        }
        
        return array_values($grouped);
    }
}