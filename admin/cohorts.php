<?php
require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_aitools\cohort_manager;

// POPRAWKA: Używamy nazwy external page zdefiniowanej w settings.php
admin_externalpage_setup('local_aitools_cohorts');

$subplugin = required_param('subplugin', PARAM_COMPONENT);
$action = optional_param('action', '', PARAM_ALPHA);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

require_capability('local/aitools:manage', context_system::instance());

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/aitools/admin/cohorts.php', ['subplugin' => $subplugin]);
$PAGE->set_title(get_string('manage_cohorts', 'local_aitools'));
$PAGE->set_heading(get_string('manage_cohorts_for', 'local_aitools', $subplugin));

// Handle actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'add':
            if ($cohortid && cohort_manager::add_cohort_restriction($subplugin, $cohortid)) {
                \core\notification::success(get_string('cohort_added', 'local_aitools'));
            } else {
                \core\notification::error(get_string('cohort_add_failed', 'local_aitools'));
            }
            break;
            
        case 'remove':
            if ($cohortid && cohort_manager::remove_cohort_restriction($subplugin, $cohortid)) {
                \core\notification::success(get_string('cohort_removed', 'local_aitools'));
            } else {
                \core\notification::error(get_string('cohort_remove_failed', 'local_aitools'));
            }
            break;
            
        case 'clear':
            if (cohort_manager::clear_cohort_restrictions($subplugin)) {
                \core\notification::success(get_string('cohorts_cleared', 'local_aitools'));
            } else {
                \core\notification::error(get_string('cohorts_clear_failed', 'local_aitools'));
            }
            break;
    }
    
    redirect($PAGE->url);
}

// Get data for page
$current_cohorts = cohort_manager::get_subplugin_cohorts($subplugin);
$all_cohorts = cohort_manager::get_all_cohorts();
$access_stats = cohort_manager::get_access_statistics($subplugin);

// Remove already assigned cohorts from available list
foreach ($current_cohorts as $current) {
    unset($all_cohorts[$current->id]);
}

// Prepare template data
$template_data = [
    'subplugin' => $subplugin,
    'subplugin_clean' => str_replace('aitoolsub_', '', $subplugin),
    'has_current_cohorts' => !empty($current_cohorts),
    'current_cohorts' => array_values($current_cohorts),
    'has_available_cohorts' => !empty($all_cohorts),
    'available_cohorts' => array_values($all_cohorts),
    'access_stats' => $access_stats,
    'sesskey' => sesskey(),
    'back_url' => new moodle_url('/admin/settings.php', [
        'section' => 'local_aitools_' . str_replace('aitoolsub_', '', $subplugin)
    ])
];

// Add URLs for actions
foreach ($template_data['current_cohorts'] as &$cohort) {
    $cohort->remove_url = new moodle_url($PAGE->url, [
        'action' => 'remove',
        'cohortid' => $cohort->id,
        'sesskey' => sesskey()
    ]);
}

foreach ($template_data['available_cohorts'] as &$cohort) {
    $cohort->add_url = new moodle_url($PAGE->url, [
        'action' => 'add',
        'cohortid' => $cohort->id,
        'sesskey' => sesskey()
    ]);
}

$template_data['clear_all_url'] = new moodle_url($PAGE->url, [
    'action' => 'clear',
    'sesskey' => sesskey()
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aitools/cohort_management', $template_data);
echo $OUTPUT->footer();