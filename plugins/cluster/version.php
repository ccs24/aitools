<?php
defined('MOODLE_INTERNAL') || die();

// Plugin version information
$plugin->version   = 2025092600;        // YYYYMMDDHH format
$plugin->requires  = 2024042200;        // Moodle 4.4+ (but works with 5.0)
$plugin->component = 'aitoolsub_cluster';
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.0.0';

// Plugin dependencies
$plugin->dependencies = [
    'local_aitools' => 2025091503,           // Requires main AI Tools plugin
    'aitoolsub_valuemapdoc' => 2025080106    // Requires ValueMapDoc for data integration
];