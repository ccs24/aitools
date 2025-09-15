<?php
namespace aitoolsub_valuemapdoc;

use local_aitools\plugin_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * ValueMapDoc plugin for AI Tools
 */
class plugin implements plugin_interface {
    
    /**
     * Get dashboard blocks for ValueMapDoc
     */
    public function get_dashboard_blocks() {
        global $USER;
        
        if (!$this->has_access()) {
            return [];
        }
        
        // Use data provider for accurate statistics
        $stats = data_provider::get_user_statistics($USER->id);
        
        // Get recent content (first 5 items)
        $all_content = data_provider::get_accessible_content($USER->id);
        $recent_content = array_slice($all_content, 0, 5);
        
        // Format recent content for template
        $formatted_recent = [];
        foreach ($recent_content as $record) {
            $formatted_recent[] = [
                'id' => $record->id,
                'name' => $record->name,
                'timecreated_formatted' => userdate($record->timecreated, get_string('strftimedatefullshort')),
                'view_url' => new \moodle_url('/mod/valuemapdoc/rate_content.php', [
                    'id' => $record->cmid,
                    'docid' => $record->id
                ])
            ];
        }
        
        return [
            'valuemap_summary' => [
                'title' => get_string('valuemap_summary', 'aitoolsub_valuemapdoc'),
                'template' => 'aitoolsub_valuemapdoc/dashboard_summary',
                'weight' => 10,
                'size' => 'large',
                'data' => [
                    'content_count' => $stats['total_content'],
                    'entries_count' => $stats['total_entries'],
                    'recent_content' => $formatted_recent,
                    'has_recent_content' => !empty($formatted_recent)
                ]
            ],
            
            'quick_stats' => [
                'title' => get_string('quick_stats', 'aitoolsub_valuemapdoc'),
                'template' => 'aitoolsub_valuemapdoc/quick_stats',
                'weight' => 20,
                'size' => 'medium',
                'data' => [
                    'total_documents' => $stats['total_content'],
                    'total_valuemaps' => $stats['total_entries'],
                    'this_week' => [
                        'content' => $stats['content_this_week'],
                        'entries' => $stats['entries_this_week'],
                        'content_progress_width' => $stats['content_this_week'] > 0 ? 70 : 5,
                        'valuemaps_progress_width' => $stats['entries_this_week'] > 0 ? 60 : 5
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get tools provided by ValueMapDoc
     */
    public function get_tools() {
        if (!$this->has_access()) {
            return [];
        }
        
        return [
            'my_content' => [
                'title' => get_string('my_content', 'aitoolsub_valuemapdoc'),
                'description' => get_string('my_content_desc', 'aitoolsub_valuemapdoc'),
                'url' => '/local/aitools/plugins/valuemapdoc/my_content.php',
                'icon' => 'fa-file-text',
                'category' => 'sales'
            ],
            
            'my_valuemaps' => [
                'title' => get_string('my_valuemaps', 'aitoolsub_valuemapdoc'),
                'description' => get_string('my_valuemaps_desc', 'aitoolsub_valuemapdoc'),
                'url' => '/local/aitools/plugins/valuemapdoc/my_valuemaps.php',
                'icon' => 'fa-sitemap',
                'category' => 'sales'
            ],
            
            'content_analytics' => [
                'title' => get_string('content_analytics', 'aitoolsub_valuemapdoc'),
                'description' => get_string('content_analytics_desc', 'aitoolsub_valuemapdoc'),
                'url' => '/local/aitools/plugins/valuemapdoc/analytics.php',
                'icon' => 'fa-chart-bar',
                'category' => 'analytics'
            ]
        ];
    }
    
    /**
     * Get plugin information
     */
    public function get_plugin_info() {
        return [
            'name' => 'ValueMapDoc',
            'version' => '1.0.0',
            'description' => get_string('plugin_description', 'aitoolsub_valuemapdoc'),
            'author' => 'ValueMapDoc Team',
            'category' => 'sales'
        ];
    }
    
    /**
     * Check if user has access to ValueMapDoc tools
     */
    public function has_access() {
        global $USER;
        
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        
        // Check basic ValueMapDoc capabilities
        // Temporarily simplified - in production should check proper capabilities
        return true;
    }
}