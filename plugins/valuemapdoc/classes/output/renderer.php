<?php
namespace aitoolsub_valuemapdoc\output;

use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for aitoolsub_valuemapdoc
 */
class renderer extends plugin_renderer_base {
    
    /**
     * Render dashboard summary block
     */
    public function render_dashboard_summary($data) {
        return $this->render_from_template('aitoolsub_valuemapdoc/dashboard_summary', $data);
    }
    
    /**
     * Render quick stats block
     */
    public function render_quick_stats($data) {
        return $this->render_from_template('aitoolsub_valuemapdoc/quick_stats', $data);
    }
    
    /**
     * Render my content page
     */
    public function render_my_content($data) {
        return $this->render_from_template('aitoolsub_valuemapdoc/my_content', $data);
    }
}