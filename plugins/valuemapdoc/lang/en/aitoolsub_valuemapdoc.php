<?php
defined('MOODLE_INTERNAL') || die();

// === PODSTAWOWE STRINGI PLUGINU ===
$string['pluginname'] = 'ValueMapDoc AI Tools';
$string['plugin_description'] = 'AI-powered tools for value mapping and document generation';

// === PODSTAWOWE AKCJE (wcześniej używałeś core) ===
$string['export'] = 'Export';
$string['refresh'] = 'Refresh';
$string['please_wait'] = 'Please wait...';
$string['try_again'] = 'Try Again';
$string['close'] = 'Close';
$string['id'] = 'ID';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['loading'] = 'Loading...';

// === DASHBOARD BLOCKS ===
$string['valuemap_summary'] = 'ValueMapDoc Summary';
$string['quick_stats'] = 'Quick Statistics';
$string['documents'] = 'Documents';
$string['value_maps'] = 'Value Maps';
$string['recent_activity'] = 'Recent Activity';
$string['no_recent_activity'] = 'No recent activity';
$string['view_all_content'] = 'View All Content';
$string['total_docs'] = 'Total Docs';
$string['this_week'] = 'This Week';
$string['new'] = 'new';
$string['view_analytics'] = 'View Analytics';

// === TOOLS ===
$string['my_content'] = 'My Content';
$string['my_content_desc'] = 'View and manage all your generated documents across courses';
$string['my_valuemaps'] = 'My Value Maps';
$string['my_valuemaps_desc'] = 'Access your value map entries and templates';
$string['content_analytics'] = 'Content Analytics';
$string['content_analytics_desc'] = 'Analyze effectiveness and performance of your content';

// === MY VALUE MAPS DASHBOARD ===
$string['my_valuemaps_dashboard'] = 'My Value Maps Dashboard';
$string['valuemaps_dashboard_subtitle'] = 'Manage all your value map entries from one central location';
$string['value_map_entries'] = 'Value Map Entries';

// === STATISTICS ===
$string['total_entries'] = 'Total Entries';
$string['courses_with_entries'] = 'Courses with Entries';
$string['activities_with_entries'] = 'Activities with Entries';

// === ENTRY TYPES ===
$string['entry_type'] = 'Entry Type';
$string['customer_profile'] = 'Customer Profile';
$string['value_proposition'] = 'Value Proposition';
$string['pain_analysis'] = 'Pain Analysis';
$string['value_map'] = 'Value Map';
$string['general'] = 'General';
$string['unknown'] = 'Unknown';

// === FILTERS AND SEARCH ===
$string['all_courses'] = 'All Courses';
$string['all_activities'] = 'All Activities';
$string['all_types'] = 'All Types';
$string['clear_filters'] = 'Clear Filters';
$string['search_entries_placeholder'] = 'Search in entries, courses, activities...';
$string['filter_options'] = 'Filter Options';

// === TABLE COLUMNS ===
$string['preview'] = 'Preview';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['columns'] = 'Columns';
$string['column_visibility'] = 'Column Visibility';
$string['apply'] = 'Apply';

// === VIEW MODES ===
$string['view_only_mode'] = 'View-Only Mode';
$string['view_only_description'] = 'This is a global overview of your value map entries. To edit entries, click the edit button to open the activity in context.';
$string['fullscreen'] = 'Fullscreen';

// === STATES AND MESSAGES ===
$string['loading_entries'] = 'Loading Entries';
$string['no_entries_yet'] = 'No Entries Yet';
$string['no_entries_description'] = 'You haven\'t created any value map entries yet. Start by visiting your courses and using ValueMapDoc activities.';
$string['error_loading_entries'] = 'Error Loading Entries';
$string['browse_courses'] = 'Browse Courses';

// === EXPORT OPTIONS ===
$string['export_csv'] = 'Export as CSV';
$string['export_json'] = 'Export as JSON';
$string['export_xlsx'] = 'Export as Excel';

// === HELP AND TIPS ===
$string['help_and_tips'] = 'Help & Tips';
$string['navigation_tips'] = 'Navigation Tips';
$string['tip_double_click'] = 'Double-click any row to open the entry for editing in its activity context';
$string['tip_column_filters'] = 'Use column header filters for quick filtering';
$string['tip_fullscreen'] = 'Use fullscreen mode for better table viewing experience';
$string['tip_export'] = 'Export your data to CSV, JSON, or Excel formats';

// === ENTRY TYPE DESCRIPTIONS ===
$string['entry_types_help'] = 'Entry Types';
$string['customer_profile_desc'] = 'Customer information and characteristics';
$string['value_proposition_desc'] = 'Problem-solution mappings';
$string['pain_analysis_desc'] = 'Customer pain points and challenges';
$string['value_map_desc'] = 'Comprehensive value mapping entries';

// === ACTIONS ===
$string['opening_in_activity'] = 'Opening in activity context...';
$string['entry_details'] = 'Entry Details';
$string['edit_in_activity'] = 'Edit in Activity';
$string['show_details'] = 'Show Details';

// === USER FILTER ===
$string['my_entries'] = 'My Entries';
$string['all_entries'] = 'All Entries';

// === CONTENT DASHBOARD (dla my_content.php) ===
$string['my_content_dashboard'] = 'My Content Dashboard';
$string['content_dashboard_subtitle'] = 'Manage all your generated content from one central location';
$string['total_documents'] = 'Total Documents';
$string['courses_with_content'] = 'Courses with Content';
$string['activities_with_content'] = 'Activities with Content';
$string['content_by_course'] = 'Content by Course';

// === SEARCH AND FILTERS (rozszerzone) ===
$string['search_content'] = 'Search Content';
$string['search_placeholder'] = 'Search in document names and content...';
$string['all_templates'] = 'All Templates';
$string['all_statuses'] = 'All Statuses';
$string['status_ready'] = 'Ready';
$string['status_pending'] = 'Pending';
$string['status_error'] = 'Error';

// === CONTENT ITEMS ===
$string['template'] = 'Template';
$string['effectiveness'] = 'Effectiveness';
$string['no_content'] = 'No content available';
$string['custom_prompt'] = 'Custom Prompt';

// === EMPTY STATES ===
$string['no_content_yet'] = 'No Content Yet';
$string['no_content_description'] = 'You haven\'t created any documents yet. Start by visiting your courses and using ValueMapDoc activities.';

// === TIME STRINGS ===
$string['just_now'] = 'Just now';
$string['minutes_ago'] = '{$a} minutes ago';
$string['hours_ago'] = '{$a} hours ago';
$string['days_ago'] = '{$a} days ago';

// === ERROR MESSAGES ===
$string['noaccess'] = 'You do not have access to ValueMapDoc tools';

// === PRIVACY ===
$string['privacy:metadata'] = 'The ValueMapDoc AI Tools subplugin does not store any additional personal data beyond what is already stored by the main ValueMapDoc module.';

// === VALUEMAPDOC SPECIFIC FIELDS (dla entry_data) ===
$string['market'] = 'Market';
$string['industry'] = 'Industry';
$string['role'] = 'Role';
$string['businessgoal'] = 'Business Goal';
$string['strategy'] = 'Strategy';
$string['difficulty'] = 'Difficulty';
$string['situation'] = 'Situation';
$string['statusquo'] = 'Status Quo';
$string['coi'] = 'Cost of Inaction';
$string['differentiator'] = 'Differentiator';
$string['impact'] = 'Impact';
$string['newstate'] = 'New State';
$string['successmetric'] = 'Success Metric';
$string['impactstrategy'] = 'Impact on Strategy';
$string['impactbusinessgoal'] = 'Impact on Business Goal';
$string['impactothers'] = 'Impact on Others';
$string['proof'] = 'Proof';
$string['time2results'] = 'Time to Results';
$string['quote'] = 'Quote';
$string['clientname'] = 'Client Name';

// === TABLE FUNCTIONALITY ===
$string['sort_ascending'] = 'Sort ascending';
$string['sort_descending'] = 'Sort descending';
$string['filter_column'] = 'Filter column';
$string['show_all'] = 'Show all';
$string['hide_column'] = 'Hide column';
$string['show_column'] = 'Show column';

// === PAGINATION ===
$string['page_size'] = 'Page Size';
$string['first'] = 'First';
$string['last'] = 'Last';
$string['previous'] = 'Previous';
$string['next'] = 'Next';
$string['page'] = 'Page';
$string['of'] = 'of';
$string['showing'] = 'Showing';
$string['entries'] = 'entries';

// === MODAL DIALOGS ===
$string['close'] = 'Close';
$string['cancel'] = 'Cancel';
$string['confirm'] = 'Confirm';
$string['save'] = 'Save';

// === NOTIFICATIONS ===
$string['success'] = 'Success';
$string['error'] = 'Error';
$string['warning'] = 'Warning';
$string['info'] = 'Information';

// === ACTIONS EXTENDED ===
$string['refresh'] = 'Refresh';
$string['reload'] = 'Reload';
$string['try_again'] = 'Try Again';
$string['please_wait'] = 'Please wait...';
$string['loading'] = 'Loading...';

// === ACCESSIBILITY ===
$string['screen_reader_table'] = 'Value map entries table';
$string['screen_reader_filter'] = 'Filter entries by {$a}';
$string['screen_reader_sort'] = 'Sort by {$a}';
$string['screen_reader_page'] = 'Page {$a}';

// === RESPONSIVE ===
$string['mobile_view'] = 'Mobile View';
$string['desktop_view'] = 'Desktop View';
$string['tablet_view'] = 'Tablet View';

// === ADVANCED FEATURES ===
$string['bulk_actions'] = 'Bulk Actions';
$string['select_all'] = 'Select All';
$string['deselect_all'] = 'Deselect All';
$string['selected_items'] = 'Selected Items';

// === DEBUG/DEVELOPMENT ===
$string['debug_mode'] = 'Debug Mode';
$string['development_mode'] = 'Development Mode';
$string['test_data'] = 'Test Data';

$string['export'] = 'Export';
$string['close'] = 'Close';
$string['try_again'] = 'Try Again';
$string['please_wait'] = 'Please wait...'; 
$string['id'] = 'ID';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['loading'] = 'Loading...';
$string['search'] = 'Search';
$string['activity'] = 'Activity';
$string['all_activities'] = 'All Activities';
$string['course'] = 'Course';
$string['all_courses'] = 'All Courses';
$string['type'] = 'Type';
$string['all_types'] = 'All Types';
$string['clear_filters'] = 'Clear Filters';
$string['filter_options'] = 'Filter Options';
$string['preview'] = 'Preview';
$string['actions'] = 'Actions';