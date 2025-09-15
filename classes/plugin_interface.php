<?php
namespace local_aitools;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for AI Tools subplugins
 */
interface plugin_interface {
    
    /**
     * Get dashboard blocks for this plugin
     * 
     * @return array Array of dashboard blocks configuration
     */
    public function get_dashboard_blocks();
    
    /**
     * Get tools provided by this plugin
     * 
     * @return array Array of tools configuration
     */
    public function get_tools();
    
    /**
     * Get plugin information
     * 
     * @return array Plugin metadata
     */
    public function get_plugin_info();
    
    /**
     * Check if user has access to this plugin
     * 
     * @return bool
     */
    public function has_access();
}