<?php
defined('MOODLE_INTERNAL') || die();

use local_aitools\manager;

if ($hassiteconfig) {
    
    // Main settings page
    $settings = new admin_settingpage('local_aitools', 
        get_string('pluginname', 'local_aitools'));
    
    // Add general settings
    $settings->add(new admin_setting_heading('local_aitools/general',
        'General Settings',
        'Configure general AI Tools settings'
    ));
    
    // Enable/disable AI Tools
    $settings->add(new admin_setting_configcheckbox('local_aitools/enabled',
        'Enable AI Tools',
        'Enable or disable the AI Tools dashboard',
        1
    ));
    
    // Debug mode for AI Tools
    $settings->add(new admin_setting_configcheckbox('local_aitools/debug',
        'Debug Mode',
        'Enable debug mode for troubleshooting',
        0
    ));
    
    $ADMIN->add('localplugins', $settings);
    
    // Subplugin management category
    $subplugins_page = new admin_category('local_aitools_subplugins',
        'AI Tools Subplugins');
    
    $ADMIN->add('localplugins', $subplugins_page);
    
    // Get installed subplugins and create settings pages
    try {
        $plugins = manager::get_plugins();
        
        if (empty($plugins)) {
            // No subplugins yet - show info page
            $no_subplugins_page = new admin_settingpage(
                'local_aitools_no_subplugins',
                'No Subplugins Available'
            );
            
            $no_subplugins_page->add(new admin_setting_description(
                'local_aitools/no_subplugins',
                'Subplugins Status',
                '
                <div class="alert alert-info">
                    <h4>No Subplugins Available</h4>
                    <p>Either no subplugins are installed, or you don\'t have access to them due to cohort restrictions.</p>
                    <p>Contact your administrator if you think you should have access.</p>
                </div>
                '
            ));
            
            $ADMIN->add('local_aitools_subplugins', $no_subplugins_page);
        } else {
            // Create settings page for each accessible subplugin
            foreach ($plugins as $plugin_name => $plugin_instance) {
                $plugin_info = $plugin_instance->get_plugin_info();
                $clean_name = str_replace('aitoolsub_', '', $plugin_name);
                
                $plugin_settings = new admin_settingpage(
                    'local_aitools_' . $clean_name,
                    $plugin_info['name'] ?? $clean_name
                );
                
                // Plugin info
                $plugin_settings->add(new admin_setting_description(
                    'local_aitools/' . $plugin_name . '_info',
                    'Plugin Information',
                    'Plugin: ' . $plugin_name . '<br>' .
                    'Version: ' . ($plugin_info['version'] ?? 'Unknown') . '<br>' .
                    'Status: Active'
                ));
                
                // Cohort management - only for managers
                if (has_capability('local/aitools:manage', context_system::instance())) {
                    $plugin_settings->add(new admin_setting_heading(
                        'local_aitools/' . $plugin_name . '_cohorts',
                        'Cohort Access Control',
                        'Control which cohorts can access this subplugin'
                    ));
                    
                    // Link to cohort management page
                    $cohort_manage_url = new moodle_url('/local/aitools/admin/cohorts.php', 
                        ['subplugin' => $plugin_name]);
                    
                    $plugin_settings->add(new admin_setting_description(
                        'local_aitools/' . $plugin_name . '_cohort_link',
                        'Manage Cohorts',
                        html_writer::link($cohort_manage_url, 
                            'Configure Cohort Access →',
                            ['class' => 'btn btn-primary']
                        )
                    ));
                    
                    // Show current cohort statistics
                    if (class_exists('local_aitools\cohort_manager')) {
                        $stats = \local_aitools\cohort_manager::get_access_statistics($plugin_name);
                        
                        if ($stats['unrestricted']) {
                            $stats_text = '<span class="text-warning">⚠️ Unrestricted Access</span><br>All users have access to this subplugin.';
                        } else {
                            $stats_text = '<span class="text-info">🔒 Restricted Access</span><br>' . 
                                         $stats['total_cohorts'] . ' cohorts configured<br>' .
                                         $stats['total_users'] . ' users have access';
                        }
                        
                        $plugin_settings->add(new admin_setting_description(
                            'local_aitools/' . $plugin_name . '_stats',
                            'Current Access Status',
                            $stats_text
                        ));
                    }
                }
                
                $ADMIN->add('local_aitools_subplugins', $plugin_settings);
            }
        }
        
    } catch (\Exception $e) {
        // Error handling - show debug info for managers
        if (has_capability('local/aitools:manage', context_system::instance())) {
            $error_page = new admin_settingpage(
                'local_aitools_error',
                'Configuration Error'
            );
            
            $error_page->add(new admin_setting_description(
                'local_aitools/error',
                'Error Loading Subplugins',
                '<div class="alert alert-danger">' .
                'Error: ' . htmlspecialchars($e->getMessage()) . '<br>' .
                'Check error logs for more details.' .
                '</div>'
            ));
            
            $ADMIN->add('local_aitools_subplugins', $error_page);
        }
    }
}