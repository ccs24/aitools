<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'aitoolsub_valuemapdoc_get_user_content_global' => [
        'classname'   => 'aitoolsub_valuemapdoc\external\get_user_content_global',
        'methodname'  => 'execute',
        'description' => 'Get all user content from ValueMapDoc across all courses',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/aitools:view',
    ],
    'aitoolsub_valuemapdoc_get_all_entries_global' => [
        'classname'   => 'aitoolsub_valuemapdoc\external\get_all_entries_global',
        'methodname'  => 'execute',
        'description' => 'Get all ValueMapDoc entries that user has access to',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/aitools:view',
    ]
];