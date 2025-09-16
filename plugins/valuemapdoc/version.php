<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'aitoolsub_valuemapdoc';
$plugin->version   = 2025091501;
$plugin->requires  = 2024042200; // Moodle 4.4+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
$plugin->dependencies = [
    'mod_valuemapdoc' => 2025080106,  // Wymaga głównego pluginu ValueMapDoc
    'local_aitools' => 2025091503     // Wymaga AI Tools
];