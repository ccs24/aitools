<?php
namespace local_aitools;

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort access manager for AI Tools subplugins
 */
class cohort_manager {
    
    /**
     * Check if user has access to subplugin based on cohort membership
     * 
     * @param string $subplugin Component name (e.g., 'aitoolsub_valuemapdoc')
     * @param int $userid User ID (0 = current user)
     * @return bool
     */
    public static function has_cohort_access($subplugin, $userid = 0) {
        global $DB, $USER;
        
        if ($userid == 0) {
            $userid = $USER->id;
        }
        
        try {
            // Check if cohorts table exists
            if (!self::cohorts_table_exists()) {
                return true; // No restrictions if table doesn't exist
            }
            
            // Get cohort restrictions for this subplugin
            $restricted_cohorts = $DB->get_records('local_aitools_cohorts', 
                ['subplugin' => $subplugin], '', 'cohortid');
            
            // If no cohort restrictions, allow access for everyone
            if (empty($restricted_cohorts)) {
                return true;
            }
            
            // Get user's cohort memberships
            $user_cohorts = $DB->get_records('cohort_members', 
                ['userid' => $userid], '', 'cohortid');
            
            // Check if user is in any of the required cohorts
            foreach ($restricted_cohorts as $restriction) {
                if (isset($user_cohorts[$restriction->cohortid])) {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            // If there's an error, be permissive
            error_log('cohort_manager error: ' . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Check if the cohorts table exists
     * 
     * @return bool
     */
    private static function cohorts_table_exists() {
        global $DB;
        
        try {
            $dbman = $DB->get_manager();
            return $dbman->table_exists('local_aitools_cohorts');
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get cohorts configured for a subplugin
     * 
     * @param string $subplugin Component name
     * @return array Array of cohort objects
     */
    public static function get_subplugin_cohorts($subplugin) {
        global $DB;
        
        try {
            if (!self::cohorts_table_exists()) {
                return [];
            }
            
            $sql = "SELECT c.id, c.name, c.idnumber, c.description
                    FROM {cohort} c
                    JOIN {local_aitools_cohorts} ac ON ac.cohortid = c.id
                    WHERE ac.subplugin = ?
                    ORDER BY c.name";
            
            return $DB->get_records_sql($sql, [$subplugin]);
            
        } catch (\Exception $e) {
            error_log('get_subplugin_cohorts error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add cohort restriction for subplugin
     * 
     * @param string $subplugin Component name
     * @param int $cohortid Cohort ID
     * @return bool Success
     */
    public static function add_cohort_restriction($subplugin, $cohortid) {
        global $DB, $USER;
        
        try {
            if (!self::cohorts_table_exists()) {
                return false;
            }
            
            // Check if restriction already exists
            if ($DB->record_exists('local_aitools_cohorts', [
                'subplugin' => $subplugin, 
                'cohortid' => $cohortid
            ])) {
                return false;
            }
            
            $record = new \stdClass();
            $record->subplugin = $subplugin;
            $record->cohortid = $cohortid;
            $record->timecreated = time();
            $record->timemodified = time();
            $record->usermodified = $USER->id;
            
            return (bool)$DB->insert_record('local_aitools_cohorts', $record);
            
        } catch (\Exception $e) {
            error_log('add_cohort_restriction error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove cohort restriction for subplugin
     * 
     * @param string $subplugin Component name
     * @param int $cohortid Cohort ID
     * @return bool Success
     */
    public static function remove_cohort_restriction($subplugin, $cohortid) {
        global $DB;
        
        try {
            if (!self::cohorts_table_exists()) {
                return false;
            }
            
            return $DB->delete_records('local_aitools_cohorts', [
                'subplugin' => $subplugin,
                'cohortid' => $cohortid
            ]);
            
        } catch (\Exception $e) {
            error_log('remove_cohort_restriction error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cohort restrictions for subplugin
     * 
     * @param string $subplugin Component name
     * @return bool Success
     */
    public static function clear_cohort_restrictions($subplugin) {
        global $DB;
        
        try {
            if (!self::cohorts_table_exists()) {
                return false;
            }
            
            return $DB->delete_records('local_aitools_cohorts', ['subplugin' => $subplugin]);
            
        } catch (\Exception $e) {
            error_log('clear_cohort_restrictions error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all available cohorts for selection
     * 
     * @return array Array of cohort objects
     */
    public static function get_all_cohorts() {
        global $DB;
        
        try {
            return $DB->get_records('cohort', null, 'name ASC', 'id, name, idnumber, description');
        } catch (\Exception $e) {
            error_log('get_all_cohorts error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cohort access statistics for dashboard
     * 
     * @param string $subplugin Component name
     * @return array Statistics
     */
    public static function get_access_statistics($subplugin) {
        global $DB;
        
        try {
            if (!self::cohorts_table_exists()) {
                return [
                    'total_users' => 0,
                    'total_cohorts' => 0,
                    'unrestricted' => true
                ];
            }
            
            // Get total users in configured cohorts
            $sql = "SELECT COUNT(DISTINCT cm.userid) as total_users
                    FROM {cohort_members} cm
                    JOIN {local_aitools_cohorts} ac ON ac.cohortid = cm.cohortid
                    WHERE ac.subplugin = ?";
            
            $result = $DB->get_record_sql($sql, [$subplugin]);
            $total_users = $result ? $result->total_users : 0;
            
            // Get number of configured cohorts
            $total_cohorts = $DB->count_records('local_aitools_cohorts', ['subplugin' => $subplugin]);
            
            return [
                'total_users' => $total_users,
                'total_cohorts' => $total_cohorts,
                'unrestricted' => ($total_cohorts == 0)
            ];
            
        } catch (\Exception $e) {
            error_log('get_access_statistics error: ' . $e->getMessage());
            return [
                'total_users' => 0,
                'total_cohorts' => 0,
                'unrestricted' => true
            ];
        }
    }
}