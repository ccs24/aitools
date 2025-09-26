<?php
defined('MOODLE_INTERNAL') || die();

// Define external services for cluster plugin
$functions = [
    // Cluster management
    'aitoolsub_cluster_create_cluster' => [
        'classname'   => 'aitoolsub_cluster\external\create_cluster',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Create a new sales cluster',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_update_cluster' => [
        'classname'   => 'aitoolsub_cluster\external\update_cluster',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Update an existing cluster',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_delete_cluster' => [
        'classname'   => 'aitoolsub_cluster\external\delete_cluster',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Delete a cluster',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_get_clusters' => [
        'classname'   => 'aitoolsub_cluster\external\get_clusters',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get user clusters with filtering',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    // Company management
    'aitoolsub_cluster_add_company' => [
        'classname'   => 'aitoolsub_cluster\external\add_company',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Add company to cluster',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_update_company' => [
        'classname'   => 'aitoolsub_cluster\external\update_company',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Update company information',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_delete_company' => [
        'classname'   => 'aitoolsub_cluster\external\delete_company',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Remove company from cluster',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_get_companies' => [
        'classname'   => 'aitoolsub_cluster\external\get_companies',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get companies in cluster',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    // Person management
    'aitoolsub_cluster_add_person' => [
        'classname'   => 'aitoolsub_cluster\external\add_person',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Add person to company',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_update_person' => [
        'classname'   => 'aitoolsub_cluster\external\update_person',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Update person information',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_delete_person' => [
        'classname'   => 'aitoolsub_cluster\external\delete_person',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Remove person from company',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_get_persons' => [
        'classname'   => 'aitoolsub_cluster\external\get_persons',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get persons in company',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    // Message management
    'aitoolsub_cluster_create_message' => [
        'classname'   => 'aitoolsub_cluster\external\create_message',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Create sales message for cluster',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_generate_message' => [
        'classname'   => 'aitoolsub_cluster\external\generate_message',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'AI-generate sales message using ValueMapDoc',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_update_message' => [
        'classname'   => 'aitoolsub_cluster\external\update_message',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Update message content and effectiveness',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_get_messages' => [
        'classname'   => 'aitoolsub_cluster\external\get_messages',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get messages for cluster',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_track_usage' => [
        'classname'   => 'aitoolsub_cluster\external\track_usage',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Track message usage and effectiveness',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    // ValueMapDoc integration
    'aitoolsub_cluster_get_valuemap_data' => [
        'classname'   => 'aitoolsub_cluster\external\get_valuemap_data',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get ValueMapDoc data for dropdowns',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    // Research and automation
    'aitoolsub_cluster_generate_research' => [
        'classname'   => 'aitoolsub_cluster\external\generate_research',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Generate research report for company/person',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_get_research' => [
        'classname'   => 'aitoolsub_cluster\external\get_research',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get research reports',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    // Team collaboration
    'aitoolsub_cluster_share_cluster' => [
        'classname'   => 'aitoolsub_cluster\external\share_cluster',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Share cluster with team members',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
    
    'aitoolsub_cluster_get_shared_access' => [
        'classname'   => 'aitoolsub_cluster\external\get_shared_access',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get shared access for cluster',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aitools:view',
        'loginrequired' => true,
    ],
];

// No services defined - all functions are standalone