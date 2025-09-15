<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Tools Dashboard';
$string['aitools'] = 'AI Tools';
$string['my_aitools'] = 'My AI Tools';
$string['aitools_dashboard'] = 'AI Tools Dashboard';

// Subplugin type strings - REQUIRED dla Moodle 5.0
$string['subplugintype_aitoolsub'] = 'AI Tools subplugin';
$string['subplugintype_aitoolsub_plural'] = 'AI Tools subplugins';

// Dashboard
$string['welcome_user'] = 'Welcome to AI Tools';
$string['dashboard_subtitle'] = 'Your centralized hub for AI-powered tools and analytics';
$string['dashboard_overview'] = 'Overview';
$string['manage'] = 'Manage';

// Statistics
$string['active_plugins'] = 'Active Plugins';
$string['available_tools'] = 'Available Tools';
$string['dashboard_blocks'] = 'Dashboard Blocks';
$string['availability'] = 'Availability';

// Tools
$string['tools'] = 'tools';
$string['open_tool'] = 'Open Tool';
$string['no_tools_available'] = 'No Tools Available';
$string['no_tools_description'] = 'Install AI Tools subplugins to see tools here.';

// Categories
$string['category_general'] = 'General';
$string['category_sales'] = 'Sales';
$string['category_content'] = 'Content';
$string['category_analytics'] = 'Analytics';
$string['category_communication'] = 'Communication';

// Settings
$string['general_settings'] = 'General Settings';
$string['general_settings_desc'] = 'Configure general AI Tools settings';
$string['enabled'] = 'Enable AI Tools';
$string['enabled_desc'] = 'Enable or disable the AI Tools dashboard';
$string['debug_mode'] = 'Debug Mode';
$string['debug_mode_desc'] = 'Enable debug mode for troubleshooting';
$string['subplugins_management'] = 'Subplugins Management';

// Cohort Management
$string['cohort_access'] = 'Cohort Access Control';
$string['cohort_access_desc'] = 'Control which cohorts can access this subplugin';
$string['manage_cohorts'] = 'Manage Cohorts';
$string['manage_cohorts_link'] = 'Configure Cohort Access';
$string['manage_cohorts_for'] = 'Manage Cohorts for {$a}';
$string['current_access'] = 'Current Access Status';
$string['access_unrestricted'] = 'Unrestricted - All users have access';
$string['access_restricted_stats'] = 'Restricted - {$a->total_cohorts} cohorts, {$a->total_users} users';

// Cohort Management Page
$string['cohort_management'] = 'Cohort Management';
$string['managing_cohorts_for'] = 'Managing cohorts for';
$string['back_to_settings'] = 'Back to Settings';
$string['unrestricted_access'] = 'Unrestricted Access';
$string['restricted_access'] = 'Restricted Access';
$string['assigned_cohorts'] = 'Assigned Cohorts';
$string['available_cohorts'] = 'Available Cohorts';
$string['users_with_access'] = 'Users with Access';
$string['clear_all'] = 'Clear All';
$string['no_cohort_restrictions'] = 'No Restrictions';
$string['everyone_has_access'] = 'Everyone has access to this subplugin';
$string['all_cohorts_assigned'] = 'All Cohorts Assigned';
$string['no_more_cohorts_available'] = 'No more cohorts available to assign';

// How it works
$string['how_cohort_access_works'] = 'How Cohort Access Works';
$string['cohort_rule_1'] = 'If no cohorts are assigned, ALL users have access';
$string['cohort_rule_2'] = 'If cohorts are assigned, ONLY members of those cohorts have access';
$string['cohort_rule_3'] = 'Users must be in at least ONE of the assigned cohorts';

// Actions and confirmations
$string['confirm_clear_all'] = 'Are you sure you want to remove all cohort restrictions? This will give access to ALL users.';
$string['confirm_remove_cohort'] = 'Are you sure you want to remove this cohort restriction?';
$string['confirm_action'] = 'Are you sure you want to perform this action?';
$string['cohort_added'] = 'Cohort access restriction added successfully';
$string['cohort_removed'] = 'Cohort access restriction removed successfully';
$string['cohorts_cleared'] = 'All cohort restrictions cleared successfully';
$string['cohort_add_failed'] = 'Failed to add cohort restriction';
$string['cohort_remove_failed'] = 'Failed to remove cohort restriction';
$string['cohorts_clear_failed'] = 'Failed to clear cohort restrictions';

// Help and examples
$string['help_and_examples'] = 'Help & Examples';
$string['common_use_cases'] = 'Common Use Cases';
$string['use_case_pilot'] = 'Pilot testing with selected users';
$string['use_case_department'] = 'Department-specific tool access';
$string['use_case_premium'] = 'Premium features for paid users';
$string['use_case_gradual'] = 'Gradual rollout to user groups';
$string['management_tips'] = 'Management Tips';
$string['tip_test_first'] = 'Test with small cohorts first';
$string['tip_monitor_usage'] = 'Monitor usage and feedback';
$string['tip_communicate'] = 'Communicate changes to users';
$string['tip_backup_plan'] = 'Have a rollback plan ready';

// Capabilities
$string['aitools:view'] = 'View AI Tools Dashboard';
$string['aitools:manage'] = 'Manage AI Tools';

// Privacy
$string['privacy:metadata'] = 'The AI Tools plugin does not store any personal data.';