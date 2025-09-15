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
$PAGE->set_url('/local/aitools/plugins/valuemapdoc/my_content.php');
$PAGE->set_title('My Content - ValueMapDoc');
$PAGE->set_heading('My Content Dashboard');
$PAGE->set_pagelayout('standard');

// Add navigation
$PAGE->navbar->add('AI Tools', new moodle_url('/local/aitools/'));
$PAGE->navbar->add('My Content');

echo $OUTPUT->header();

echo html_writer::start_div('container-fluid');
echo html_writer::tag('h2', '📄 My Content Dashboard', ['class' => 'mb-4']);

// Status info
echo html_writer::start_div('alert alert-success');
echo html_writer::tag('h4', '🎉 ValueMapDoc Subplugin Working!');
echo html_writer::tag('p', 'This is a placeholder for the full content management interface.');
echo html_writer::tag('p', 'In the complete version, you would see all your documents from across all courses here.');
echo html_writer::end_div();

// Basic stats
global $USER, $DB;
try {
    $content_count = 0;
    $entries_count = 0;
    
    if ($DB->get_manager()->table_exists('valuemapdoc_content')) {
        $content_count = $DB->count_records('valuemapdoc_content', ['userid' => $USER->id]);
    }
    
    if ($DB->get_manager()->table_exists('valuemapdoc_entries')) {
        $entries_count = $DB->count_records('valuemapdoc_entries', ['userid' => $USER->id]);
    }
    
    echo html_writer::start_div('row mb-4');
    
    // Documents card
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h3', $content_count, ['class' => 'text-primary']);
    echo html_writer::tag('p', 'Generated Documents');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Value Maps card
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h3', $entries_count, ['class' => 'text-success']);
    echo html_writer::tag('p', 'Value Map Entries');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    echo html_writer::end_div(); // row
    
} catch (Exception $e) {
    echo html_writer::start_div('alert alert-warning');
    echo html_writer::tag('p', 'Could not load statistics: ' . $e->getMessage());
    echo html_writer::end_div();
}

// Next steps
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Next Steps', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::tag('p', '✅ Step 4: ValueMapDoc subplugin installed and working');
echo html_writer::tag('p', '⏳ Step 5: Add cohort management functionality');
echo html_writer::tag('p', '⏳ Step 6: Add complete content management interface');
echo html_writer::tag('p', '⏳ Step 7: Add advanced analytics and reporting');

echo html_writer::start_div('mt-3');
echo html_writer::link(
    new moodle_url('/local/aitools/'),
    'Back to AI Tools Dashboard',
    ['class' => 'btn btn-outline-primary me-2']
);

// Link to ValueMapDoc if available
if (file_exists($CFG->dirroot . '/mod/valuemapdoc/view.php')) {
    echo html_writer::link(
        new moodle_url('/course/'),
        'Browse Courses with ValueMapDoc',
        ['class' => 'btn btn-outline-secondary']
    );
}
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // container

echo $OUTPUT->footer();