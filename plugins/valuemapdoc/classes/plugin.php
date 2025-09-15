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
        
        // Basic statistics for testing
        try {
            // Count content tylko jeśli tabela istnieje
            $content_count = 0;
            if ($DB->get_manager()->table_exists('valuemapdoc_content')) {
                $content_count = $DB->count_records('valuemapdoc_content', ['userid' => $USER->id]);
            }
            
            // Count entries tylko jeśli tabela istnieje  
            $entries_count = 0;
            if ($DB->get_manager()->table_exists('valuemapdoc_entries')) {
                $entries_count = $DB->count_records('valuemapdoc_entries', ['userid' => $USER->id]);
            }
        } catch (\Exception $e) {
            error_log('ValueMapDoc subplugin error: ' . $e->getMessage());
            $content_count = 0;
            $entries_count = 0;
        }
        
        return [
            'valuemap_summary' => [
                'title' => 'ValueMapDoc Summary',
                'template' => 'aitoolsub_valuemapdoc/dashboard_summary',
                'weight' => 10,
                'size' => 'large',
                'data' => [
                    'content_count' => $content_count,
                    'entries_count' => $entries_count,
                    'user_name' => fullname($USER)
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
                'title' => 'My Content',
                'description' => 'View and manage all your generated documents across courses',
                'url' => '/local/aitools/plugins/valuemapdoc/my_content.php',
                'icon' => 'fa-file-text',
                'category' => 'sales'
            ],
            
            'my_valuemaps' => [
                'title' => 'My Value Maps',
                'description' => 'Access your value map entries and templates',
                'url' => '/mod/valuemapdoc/view.php',  // Link do głównego modułu
                'icon' => 'fa-sitemap',
                'category' => 'sales'
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
            'description' => 'AI-powered value mapping and document generation tools',
            'author' => 'ValueMapDoc Team',
            'category' => 'sales'
        ];
    }
    
    /**
     * Check if user has access to ValueMapDoc tools
     */
    public function has_access() {
        global $USER;
        
        // Podstawowe sprawdzenie - zalogowany i nie gość
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        
        // Na poziomie ogólnym - pozwalamy dostęp
        // Szczegółowe uprawnienia weryfikujemy przy konkretnych treściach
        return true;
    }
}