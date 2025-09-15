<?php
namespace local_aitools\output;

use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for local_aitools
 */
class renderer extends plugin_renderer_base {
    
    /**
     * Render the main AI Tools dashboard
     */
    public function render_dashboard($data) {
        return $this->render_from_template('local_aitools/dashboard', $data);
    }
    
    /**
     * Render a dashboard block
     */
    public function render_dashboard_block($block_data) {
        $template = $block_data['template'] ?? 'local_aitools/default_block';
        return $this->render_from_template($template, $block_data);
    }
    
    /**
     * Render tools grid
     */
    public function render_tools_grid($tools_data) {
        return $this->render_from_template('local_aitools/tools_grid', $tools_data);
    }
}