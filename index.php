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

// Prepare blocks with column size classes and pre-rendered content
$prepared_blocks = [];
$renderer = $PAGE->get_renderer('local_aitools');

foreach ($blocks as $block) {
    // Determine column size based on block size
    switch ($block['size'] ?? 'medium') {
        case 'large':
            $column_class = 'col-md-6';
            break;
        case 'small':
            $column_class = 'col-md-3';
            break;
        case 'medium':
        default:
            $column_class = 'col-md-4';
            break;
    }
    
    $block['column_class'] = $column_class;
    
    // Pre-render the block content using the specified template
    try {
        if (isset($block['template']) && !empty($block['template'])) {
            $block['rendered_content'] = $renderer->render_from_template($block['template'], $block);
        } else {
            $block['rendered_content'] = '<p class="text-muted">No template specified</p>';
        }
    } catch (Exception $e) {
        // Fallback if template rendering fails
        $block['rendered_content'] = '<div class="alert alert-warning">Template error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        error_log('AI Tools block rendering error: ' . $e->getMessage());
    }
    
    $prepared_blocks[] = $block;
}

// Prepare template data
$template_data = [
    'user_fullname' => fullname($USER),
    'has_blocks' => !empty($prepared_blocks),
    'blocks' => $prepared_blocks,
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