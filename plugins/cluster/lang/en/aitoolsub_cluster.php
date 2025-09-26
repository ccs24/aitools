<?php
defined('MOODLE_INTERNAL') || die();

// Plugin information
$string['pluginname'] = 'AI Tools: Sales Clusters';
$string['cluster'] = 'Sales Clusters';

// Capabilities
$string['cluster:view'] = 'View sales clusters';
$string['cluster:manage'] = 'Manage sales clusters';
$string['cluster:create'] = 'Create sales clusters';
$string['cluster:delete'] = 'Delete sales clusters';

// Navigation and pages
$string['clustermanagement'] = 'Cluster Management';
$string['manageclusters'] = 'Manage Clusters';
$string['companyresearch'] = 'Company Research';
$string['messagegenerator'] = 'Message Generator';
$string['saleswarroom'] = 'Sales War Room';

// Cluster operations
$string['createcluster'] = 'Create Cluster';
$string['editcluster'] = 'Edit Cluster';
$string['deletecluster'] = 'Delete Cluster';
$string['clustercreated'] = 'Cluster created successfully';
$string['clusterupdated'] = 'Cluster updated successfully';
$string['clusterdeleted'] = 'Cluster deleted successfully';
$string['clustername'] = 'Cluster Name';
$string['clusterdescription'] = 'Cluster Description';
$string['clustermarket'] = 'Market';
$string['clusterstatus'] = 'Status';

// Cluster statuses
$string['status_planning'] = 'Planning';
$string['status_active'] = 'Active';
$string['status_paused'] = 'Paused';
$string['status_completed'] = 'Completed';

// Company operations
$string['addcompany'] = 'Add Company';
$string['editcompany'] = 'Edit Company';
$string['deletecompany'] = 'Delete Company';
$string['companyname'] = 'Company Name';
$string['companyindustry'] = 'Industry';
$string['companywebsite'] = 'Website';
$string['companylinkedin'] = 'LinkedIn URL';
$string['companydescription'] = 'Company Description';
$string['companyadded'] = 'Company added successfully';
$string['companyupdated'] = 'Company updated successfully';
$string['companydeleted'] = 'Company deleted successfully';
$string['managecompanies'] = 'Manage Companies';

// Person operations
$string['addperson'] = 'Add Contact';
$string['editperson'] = 'Edit Contact';
$string['deleteperson'] = 'Delete Contact';
$string['personname'] = 'Full Name';
$string['personemail'] = 'Email Address';
$string['personrole'] = 'Role';
$string['personlinkedin'] = 'LinkedIn Profile';
$string['persondescription'] = 'Notes';
$string['personadded'] = 'Contact added successfully';
$string['personupdated'] = 'Contact updated successfully';
$string['persondeleted'] = 'Contact deleted successfully';
$string['managepersons'] = 'Manage Contacts';

// Message operations
$string['createmessage'] = 'Create Message';
$string['generatemessage'] = 'Generate with AI';
$string['editmessage'] = 'Edit Message';
$string['deletemessage'] = 'Delete Message';
$string['messagetitle'] = 'Message Title';
$string['messagecontent'] = 'Message Content';
$string['messagechannel'] = 'Channel';
$string['messagepriority'] = 'Priority';
$string['targetpersona'] = 'Target Persona';
$string['effectivenessscore'] = 'Effectiveness Score';
$string['usagecount'] = 'Usage Count';
$string['successcount'] = 'Success Count';
$string['messagecreated'] = 'Message created successfully';
$string['messageupdated'] = 'Message updated successfully';
$string['messagedeleted'] = 'Message deleted successfully';
$string['trackusage'] = 'Track Usage';
$string['managemessages'] = 'Manage Messages';

// Message channels
$string['channel_email'] = 'Email';
$string['channel_linkedin'] = 'LinkedIn';
$string['channel_phone'] = 'Phone';

// Message priorities
$string['priority_low'] = 'Low';
$string['priority_medium'] = 'Medium';
$string['priority_high'] = 'High';

// Research and AI
$string['generateresearch'] = 'Generate Research';
$string['researchreport'] = 'Research Report';
$string['aisuggestions'] = 'AI Suggestions';
$string['valuemapintegration'] = 'ValueMap Integration';
$string['refreshdata'] = 'Refresh Data';
$string['loadingdata'] = 'Loading data...';
$string['generatingcontent'] = 'Generating content...';

// Filters and search
$string['filterby'] = 'Filter by';
$string['searchclusters'] = 'Search clusters...';
$string['allstatuses'] = 'All Statuses';
$string['allmarkets'] = 'All Markets';
$string['clearfilters'] = 'Clear Filters';
$string['noresults'] = 'No results found';
$string['showing'] = 'Showing';
$string['of'] = 'of';
$string['results'] = 'results';

// Table headers
$string['name'] = 'Name';
$string['market'] = 'Market';
$string['industry'] = 'Industry';
$string['status'] = 'Status';
$string['companies'] = 'Companies';
$string['contacts'] = 'Contacts';
$string['messages'] = 'Messages';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['actions'] = 'Actions';
$string['owner'] = 'Owner';
$string['role'] = 'Role';
$string['email'] = 'Email';
$string['website'] = 'Website';
$string['linkedin'] = 'LinkedIn';

// Dashboard
$string['dashboardsummary'] = 'Cluster Summary';
$string['recentactivity'] = 'Recent Activity';
$string['totalclusters'] = 'Total Clusters';
$string['activeclusters'] = 'Active Clusters';
$string['planningclusters'] = 'Planning Clusters';
$string['completedclusters'] = 'Completed Clusters';
$string['totalcompanies'] = 'Total Companies';
$string['totalcontacts'] = 'Total Contacts';
$string['viewall'] = 'View All';

// Team collaboration
$string['sharecluster'] = 'Share Cluster';
$string['sharedwith'] = 'Shared with';
$string['accesslevel'] = 'Access Level';
$string['accessview'] = 'View Only';
$string['accessedit'] = 'Can Edit';
$string['accessmanage'] = 'Can Manage';
$string['sharedsuccess'] = 'Cluster shared successfully';
$string['removeshare'] = 'Remove Share';
$string['teamaccess'] = 'Team Access';

// Conversion to opportunity
$string['converttoopportunity'] = 'Convert to Opportunity';
$string['selectcompany'] = 'Select Company';
$string['opportunityvalue'] = 'Deal Value';
$string['expectedclose'] = 'Expected Close Date';
$string['conversionsuccessful'] = 'Successfully converted to opportunity';

// Errors and validation
$string['noaccess'] = 'You do not have access to this feature';
$string['invalidcluster'] = 'Invalid cluster ID';
$string['invalidcompany'] = 'Invalid company ID';
$string['invalidperson'] = 'Invalid person ID';
$string['clusternamerequired'] = 'Cluster name is required';
$string['companynamerequired'] = 'Company name is required';
$string['personnamerequired'] = 'Person name is required';
$string['errorcreatecluster'] = 'Error creating cluster';
$string['errorupdatecluster'] = 'Error updating cluster';
$string['errordeletecluster'] = 'Error deleting cluster';
$string['erroraddcompany'] = 'Error adding company';
$string['errorupdatecompany'] = 'Error updating company';
$string['errordeletecompany'] = 'Error deleting company';
$string['erroraddperson'] = 'Error adding person';
$string['errorupdateperson'] = 'Error updating person';
$string['errordeleteperson'] = 'Error deleting person';
$string['errorcreatemessage'] = 'Error creating message';
$string['errorupdatemessage'] = 'Error updating message';
$string['errordeletemessage'] = 'Error deleting message';
$string['errorloadingdata'] = 'Error loading data';
$string['errorgeneratingai'] = 'Error generating AI content';

// Confirmation messages
$string['confirmdelete'] = 'Are you sure you want to delete this item?';
$string['confirmdeletecluster'] = 'Are you sure you want to delete this cluster? This will also delete all associated companies, contacts, and messages.';
$string['confirmdeletecompany'] = 'Are you sure you want to delete this company? This will also delete all associated contacts.';
$string['confirmdeleteperson'] = 'Are you sure you want to delete this contact?';
$string['confirmdeletemessage'] = 'Are you sure you want to delete this message?';
$string['cannotbeundone'] = 'This action cannot be undone.';

// Empty states
$string['noclusters'] = 'No clusters found';
$string['noclustersdesc'] = 'Create your first sales cluster to get started with strategic campaign management.';
$string['nocompanies'] = 'No companies found';
$string['nocompaniesdesc'] = 'Add companies to this cluster to start building your target list.';
$string['nopersons'] = 'No contacts found';
$string['nopersonsdesc'] = 'Add contacts to this company to build your outreach list.';
$string['nomessages'] = 'No messages found';
$string['nomessagesdesc'] = 'Create or generate AI-powered messages for your sales campaigns.';
$string['noactivity'] = 'No recent activity';
$string['noactivitydesc'] = 'Start creating clusters to see activity here.';

// Buttons and actions
$string['create'] = 'Create';
$string['save'] = 'Save';
$string['cancel'] = 'Cancel';
$string['delete'] = 'Delete';
$string['edit'] = 'Edit';
$string['view'] = 'View';
$string['close'] = 'Close';
$string['back'] = 'Back';
$string['next'] = 'Next';
$string['previous'] = 'Previous';
$string['refresh'] = 'Refresh';
$string['export'] = 'Export';
$string['import'] = 'Import';
$string['copy'] = 'Copy';
$string['share'] = 'Share';
$string['convert'] = 'Convert';
$string['generate'] = 'Generate';
$string['search'] = 'Search';
$string['filter'] = 'Filter';
$string['sort'] = 'Sort';
$string['select'] = 'Select';
$string['selectall'] = 'Select All';
$string['deselectall'] = 'Deselect All';

// Form labels and placeholders
$string['required'] = 'Required';
$string['optional'] = 'Optional';
$string['placeholder_clustername'] = 'e.g., Enterprise Software Q4 2025';
$string['placeholder_companyname'] = 'e.g., Acme Corporation';
$string['placeholder_personname'] = 'e.g., John Smith';
$string['placeholder_email'] = 'e.g., john.smith@company.com';
$string['placeholder_website'] = 'e.g., https://www.company.com';
$string['placeholder_linkedin'] = 'e.g., https://linkedin.com/company/acme';
$string['placeholder_search'] = 'Type to search...';

// Help and tooltips
$string['help_clustername'] = 'Choose a descriptive name for your sales campaign';
$string['help_clustermarket'] = 'Markets are loaded from ValueMapDoc. You can also type a custom market.';
$string['help_clusterdescription'] = 'Describe your sales strategy, target criteria, and campaign goals';
$string['help_companyindustry'] = 'Industries are loaded from ValueMapDoc. You can also type a custom industry.';
$string['help_personrole'] = 'Roles are loaded from ValueMapDoc. You can also type a custom role.';
$string['help_messagechannel'] = 'Select the communication channel for this message';
$string['help_effectivenessscore'] = 'Rate this message effectiveness from 1-5 based on responses';
$string['help_aisuggestions'] = 'AI suggestions are based on data from ValueMapDoc';

// Privacy and data
$string['privacy:metadata'] = 'The Sales Clusters plugin stores data about sales campaigns, companies, and contacts.';
$string['privacy:metadata:clusters'] = 'Information about sales clusters';
$string['privacy:metadata:companies'] = 'Information about companies in clusters';
$string['privacy:metadata:persons'] = 'Information about persons in companies';
$string['privacy:metadata:messages'] = 'Sales messages created by users';
$string['privacy:metadata:activity'] = 'Activity logs for audit purposes';

// Time and dates
$string['today'] = 'Today';
$string['yesterday'] = 'Yesterday';
$string['thisweek'] = 'This Week';
$string['lastweek'] = 'Last Week';
$string['thismonth'] = 'This Month';
$string['lastmonth'] = 'Last Month';
$string['daysago'] = '{$a} days ago';
$string['hoursago'] = '{$a} hours ago';
$string['minutesago'] = '{$a} minutes ago';
$string['justnow'] = 'Just now';

// Integration messages
$string['valuemapdoc_required'] = 'This feature requires the ValueMapDoc plugin to be installed and configured.';
$string['valuemapdoc_nodata'] = 'No data found in ValueMapDoc. Please add some entries first.';
$string['n8n_integration'] = 'n8n Integration';
$string['webhook_url'] = 'Webhook URL';
$string['automation_enabled'] = 'Automation Enabled';

// Success messages
$string['success'] = 'Success';
$string['operationsuccessful'] = 'Operation completed successfully';
$string['datasaved'] = 'Data saved successfully';
$string['dataimported'] = 'Data imported successfully';
$string['dataexported'] = 'Data exported successfully';