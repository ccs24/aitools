<?php
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/valuemapdoc/classes/local/field_levels.php');

use mod_valuemapdoc\local\field_levels;

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

$PAGE->requires->js_call_amd('aitoolsub_valuemapdoc/valuemaps_manager', 'init');

// Add navigation
$PAGE->navbar->add('AI Tools', new moodle_url('/local/aitools/'));
$PAGE->navbar->add('My Value Maps');

// Get user's field level and filter columns accordingly (like in module)
$user_fields = field_levels::get_user_fields();
$user_level_config = field_levels::get_user_level_config();

// Define all possible columns (same as in module)
$all_columns = [
    'market', 'industry', 'role', 'businessgoal', 'strategy', 'difficulty',
    'situation', 'statusquo', 'coi', 'differentiator', 'impact', 'newstate',
    'successmetric', 'impactstrategy', 'impactbusinessgoal', 'impactothers',
    'proof', 'time2results', 'quote', 'clientname'
];

// Filter columns based on user's level (same logic as module)
$columns = array_values(array_intersect($all_columns, $user_fields));

// Generate columns JSON for JavaScript (same as module)
$columnsjson = json_encode(array_map(function($c) {
    return [
        'title' => get_string($c, 'mod_valuemapdoc'),
        'field' => $c,
        'hozAlign' => 'left',
        'headerSort' => true,
        'width' => 150
    ];
}, $columns), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

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
        'userid' => $USER->id,
        'columns' => $columnsjson,
        'field_level' => [
            'current_level' => field_levels::get_user_level(),
            'level_name' => $user_level_config['name'],
            'fields_count' => $user_level_config['fields_count'],
            'preferences_url' => new moodle_url('/mod/valuemapdoc/preferences.php', [
                'returnurl' => $PAGE->url->out(false)
            ])
        ]
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
        'columns' => '[]',
        'field_level' => [
            'current_level' => 'basic',
            'level_name' => 'Basic',
            'fields_count' => 7,
            'preferences_url' => new moodle_url('/mod/valuemapdoc/preferences.php')
        ],
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

// Add columns JSON for JavaScript (same pattern as module)
//echo '<script type="application/json" id="valuemap-columns">' . $template_data['columns'] . '</script>';

echo $OUTPUT->footer();