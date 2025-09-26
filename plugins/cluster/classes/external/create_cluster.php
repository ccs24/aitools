<?php
namespace aitoolsub_cluster\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Create cluster external service
 */
class create_cluster extends \external_api {
    
    /**
     * Define parameters for create_cluster
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'name' => new \external_value(PARAM_TEXT, 'Cluster name'),
            'market' => new \external_value(PARAM_TEXT, 'Market from valuemapdoc', VALUE_OPTIONAL, ''),
            'description' => new \external_value(PARAM_TEXT, 'Cluster description', VALUE_OPTIONAL, ''),
            'status' => new \external_value(PARAM_ALPHA, 'Cluster status', VALUE_OPTIONAL, 'planning'),
        ]);
    }
    
    /**
     * Create a new cluster
     */
    public static function execute($name, $market = '', $description = '', $status = 'planning') {
        global $DB, $USER;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'market' => $market,
            'description' => $description,
            'status' => $status
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
        
        // Validate status
        $valid_statuses = ['planning', 'active', 'paused', 'completed'];
        if (!in_array($params['status'], $valid_statuses)) {
            throw new \invalid_parameter_exception('Invalid status');
        }
        
        // Validate market against ValueMapDoc if provided
        if (!empty($params['market'])) {
            $valid_markets = \aitoolsub_cluster\plugin::get_valuemapdoc_values('market');
            if (!in_array($params['market'], $valid_markets) && !empty($valid_markets)) {
                // Market not in ValueMapDoc - log for review but allow custom values
                error_log('Cluster created with custom market: ' . $params['market']);
            }
        }
        
        // Create cluster record
        $cluster = new \stdClass();
        $cluster->name = trim($params['name']);
        $cluster->market = trim($params['market']);
        $cluster->description = trim($params['description']);
        $cluster->status = $params['status'];
        $cluster->created_by = $USER->id;
        $cluster->created_date = time();
        $cluster->modified_date = time();
        
        // Insert into database
        $cluster->id = $DB->insert_record('aitools_clusters', $cluster);
        
        if (!$cluster->id) {
            throw new \moodle_exception('errorcreatecluster', 'aitoolsub_cluster');
        }
        
        // Log activity
        \aitoolsub_cluster\plugin::log_activity(
            'cluster_created', 
            'cluster',
            $cluster->id, 
            [
                'name' => $cluster->name,
                'market' => $cluster->market,
                'status' => $cluster->status
            ]
        );
        
        return [
            'success' => true,
            'cluster_id' => $cluster->id,
            'message' => get_string('clustercreated', 'aitoolsub_cluster'),
            'cluster' => [
                'id' => $cluster->id,
                'name' => $cluster->name,
                'market' => $cluster->market,
                'description' => $cluster->description,
                'status' => $cluster->status,
                'created_date' => $cluster->created_date
            ]
        ];
    }
    
    /**
     * Define return values for create_cluster
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'cluster_id' => new \external_value(PARAM_INT, 'Created cluster ID'),
            'message' => new \external_value(PARAM_TEXT, 'Success message'),
            'cluster' => new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'Cluster ID'),
                'name' => new \external_value(PARAM_TEXT, 'Cluster name'),
                'market' => new \external_value(PARAM_TEXT, 'Market'),
                'description' => new \external_value(PARAM_TEXT, 'Description'),
                'status' => new \external_value(PARAM_ALPHA, 'Status'),
                'created_date' => new \external_value(PARAM_INT, 'Creation timestamp')
            ])
        ]);
    }
}