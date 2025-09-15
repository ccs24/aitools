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
        global $USER, $DB;
        
        if (!$this->has_access()) {
            return [];
        }
        
        // Get user's content statistics
        $content_count = $DB->count_records('valuemapdoc_content', ['userid' => $USER->id]);
        $entries_count = $DB->count_records('valuemapdoc_entries', ['userid' => $USER->id]);
        
        // Get recent activity
        $recent_content = $DB->get_records('valuemapdoc_content', 
            ['userid' => $USER->id], 
            'timecreated DESC', 
            'id, name, timecreated', 
            0, 5
        );
        
        return [
            'valuemap_summary' => [
                'title' => get_string('valuemap_summary', 'aitoolsub_valuemapdoc'),
                'template' => 'aitoolsub_valuemapdoc/dashboard_summary',
                'weight' => 10,
                'size' => 'large',
                'data' => [
                    'content_count' => $content_count,
                    'entries_count' => $entries_count,
                    'recent_content' => array_values($recent_content)
                ]
            ],
            
            'quick_stats' => [
                'title' => get_string('quick_stats', 'aitoolsub_valuemapdoc'),
                'template' => 'aitoolsub_valuemapdoc/quick_stats',
                'weight' => 20,
                'size' => 'medium',
                'data' => [
                    'total_documents' => $content_count,
                    'total_valuemaps' => $entries_count,
                    'this_week' => $this->get_weekly_stats()
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
//        $contexts = get_contexts_with_capability_for_user($USER->id, 'mod/valuemapdoc:view');
        $contexts = "sd";
        if (empty($contexts)) {
            return false;
        }
        
        // Check cohort access (this is handled by manager, but we can add extra logic here)
        return true;
    }
    
    /**
     * Get weekly statistics for dashboard
     */
    private function get_weekly_stats() {
        global $USER, $DB;
        
        $week_ago = time() - (7 * 24 * 60 * 60);
        
        $content_this_week = $DB->count_records_select('valuemapdoc_content', 
            'userid = ? AND timecreated >= ?', 
            [$USER->id, $week_ago]
        );
        
        $entries_this_week = $DB->count_records_select('valuemapdoc_entries', 
            'userid = ? AND timemodified >= ?', 
            [$USER->id, $week_ago]
        );
        
        return [
            'content' => $content_this_week,
            'entries' => $entries_this_week
        ];
    }
}