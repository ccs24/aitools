<?php
require_once('../../config.php');

use local_aitools\manager;

require_login();
require_capability('local/aitools:view', context_system::instance());

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/aitools/');
$PAGE->set_title(get_string('aitools', 'local_aitools'));
$PAGE->set_heading(get_string('aitools_dashboard', 'local_aitools'));
$PAGE->set_pagelayout('standard');

// Add CSS and JS
$PAGE->requires->css('/local/aitools/styles/dashboard.css');
$PAGE->requires->js_call_amd('local_aitools/dashboard', 'init');

// Get dashboard data
$blocks = manager::get_dashboard_blocks();
$tools = manager::get_tools();
$statistics = manager::get_statistics();

// Prepare template data
$template_data = [
    'user_fullname' => fullname($USER),
    'has_blocks' => !empty($blocks),
    'blocks' => $blocks,
    'has_tools' => !empty($tools),
    'tools_categories' => [],
    'statistics' => $statistics,
    'can_manage' => has_capability('local/aitools:manage', $context)
];

// Group tools by category
foreach ($tools as $category => $category_tools) {
    $template_data['tools_categories'][] = [
        'category_name' => get_string('category_' . $category, 'local_aitools'),
        'category_key' => $category,
        'tools' => $category_tools,
        'tools_count' => count($category_tools)
    ];
}

$renderer = $PAGE->get_renderer('local_aitools');

echo $OUTPUT->header();
echo $renderer->render_from_template('local_aitools/dashboard', $template_data);
echo $OUTPUT->footer();