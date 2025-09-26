<?php
namespace aitoolsub_cluster\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Get ValueMapDoc data for dropdowns and AI context
 */
class get_valuemap_data extends \external_api {
    
    /**
     * Define parameters for get_valuemap_data
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'fields' => new \external_multiple_structure(
                new \external_value(PARAM_ALPHA, 'Field name'),
                'Fields to retrieve (market, industry, role, businessgoal, strategy)',
                VALUE_OPTIONAL,
                ['market', 'industry', 'role']
            ),
            'search' => new \external_value(PARAM_TEXT, 'Search term for filtering', VALUE_OPTIONAL, ''),
            'limit' => new \external_value(PARAM_INT, 'Limit results per field', VALUE_OPTIONAL, 100)
        ]);
    }
    
    /**
     * Get ValueMapDoc data for cluster integration
     */
    public static function execute($fields = ['market', 'industry', 'role'], $search = '', $limit = 100) {
        global $DB;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'fields' => $fields,
            'search' => $search,
            'limit' => $limit
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
        
        // Validate fields
        $valid_fields = ['market', 'industry', 'role', 'businessgoal', 'strategy'];
        $requested_fields = array_intersect($params['fields'], $valid_fields);
        
        if (empty($requested_fields)) {
            $requested_fields = ['market', 'industry', 'role'];
        }
        
        $search = trim($params['search']);
        $limit = max(1, min(200, $params['limit']));
        
        $result = [];
        
        foreach ($requested_fields as $field) {
            $sql = "SELECT DISTINCT {$field} as value, COUNT(*) as usage_count
                    FROM {valuemapdoc_entries} 
                    WHERE {$field} IS NOT NULL 
                      AND {$field} != ''";
            
            $sql_params = [];
            
            if (!empty($search)) {
                $sql .= " AND {$field} LIKE :search";
                $sql_params['search'] = '%' . $search . '%';
            }
            
            $sql .= " GROUP BY {$field} ORDER BY usage_count DESC, {$field} ASC";
            
            $records = $DB->get_records_sql($sql, $sql_params, 0, $limit);
            
            $values = [];
            foreach ($records as $record) {
                $value = trim($record->value);
                if (!empty($value)) {
                    $values[] = [
                        'value' => $value,
                        'usage_count' => (int)$record->usage_count,
                        'display' => $value . ' (' . $record->usage_count . ')'
                    ];
                }
            }
            
            $result[$field] = $values;
        }
        
        // Get context data for AI message generation
        $context_data = [];
        if (in_array('businessgoal', $requested_fields) || in_array('strategy', $requested_fields)) {
            // Get sample context for AI prompts
            $context_sql = "SELECT market, industry, role, businessgoal, strategy
                           FROM {valuemapdoc_entries}
                           WHERE market IS NOT NULL 
                             AND industry IS NOT NULL 
                             AND role IS NOT NULL
                             AND businessgoal IS NOT NULL
                           ORDER BY id DESC";
            
            $context_records = $DB->get_records_sql($context_sql, [], 0, 20);
            
            foreach ($context_records as $record) {
                $context_data[] = [
                    'market' => $record->market,
                    'industry' => $record->industry,
                    'role' => $record->role,
                    'businessgoal' => $record->businessgoal,
                    'strategy' => $record->strategy ?? ''
                ];
            }
        }
        
        // Get statistics
        $stats = [];
        foreach ($requested_fields as $field) {
            $count_sql = "SELECT COUNT(DISTINCT {$field}) as unique_count,
                                 COUNT(*) as total_count
                          FROM {valuemapdoc_entries}
                          WHERE {$field} IS NOT NULL AND {$field} != ''";
            
            $count_params = [];
            if (!empty($search)) {
                $count_sql .= " AND {$field} LIKE :search";
                $count_params['search'] = '%' . $search . '%';
            }
            
            $stat = $DB->get_record_sql($count_sql, $count_params);
            $stats[$field] = [
                'unique_values' => (int)$stat->unique_count,
                'total_entries' => (int)$stat->total_count
            ];
        }
        
        return [
            'fields' => $result,
            'context_data' => $context_data,
            'statistics' => $stats,
            'search_applied' => !empty($search),
            'search_term' => $search
        ];
    }
    
    /**
     * Define return values for get_valuemap_data
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'fields' => new \external_single_structure([
                'market' => new \external_multiple_structure(
                    new \external_single_structure([
                        'value' => new \external_value(PARAM_TEXT, 'Market value'),
                        'usage_count' => new \external_value(PARAM_INT, 'Number of times used'),
                        'display' => new \external_value(PARAM_TEXT, 'Display text with count')
                    ]),
                    'Market values',
                    VALUE_OPTIONAL
                ),
                'industry' => new \external_multiple_structure(
                    new \external_single_structure([
                        'value' => new \external_value(PARAM_TEXT, 'Industry value'),
                        'usage_count' => new \external_value(PARAM_INT, 'Number of times used'),
                        'display' => new \external_value(PARAM_TEXT, 'Display text with count')
                    ]),
                    'Industry values',
                    VALUE_OPTIONAL
                ),
                'role' => new \external_multiple_structure(
                    new \external_single_structure([
                        'value' => new \external_value(PARAM_TEXT, 'Role value'),
                        'usage_count' => new \external_value(PARAM_INT, 'Number of times used'),
                        'display' => new \external_value(PARAM_TEXT, 'Display text with count')
                    ]),
                    'Role values',
                    VALUE_OPTIONAL
                ),
                'businessgoal' => new \external_multiple_structure(
                    new \external_single_structure([
                        'value' => new \external_value(PARAM_TEXT, 'Business goal value'),
                        'usage_count' => new \external_value(PARAM_INT, 'Number of times used'),
                        'display' => new \external_value(PARAM_TEXT, 'Display text with count')
                    ]),
                    'Business goal values',
                    VALUE_OPTIONAL
                ),
                'strategy' => new \external_multiple_structure(
                    new \external_single_structure([
                        'value' => new \external_value(PARAM_TEXT, 'Strategy value'),
                        'usage_count' => new \external_value(PARAM_INT, 'Number of times used'),
                        'display' => new \external_value(PARAM_TEXT, 'Display text with count')
                    ]),
                    'Strategy values',
                    VALUE_OPTIONAL
                )
            ]),
            'context_data' => new \external_multiple_structure(
                new \external_single_structure([
                    'market' => new \external_value(PARAM_TEXT, 'Market'),
                    'industry' => new \external_value(PARAM_TEXT, 'Industry'),
                    'role' => new \external_value(PARAM_TEXT, 'Role'),
                    'businessgoal' => new \external_value(PARAM_TEXT, 'Business goal'),
                    'strategy' => new \external_value(PARAM_TEXT, 'Strategy')
                ]),
                'Context data for AI generation'
            ),
            'statistics' => new \external_single_structure([
                'market' => new \external_single_structure([
                    'unique_values' => new \external_value(PARAM_INT, 'Number of unique values'),
                    'total_entries' => new \external_value(PARAM_INT, 'Total entries with this field')
                ], VALUE_OPTIONAL),
                'industry' => new \external_single_structure([
                    'unique_values' => new \external_value(PARAM_INT, 'Number of unique values'),
                    'total_entries' => new \external_value(PARAM_INT, 'Total entries with this field')
                ], VALUE_OPTIONAL),
                'role' => new \external_single_structure([
                    'unique_values' => new \external_value(PARAM_INT, 'Number of unique values'),
                    'total_entries' => new \external_value(PARAM_INT, 'Total entries with this field')
                ], VALUE_OPTIONAL),
                'businessgoal' => new \external_single_structure([
                    'unique_values' => new \external_value(PARAM_INT, 'Number of unique values'),
                    'total_entries' => new \external_value(PARAM_INT, 'Total entries with this field')
                ], VALUE_OPTIONAL),
                'strategy' => new \external_single_structure([
                    'unique_values' => new \external_value(PARAM_INT, 'Number of unique values'),
                    'total_entries' => new \external_value(PARAM_INT, 'Total entries with this field')
                ], VALUE_OPTIONAL)
            ]),
            'search_applied' => new \external_value(PARAM_BOOL, 'Whether search filter was applied'),
            'search_term' => new \external_value(PARAM_TEXT, 'Search term used')
        ]);
    }
}