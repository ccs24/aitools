<?php
namespace local_aitools\plugininfo;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin info class for aitoolsub subplugins
 */
class aitoolsub extends \core\plugininfo\base {
    
    /**
     * Should there be a way to uninstall the plugin via the administration UI.
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }
    
    /**
     * Returns the information about plugin availability
     *
     * True means that the plugin is enabled. False means that the plugin is
     * disabled. Null means that the information is not available, or the
     * plugin does not support configurable availability or the availability
     * can not be changed.
     *
     * @return null|bool
     */
    public function is_enabled() {
        return true;
    }
    
    /**
     * Pre-uninstall hook.
     */
    public function uninstall_cleanup() {
        parent::uninstall_cleanup();
    }
}