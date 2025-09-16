<?php
require_once('../../../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/aitools:view', $context);

// Check if user has access to valuemapdoc
$plugin = new \aitoolsub_valuemapdoc\plugin();
if (!$plugin->has_access()) {
    throw new moodle_exception('noaccess', 'aitoolsub_valuemapdoc');
}

$PAGE->set_context($context);
$PAGE->set_url('/local/aitools/plugins/valuemapdoc/my_valuemaps.php');
$PAGE->set_title('My Value Maps - ValueMapDoc');
$PAGE->set_heading('My Value Maps Dashboard');
$PAGE->set_pagelayout('standard');

// UŻYWAMY PLIKÓW BEZPOŚREDNIO Z MOD_VALUEMAPDOC
// CSS z modułu ValueMapDoc
$PAGE->requires->css('/mod/valuemapdoc/styles/tabulator_bootstrap5.min.css');

// JS z modułu ValueMapDoc  
$PAGE->requires->js('/mod/valuemapdoc/scripts/tabulator.min.js', true);

// Nasze lokalne style (tylko nasze customizacje)
$PAGE->requires->css('/local/aitools/plugins/valuemapdoc/styles/valuemaps.css');

// Nasz JavaScript manager
$PAGE->requires->js_call_amd('aitoolsub_valuemapdoc/valuemaps_manager', 'init');

// Add navigation
$PAGE->navbar->add('AI Tools', new moodle_url('/local/aitools/'));
$PAGE->navbar->add('My Value Maps');

// Get initial data for page statistics
try {
    // We'll load the actual data via AJAX, but get basic stats for the header
    $initial_stats = [
        'total_entries' => 0,
        'unique_courses' => 0,
        'unique_activities' => 0,
        'loading' => true
    ];
    
    // Prepare template data
    $template_data = [
        'user_fullname' => fullname($USER),
        'statistics' => $initial_stats,
        'back_url' => new moodle_url('/local/aitools/'),
        'ajax_url' => new moodle_url('/lib/ajax/service.php'),
        'sesskey' => sesskey(),
        'userid' => $USER->id
    ];
    
} catch (Exception $e) {
    // Error handling
    $template_data = [
        'user_fullname' => fullname($USER),
        'statistics' => [
            'total_entries' => 0,
            'unique_courses' => 0,
            'unique_activities' => 0,
            'loading' => false
        ],
        'back_url' => new moodle_url('/local/aitools/'),
        'ajax_url' => new moodle_url('/lib/ajax/service.php'),
        'sesskey' => sesskey(),
        'userid' => $USER->id,
        'error_message' => 'Could not load initial data: ' . $e->getMessage()
    ];
    
    error_log('ValueMapDoc my_valuemaps.php error: ' . $e->getMessage());
}

echo $OUTPUT->header();

// If there's an error, show it
if (isset($template_data['error_message'])) {
    echo html_writer::div(
        html_writer::tag('h4', 'Error Loading Data') .
        html_writer::tag('p', $template_data['error_message']),
        'alert alert-warning'
    );
}

// Render the template
$renderer = $PAGE->get_renderer('aitoolsub_valuemapdoc');
echo $renderer->render_from_template('aitoolsub_valuemapdoc/my_valuemaps', $template_data);

echo $OUTPUT->footer();