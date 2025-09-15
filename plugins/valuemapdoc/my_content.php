<?php
require_once('../../../../config.php');

use aitoolsub_valuemapdoc\data_provider;

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

// Add CSS and JS
$PAGE->requires->css('/local/aitools/plugins/valuemapdoc/styles/content.css');
$PAGE->requires->js_call_amd('aitoolsub_valuemapdoc/content_manager', 'init');

// Add navigation
$PAGE->navbar->add('AI Tools', new moodle_url('/local/aitools/'));
$PAGE->navbar->add('My Content');

// Get data using data provider
try {
    // Get statistics
    $stats = data_provider::get_user_statistics($USER->id);
    
    // Get grouped content for display
    $grouped_content = data_provider::get_grouped_content($USER->id);
    
    // Get recent content for sidebar
    $all_content = data_provider::get_accessible_content($USER->id);
    $recent_content = array_slice($all_content, 0, 10);
    
    // Format recent content
    $formatted_recent = [];
    foreach ($recent_content as $item) {
        $formatted_recent[] = [
            'id' => $item->id,
            'name' => $item->name,
            'course_name' => $item->course_name,
            'activity_name' => $item->activity_name,
            'timecreated_relative' => get_relative_time($item->timecreated),
            'view_url' => new moodle_url('/mod/valuemapdoc/rate_content.php', [
                'id' => $item->cmid,
                'docid' => $item->id
            ])
        ];
    }
    
    // Format courses data for template
    $formatted_courses = [];
    foreach ($grouped_content as $course) {
        $formatted_activities = [];
        
        foreach ($course['activities'] as $activity) {
            $formatted_content = [];
            
            foreach ($activity['content'] as $content) {
                $status_class = 'bg-secondary';
                switch ($content->status) {
                    case 'ready':
                        $status_class = 'bg-success';
                        break;
                    case 'pending':
                        $status_class = 'bg-warning';
                        break;
                    case 'error':
                        $status_class = 'bg-danger';
                        break;
                }
                
                $effectiveness_class = 'bg-secondary';
                if ($content->effectiveness) {
                    if ($content->effectiveness >= 8) {
                        $effectiveness_class = 'bg-success';
                    } elseif ($content->effectiveness >= 5) {
                        $effectiveness_class = 'bg-warning';
                    } else {
                        $effectiveness_class = 'bg-danger';
                    }
                }
                
                $formatted_content[] = [
                    'id' => $content->id,
                    'name' => $content->name,
                    'template_name' => $content->template_name ?? get_string('custom_prompt', 'aitoolsub_valuemapdoc'),
                    'content_preview' => get_content_preview($content->content),
                    'status' => $content->status ?? 'ready',
                    'status_class' => $status_class,
                    'effectiveness' => $content->effectiveness ?? 0,
                    'effectiveness_class' => $effectiveness_class,
                    'has_effectiveness' => !empty($content->effectiveness),
                    'timecreated_relative' => get_relative_time($content->timecreated),
                    'view_url' => new moodle_url('/mod/valuemapdoc/rate_content.php', [
                        'id' => $content->cmid,
                        'docid' => $content->id
                    ]),
                    'edit_url' => new moodle_url('/mod/valuemapdoc/edit_content.php', [
                        'id' => $content->cmid,
                        'docid' => $content->id
                    ])
                ];
            }
            
            $formatted_activities[] = [
                'id' => $activity['id'],
                'name' => $activity['name'],
                'content_count' => $activity['content_count'],
                'content' => $formatted_content
            ];
        }
        
        $formatted_courses[] = [
            'id' => $course['id'],
            'name' => $course['name'],
            'shortname' => $course['shortname'],
            'content_count' => $course['content_count'],
            'activities' => $formatted_activities
        ];
    }
    
    // Prepare template data
    $template_data = [
        'user_fullname' => fullname($USER),
        'total_content' => $stats['total_content'],
        'total_courses' => $stats['unique_courses'],
        'total_activities' => $stats['unique_activities'],
        'has_content' => !empty($formatted_courses),
        'courses' => $formatted_courses,
        'recent_content' => $formatted_recent,
        'back_url' => new moodle_url('/local/aitools/')
    ];
    
} catch (Exception $e) {
    // Error handling - show basic error page
    $template_data = [
        'user_fullname' => fullname($USER),
        'total_content' => 0,
        'total_courses' => 0,
        'total_activities' => 0,
        'has_content' => false,
        'courses' => [],
        'recent_content' => [],
        'back_url' => new moodle_url('/local/aitools/'),
        'error_message' => 'Could not load content: ' . $e->getMessage()
    ];
    
    error_log('ValueMapDoc my_content.php error: ' . $e->getMessage());
}

echo $OUTPUT->header();

// If there's an error, show it
if (isset($template_data['error_message'])) {
    echo html_writer::div(
        html_writer::tag('h4', 'Error Loading Content') .
        html_writer::tag('p', $template_data['error_message']),
        'alert alert-danger'
    );
}

// Render the template
$renderer = $PAGE->get_renderer('aitoolsub_valuemapdoc');
echo $renderer->render_from_template('aitoolsub_valuemapdoc/my_content', $template_data);

echo $OUTPUT->footer();

/**
 * Get content preview (first 200 characters)
 */
function get_content_preview($content) {
    if (empty($content)) {
        return get_string('no_content', 'aitoolsub_valuemapdoc');
    }
    
    $plain_text = strip_tags($content);
    return strlen($plain_text) > 200 ? substr($plain_text, 0, 200) . '...' : $plain_text;
}

/**
 * Get relative time string
 */
function get_relative_time($timestamp) {
    $time_diff = time() - $timestamp;
    
    if ($time_diff < 60) {
        return get_string('just_now', 'aitoolsub_valuemapdoc');
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return get_string('minutes_ago', 'aitoolsub_valuemapdoc', $minutes);
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return get_string('hours_ago', 'aitoolsub_valuemapdoc', $hours);
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return get_string('days_ago', 'aitoolsub_valuemapdoc', $days);
    } else {
        return userdate($timestamp, get_string('strftimedatefullshort'));
    }
}