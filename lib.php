<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation to add AI Tools
 */
function local_aitools_extend_navigation(global_navigation $navigation) {
    global $USER;
    
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    $context = context_system::instance();
    if (!has_capability('local/aitools:view', $context)) {
        return;
    }
    
    $aitools_node = $navigation->add(
        get_string('aitools', 'local_aitools'),
        new moodle_url('/local/aitools/'),
        navigation_node::TYPE_CUSTOM,
        null,
        'aitools',
        new pix_icon('i/settings', '')
    );
    
    $aitools_node->showinflatnavigation = true;
}

/**
 * Add AI Tools to user menu
 */
function local_aitools_extend_navigation_user_settings(navigation_node $navigation, stdClass $user, context_user $context, stdClass $course, context_course $coursecontext) {
    global $USER;
    
    if ($USER->id != $user->id) {
        return; // Only for current user
    }
    
    $systemcontext = context_system::instance();
    if (!has_capability('local/aitools:view', $systemcontext)) {
        return;
    }
    
    $aitools_node = $navigation->add(
        get_string('my_aitools', 'local_aitools'),
        new moodle_url('/local/aitools/'),
        navigation_node::TYPE_SETTING,
        null,
        'aitools'
    );
}

/**
 * Serve the plugin files
 */
function local_aitools_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Handle file serving if needed
    return false;
}