<?php
require_once('../../config.php');

// Test czy klasy się ładują
use local_aitools\manager;

require_login();
require_capability('local/aitools:view', context_system::instance());

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/aitools/');
$PAGE->set_title('AI Tools Dashboard');
$PAGE->set_heading('AI Tools Dashboard');
$PAGE->set_pagelayout('standard');

// Test managera
try {
    $statistics = manager::get_statistics();
    $manager_working = true;
    $manager_error = null;
} catch (Exception $e) {
    $manager_working = false;
    $manager_error = $e->getMessage();
}

// Test subplugin support
try {
    $plugin_manager = core_plugin_manager::instance();
    $subplugins = $plugin_manager->get_subplugins_of_plugin('local_aitools');
    $subplugin_support = true;
    $subplugin_count = count($subplugins);
} catch (Exception $e) {
    $subplugin_support = false;
    $subplugin_error = $e->getMessage();
    $subplugin_count = 0;
}

echo $OUTPUT->header();

echo html_writer::start_div('container-fluid');
echo html_writer::tag('h2', '🤖 AI Tools Dashboard', ['class' => 'mb-4']);

// Status kroku 3
echo html_writer::start_div('alert alert-primary');
echo html_writer::tag('h4', '🔌 Step 3: Subplugins Support Added');
echo html_writer::tag('p', 'Testing subplugin detection and loading...');
echo html_writer::end_div();

echo html_writer::start_div('row');

// System status
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'System Status', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

if ($manager_working) {
    echo html_writer::tag('p', '✅ Manager class: Working');
    echo html_writer::tag('p', '✅ Interface: Loaded');
    echo html_writer::tag('p', '✅ Statistics: ' . json_encode($statistics));
} else {
    echo html_writer::tag('p', '❌ Manager class: Error');
    echo html_writer::tag('p', 'Error: ' . htmlspecialchars($manager_error ?? 'Unknown'));
}

// Subplugin support status
if ($subplugin_support) {
    echo html_writer::tag('p', '✅ Subplugin support: Working');
    echo html_writer::tag('p', '📦 Subplugins found: ' . $subplugin_count);
} else {
    echo html_writer::tag('p', '❌ Subplugin support: Error');
    echo html_writer::tag('p', 'Error: ' . htmlspecialchars($subplugin_error ?? 'Unknown'));
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Next steps
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Next: Step 4', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::tag('p', '✅ Step 1: Basic plugin installed');
echo html_writer::tag('p', '✅ Step 2: Manager classes');
echo html_writer::tag('p', ($subplugin_support ? '✅' : '❌') . ' Step 3: Subplugins support');
echo html_writer::tag('p', '⏳ Step 4: Install ValueMapDoc subplugin');
echo html_writer::tag('p', '⏳ Step 5: Add cohort management');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // row

// Detailed subplugin info
if ($manager_working && $subplugin_support) {
    echo html_writer::start_div('row mt-4');
    echo html_writer::start_div('col-12');
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-header');
    echo html_writer::tag('h5', 'Subplugin Detection Test', ['class' => 'mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    
    // Check plugins directory
    global $CFG;
    $plugins_dir = $CFG->dirroot . '/local/aitools/plugins';
    if (is_dir($plugins_dir)) {
        echo html_writer::tag('p', '✅ Plugins directory exists: ' . $plugins_dir);
        
        $dirs = scandir($plugins_dir);
        $found_dirs = [];
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($plugins_dir . '/' . $dir)) {
                $found_dirs[] = $dir;
            }
        }
        
        if (!empty($found_dirs)) {
            echo html_writer::tag('p', '📁 Found directories: ' . implode(', ', $found_dirs));
        } else {
            echo html_writer::tag('p', '📂 No subplugin directories found (this is normal for step 3)');
        }
    } else {
        echo html_writer::tag('p', '❌ Plugins directory missing: ' . $plugins_dir);
    }
    
    // Test manager methods
    $plugins = manager::get_plugins();
    echo html_writer::tag('p', '🔌 Loaded plugins: ' . count($plugins) . ' (expected: 0 until step 4)');
    
    if (!empty($plugins)) {
        foreach ($plugins as $name => $plugin) {
            echo html_writer::tag('p', '  - ' . $name);
        }
    }
    
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div(); // container

echo $OUTPUT->footer();