<?php
namespace aitoolsub_cluster\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Get clusters external service
 */
class get_clusters extends \external_api {
    
    /**
     * Define parameters for get_clusters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'filters' => new \external_single_structure([
                'status' => new \external_value(PARAM_ALPHA, 'Filter by status', VALUE_OPTIONAL, ''),
                'market' => new \external_value(PARAM_TEXT, 'Filter by market', VALUE_OPTIONAL, ''),
                'search' => new \external_value(PARAM_TEXT, 'Search in name/description', VALUE_OPTIONAL, ''),
                'limit' => new \external_value(PARAM_INT, 'Limit results', VALUE_OPTIONAL, 50),
                'offset' => new \external_value(PARAM_INT, 'Offset for pagination', VALUE_OPTIONAL, 0)
            ], VALUE_OPTIONAL, [])
        ]);
    }
    
    /**
     * Get user's clusters with filtering and details
     */
    public static function execute($filters = []) {
        global $DB, $USER;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'filters' => $filters
        ]);
        
        // Validate context and capabilities
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/aitools:view', $context);
        
        // Check plugin access
        $plugin = new \aitoolsub_cluster\plugin();
        if (!$plugin->has_access()) {
            throw new \moodle_exception('noaccess', 'aitoolsub_cluster');
        }
        
        $filters = $params['filters'];
        
        // Build SQL query
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT cc.id) as company_count,
                       COUNT(DISTINCT cp.id) as person_count,
                       COUNT(DISTINCT cm.id) as message_count,
                       u.firstname, u.lastname
                FROM {aitools_clusters} c
                LEFT JOIN {aitools_cluster_companies} cc ON c.id = cc.cluster_id
                LEFT JOIN {aitools_cluster_persons} cp ON cc.id = cp.company_id
                LEFT JOIN {aitools_cluster_messages} cm ON c.id = cm.cluster_id
                LEFT JOIN {user} u ON c.created_by = u.id
                WHERE (c.created_by = :userid OR c.id IN (
                    SELECT sa.resource_id 
                    FROM {aitools_shared_access} sa 
                    WHERE sa.resource_type = 'cluster' 
                      AND sa.user_id = :userid2
                      AND (sa.expires_date IS NULL OR sa.expires_date > :now)
                ))";
        
        $params_sql = [
            'userid' => $USER->id,
            'userid2' => $USER->id,
            'now' => time()
        ];
        
        // Add filters
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params_sql['status'] = $filters['status'];
        }
        
        if (!empty($filters['market'])) {
            $sql .= " AND c.market LIKE :market";
            $params_sql['market'] = '%' . $filters['market'] . '%';
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE :search OR c.description LIKE :search2)";
            $params_sql['search'] = '%' . $filters['search'] . '%';
            $params_sql['search2'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " GROUP BY c.id, u.firstname, u.lastname ORDER BY c.modified_date DESC";
        
        // Apply pagination
        $limit = isset($filters['limit']) ? max(1, min(100, $filters['limit'])) : 50;
        $offset = isset($filters['offset']) ? max(0, $filters['offset']) : 0;
        
        $clusters = $DB->get_records_sql($sql, $params_sql, $offset, $limit);
        
        // Get total count for pagination
        $count_sql = "SELECT COUNT(DISTINCT c.id)
                      FROM {aitools_clusters} c
                      WHERE (c.created_by = :userid OR c.id IN (
                          SELECT sa.resource_id 
                          FROM {aitools_shared_access} sa 
                          WHERE sa.resource_type = 'cluster' 
                            AND sa.user_id = :userid2
                            AND (sa.expires_date IS NULL OR sa.expires_date > :now)
                      ))";
        
        $count_params = [
            'userid' => $USER->id,
            'userid2' => $USER->id,
            'now' => time()
        ];
        
        // Add same filters to count query
        if (!empty($filters['status'])) {
            $count_sql .= " AND c.status = :status";
            $count_params['status'] = $filters['status'];
        }
        
        if (!empty($filters['market'])) {
            $count_sql .= " AND c.market LIKE :market";
            $count_params['market'] = '%' . $filters['market'] . '%';
        }
        
        if (!empty($filters['search'])) {
            $count_sql .= " AND (c.name LIKE :search OR c.description LIKE :search2)";
            $count_params['search'] = '%' . $filters['search'] . '%';
            $count_params['search2'] = '%' . $filters['search'] . '%';
        }
        
        $total_count = $DB->count_records_sql($count_sql, $count_params);
        
        // Format clusters for return
        $formatted_clusters = [];
        foreach ($clusters as $cluster) {
            // Check user's access level to this cluster
            $access_level = 'view';
            if ($cluster->created_by == $USER->id) {
                $access_level = 'manage';
            } else {
                $access_record = $DB->get_record('aitools_shared_access', [
                    'resource_type' => 'cluster',
                    'resource_id' => $cluster->id,
                    'user_id' => $USER->id
                ]);
                if ($access_record) {
                    $access_level = $access_record->access_level;
                }
            }
            
            $formatted_clusters[] = [
                'id' => $cluster->id,
                'name' => $cluster->name,
                'market' => $cluster->market ?? '',
                'description' => $cluster->description ?? '',
                'status' => $cluster->status,
                'created_by' => $cluster->created_by,
                'creator_name' => $cluster->firstname . ' ' . $cluster->lastname,
                'created_date' => $cluster->created_date,
                'modified_date' => $cluster->modified_date,
                'company_count' => (int)$cluster->company_count,
                'person_count' => (int)$cluster->person_count,
                'message_count' => (int)$cluster->message_count,
                'access_level' => $access_level,
                'is_owner' => ($cluster->created_by == $USER->id)
            ];
        }
        
        return [
            'clusters' => $formatted_clusters,
            'total_count' => $total_count,
            'has_more' => ($offset + $limit) < $total_count,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total_count,
                'pages' => ceil($total_count / $limit),
                'current_page' => floor($offset / $limit) + 1
            ]
        ];
    }
    
    /**
     * Define return values for get_clusters
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'clusters' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'Cluster ID'),
                    'name' => new \external_value(PARAM_TEXT, 'Cluster name'),
                    'market' => new \external_value(PARAM_TEXT, 'Market'),
                    'description' => new \external_value(PARAM_TEXT, 'Description'),
                    'status' => new \external_value(PARAM_ALPHA, 'Status'),
                    'created_by' => new \external_value(PARAM_INT, 'Creator user ID'),
                    'creator_name' => new \external_value(PARAM_TEXT, 'Creator name'),
                    'created_date' => new \external_value(PARAM_INT, 'Creation timestamp'),
                    'modified_date' => new \external_value(PARAM_INT, 'Modification timestamp'),
                    'company_count' => new \external_value(PARAM_INT, 'Number of companies'),
                    'person_count' => new \external_value(PARAM_INT, 'Number of persons'),
                    'message_count' => new \external_value(PARAM_INT, 'Number of messages'),
                    'access_level' => new \external_value(PARAM_ALPHA, 'User access level'),
                    'is_owner' => new \external_value(PARAM_BOOL, 'Is user owner')
                ])
            ),
            'total_count' => new \external_value(PARAM_INT, 'Total number of clusters'),
            'has_more' => new \external_value(PARAM_BOOL, 'More clusters available'),
            'pagination' => new \external_single_structure([
                'limit' => new \external_value(PARAM_INT, 'Items per page'),
                'offset' => new \external_value(PARAM_INT, 'Current offset'),
                'total' => new \external_value(PARAM_INT, 'Total items'),
                'pages' => new \external_value(PARAM_INT, 'Total pages'),
                'current_page' => new \external_value(PARAM_INT, 'Current page number')
            ])
        ]);
    }
}