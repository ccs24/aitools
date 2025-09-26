<?php
namespace aitoolsub_cluster;

defined('MOODLE_INTERNAL') || die();

/**
 * Cluster plugin for Sales War Room - strategic sales campaigns
 * 
 * Manages clusters (strategic campaigns), companies, persons, and messages
 * with integration to valuemapdoc for AI-powered sales content generation.
 */
class plugin implements \local_aitools\plugin_interface {
    
    /**
     * Get dashboard blocks for cluster management
     * 
     * @return array Array of dashboard blocks configuration
     */
    public function get_dashboard_blocks() {
        global $USER, $DB;
        
        if (!$this->has_access()) {
            return [];
        }
        
        // Get user's cluster summary
        $cluster_summary = $this->get_cluster_summary();
        $recent_activity = $this->get_recent_activity();
        
        return [
            [
                'title' => 'My Sales Clusters',
                'template' => 'aitoolsub_cluster/dashboard_summary',
                'size' => 'large',
                'priority' => 15,
                'data' => $cluster_summary
            ],
            [
                'title' => 'Recent Cluster Activity',
                'template' => 'aitoolsub_cluster/dashboard_activity',
                'size' => 'medium',
                'priority' => 20,
                'data' => $recent_activity
            ]
        ];
    }
    
    /**
     * Get tools provided by cluster plugin
     * 
     * @return array Array of tools configuration
     */
    public function get_tools() {
        if (!$this->has_access()) {
            return [];
        }
        
        return [
            'sales_campaigns' => [
                [
                    'name' => 'Cluster Management',
                    'description' => 'Manage strategic sales campaigns and clusters',
                    'url' => '/local/aitools/plugins/cluster/index.php',
                    'icon' => 'fa-bullseye',
                    'category' => 'Sales War Room'
                ],
                [
                    'name' => 'Company Research',
                    'description' => 'Research companies and manage contact database',
                    'url' => '/local/aitools/plugins/cluster/companies.php',
                    'icon' => 'fa-building',
                    'category' => 'Sales War Room'
                ],
                [
                    'name' => 'Message Generator',
                    'description' => 'AI-powered sales message generation and management',
                    'url' => '/local/aitools/plugins/cluster/messages.php',
                    'icon' => 'fa-comments',
                    'category' => 'Sales War Room'
                ]
            ]
        ];
    }
    
    /**
     * Get plugin information
     * 
     * @return array Plugin metadata
     */
    public function get_plugin_info() {
        return [
            'name' => 'Sales Clusters',
            'description' => 'Strategic sales campaign management with AI-powered messaging',
            'version' => '1.0.0',
            'author' => 'AI Tools Team',
            'category' => 'Sales War Room',
            'requires_valuemapdoc' => true
        ];
    }
    
    /**
     * Check if user has access to cluster plugin
     * 
     * @return bool
     */
    public function has_access() {
        global $USER;
        
        // Check basic AI Tools access
        $context = \context_system::instance();
        if (!has_capability('local/aitools:view', $context)) {
            return false;
        }
        
        // Check if user has access to Sales War Room (through cohorts or capabilities)
        if (has_capability('local/aitools:manage', $context)) {
            return true; // Managers always have access
        }
        
        // Check cohort access for cluster plugin specifically
        return \local_aitools\manager::has_cohort_access('aitoolsub_cluster');
    }
    
    /**
     * Get cluster summary for dashboard
     * 
     * @return array Summary data
     */
    private function get_cluster_summary() {
        global $USER, $DB;
        
        $sql = "SELECT 
                    COUNT(*) as total_clusters,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_clusters,
                    SUM(CASE WHEN status = 'planning' THEN 1 ELSE 0 END) as planning_clusters,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_clusters
                FROM {aitools_clusters} 
                WHERE created_by = :userid";
        
        $summary = $DB->get_record_sql($sql, ['userid' => $USER->id]);
        
        if (!$summary) {
            $summary = (object)[
                'total_clusters' => 0,
                'active_clusters' => 0,
                'planning_clusters' => 0,
                'completed_clusters' => 0
            ];
        }
        
        // Get companies and persons count
        $sql_companies = "SELECT COUNT(DISTINCT cc.id) as total_companies,
                                 COUNT(DISTINCT cp.id) as total_persons
                          FROM {aitools_clusters} c
                          LEFT JOIN {aitools_cluster_companies} cc ON c.id = cc.cluster_id
                          LEFT JOIN {aitools_cluster_persons} cp ON cc.id = cp.company_id
                          WHERE c.created_by = :userid";
        
        $details = $DB->get_record_sql($sql_companies, ['userid' => $USER->id]);
        
        $summary->total_companies = $details->total_companies ?? 0;
        $summary->total_persons = $details->total_persons ?? 0;
        
        // Get recent clusters
        $recent_clusters = $DB->get_records('aitools_clusters', 
            ['created_by' => $USER->id], 
            'modified_date DESC', 
            'id, name, status, modified_date', 
            0, 5
        );
        
        $summary->recent_clusters = array_values($recent_clusters);
        
        return $summary;
    }
    
    /**
     * Get recent activity for dashboard
     * 
     * @return array Activity data
     */
    private function get_recent_activity() {
        global $USER, $DB;
        
        // Get recent activity from activity log
        $sql = "SELECT al.*, c.name as cluster_name
                FROM {aitools_activity_log} al
                LEFT JOIN {aitools_clusters} c ON al.resource_id = c.id AND al.resource_type = 'cluster'
                WHERE al.user_id = :userid 
                  AND al.resource_type IN ('cluster', 'company', 'person', 'message')
                ORDER BY al.timestamp DESC";
        
        $activities = $DB->get_records_sql($sql, ['userid' => $USER->id], 0, 10);
        
        // Format activity for display
        $formatted_activities = [];
        foreach ($activities as $activity) {
            $formatted_activities[] = [
                'action' => $activity->action,
                'resource_type' => $activity->resource_type,
                'resource_id' => $activity->resource_id,
                'cluster_name' => $activity->cluster_name,
                'timestamp' => $activity->timestamp,
                'details' => json_decode($activity->details, true)
            ];
        }
        
        return [
            'activities' => $formatted_activities,
            'has_activities' => !empty($formatted_activities)
        ];
    }
    
    /**
     * Log activity for audit trail
     * 
     * @param string $action Action performed
     * @param string $resource_type Type of resource
     * @param int $resource_id ID of resource
     * @param array $details Additional details
     */
    public static function log_activity($action, $resource_type, $resource_id, $details = []) {
        global $USER, $DB;
        
        $log = new \stdClass();
        $log->user_id = $USER->id;
        $log->resource_type = $resource_type;
        $log->resource_id = $resource_id;
        $log->action = $action;
        $log->details = json_encode($details);
        $log->timestamp = time();
        $log->ip_address = getremoteaddr();
        
        $DB->insert_record('aitools_activity_log', $log);
    }
    
    /**
     * Check if user can access specific cluster
     * 
     * @param int $cluster_id Cluster ID
     * @param string $access_level Required access level (view, edit, manage)
     * @return bool
     */
    public static function can_access_cluster($cluster_id, $access_level = 'view') {
        global $USER, $DB;
        
        // Check if user is owner
        $cluster = $DB->get_record('aitools_clusters', ['id' => $cluster_id]);
        if (!$cluster) {
            return false;
        }
        
        if ($cluster->created_by == $USER->id) {
            return true; // Owner has full access
        }
        
        // Check shared access
        $access_record = $DB->get_record('aitools_shared_access', [
            'resource_type' => 'cluster',
            'resource_id' => $cluster_id,
            'user_id' => $USER->id
        ]);
        
        if (!$access_record) {
            return false;
        }
        
        // Check if access has expired
        if ($access_record->expires_date && $access_record->expires_date < time()) {
            return false;
        }
        
        // Check access level
        $levels = ['view' => 1, 'edit' => 2, 'manage' => 3];
        $required_level = $levels[$access_level] ?? 1;
        $user_level = $levels[$access_record->access_level] ?? 1;
        
        return $user_level >= $required_level;
    }
    
    /**
     * Get integration data from ValueMapDoc
     * 
     * @param string $field Field to get unique values for (market, industry, role)
     * @return array Unique values
     */
    public static function get_valuemapdoc_values($field) {
        global $DB;
        
        $valid_fields = ['market', 'industry', 'role', 'businessgoal', 'strategy'];
        if (!in_array($field, $valid_fields)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT {$field} as value 
                FROM {valuemapdoc_entries} 
                WHERE {$field} IS NOT NULL AND {$field} != ''
                ORDER BY {$field}";
        
        $records = $DB->get_records_sql($sql);
        
        $values = [];
        foreach ($records as $record) {
            if (!empty(trim($record->value))) {
                $values[] = trim($record->value);
            }
        }
        
        return $values;
    }
}