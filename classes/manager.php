<?php
namespace local_aitools;

defined('MOODLE_INTERNAL') || die();

/**
 * AI Tools manager - Moodle 5.0 compatible
 */
class manager {
    
    /** @var array Cache for loaded plugins */
    private static $plugins_cache = null;
    
    /**
     * Get all available AI Tools subplugins
     * 
     * @return array Array of plugin instances
     */
    public static function get_plugins() {
        if (self::$plugins_cache !== null) {
            return self::$plugins_cache;
        }
        
        $plugins = [];
        
        try {
            // Moodle 5.0 - użyj core_plugin_manager (bez namespace prefix)
            if (class_exists('\core_plugin_manager')) {
                $plugin_manager = \core_plugin_manager::instance();
                
                // Subpluginy w Moodle 5.0 są pobierane przez get_subplugins_of_plugin
                try {
                    $subplugins = $plugin_manager->get_subplugins_of_plugin('local_aitools');
                    error_log('Moodle 5.0: Found ' . count($subplugins) . ' subplugins for local_aitools');
                } catch (\Exception $e) {
                    error_log('Moodle 5.0: Error getting subplugins: ' . $e->getMessage());
                    // Fallback to manual scanning
                    $subplugins = self::scan_subplugins_manually();
                }
            } else {
                // Fallback - ręczne skanowanie
                error_log('Moodle 5.0: core_plugin_manager not found, using manual scanning');
                $subplugins = self::scan_subplugins_manually();
            }
            
            foreach ($subplugins as $subplugin) {
                $plugin_name = is_object($subplugin) ? $subplugin->name : $subplugin;
                
                // Ensure plugin name has aitoolsub_ prefix
                if (!str_starts_with($plugin_name, 'aitoolsub_')) {
                    $plugin_name = 'aitoolsub_' . $plugin_name;
                }
                
                $classname = "\\{$plugin_name}\\plugin";
                
                error_log('Moodle 5.0: Trying to load class: ' . $classname);
                
                if (class_exists($classname)) {
                    try {
                        $plugin_instance = new $classname();
                        if ($plugin_instance instanceof plugin_interface && 
                            $plugin_instance->has_access() && 
                            self::has_cohort_access($plugin_name)) {
                            $plugins[$plugin_name] = $plugin_instance;
                            error_log('Moodle 5.0: Successfully loaded plugin: ' . $plugin_name);
                        } else {
                            if (!$plugin_instance->has_access()) {
                                error_log('Moodle 5.0: Plugin ' . $plugin_name . ' denied - no access');
                            } elseif (!self::has_cohort_access($plugin_name)) {
                                error_log('Moodle 5.0: Plugin ' . $plugin_name . ' denied - cohort restriction');
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('Moodle 5.0: Error instantiating plugin ' . $plugin_name . ': ' . $e->getMessage());
                    }
                } else {
                    error_log('Moodle 5.0: Class not found: ' . $classname);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break the system
            error_log('Moodle 5.0: local_aitools manager error: ' . $e->getMessage());
        }
        
        self::$plugins_cache = $plugins;
        return $plugins;
    }
    
    /**
     * Manual scanning for subplugins - Moodle 5.0 compatible
     */
    private static function scan_subplugins_manually() {
        global $CFG;
        
        $subplugins = [];
        $plugins_dir = $CFG->dirroot . '/local/aitools/plugins';
        
        if (!is_dir($plugins_dir)) {
            error_log('Moodle 5.0: Plugins directory not found: ' . $plugins_dir);
            return $subplugins;
        }
        
        $dirs = scandir($plugins_dir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $plugin_path = $plugins_dir . '/' . $dir;
            if (is_dir($plugin_path) && file_exists($plugin_path . '/version.php')) {
                $plugin_name = 'aitoolsub_' . $dir;
                $subplugins[] = $plugin_name;
                error_log('Moodle 5.0: Found manual subplugin: ' . $plugin_name . ' at ' . $plugin_path);
            }
        }
        
        return $subplugins;
    }


    public static function has_cohort_access($plugin_name) {
        return \local_aitools\cohort_manager::has_cohort_access($plugin_name);
    }
    
    /**
     * Get all dashboard blocks from all plugins
     * 
     * @return array Sorted array of dashboard blocks
     */
    public static function get_dashboard_blocks() {
        $blocks = [];
        $plugins = self::get_plugins();
        
        foreach ($plugins as $plugin_name => $plugin) {
            try {
                $plugin_blocks = $plugin->get_dashboard_blocks();
                foreach ($plugin_blocks as $block_key => $block_config) {
                    $block_config['plugin'] = $plugin_name;
                    $block_config['block_key'] = $block_key;
                    $blocks[] = $block_config;
                }
            } catch (\Exception $e) {
                error_log('Moodle 5.0: Error getting dashboard blocks from ' . $plugin_name . ': ' . $e->getMessage());
            }
        }
        
        // Sort by weight
        usort($blocks, function($a, $b) {
            return ($a['weight'] ?? 50) <=> ($b['weight'] ?? 50);
        });
        
        return $blocks;
    }
    
    /**
     * Get all tools from all plugins
     * 
     * @return array Tools grouped by category
     */
    public static function get_tools() {
        $tools = [];
        $plugins = self::get_plugins();
        
        foreach ($plugins as $plugin_name => $plugin) {
            try {
                $plugin_tools = $plugin->get_tools();
                foreach ($plugin_tools as $tool_key => $tool_config) {
                    $tool_config['plugin'] = $plugin_name;
                    $tool_config['tool_key'] = $tool_key;
                    $category = $tool_config['category'] ?? 'general';
                    $tools[$category][] = $tool_config;
                }
            } catch (\Exception $e) {
                error_log('Moodle 5.0: Error getting tools from ' . $plugin_name . ': ' . $e->getMessage());
            }
        }
        
        return $tools;
    }
    
    /**
     * Get statistics for dashboard
     * 
     * @return array Statistics data
     */
    public static function get_statistics() {
        $stats = [
            'total_plugins' => 0,
            'total_tools' => 0,
            'total_blocks' => 0,
            'user_activity' => []
        ];
        
        try {
            $plugins = self::get_plugins();
            $stats['total_plugins'] = count($plugins);
            
            foreach ($plugins as $plugin) {
                $tools = $plugin->get_tools();
                $blocks = $plugin->get_dashboard_blocks();
                $stats['total_tools'] += count($tools);
                $stats['total_blocks'] += count($blocks);
            }
        } catch (\Exception $e) {
            error_log('Moodle 5.0: Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Clear plugins cache
     */
    public static function clear_cache() {
        self::$plugins_cache = null;
    }
}