/* eslint-env es6 */
/**
 * Tabulator library wrapper for Cluster AI Tools
 * This uses the same Tabulator files as mod_valuemapdoc module
 * No need to duplicate files - we reference them directly
 */
define([], function() {
    // Return the global Tabulator object
    // The tabulator.min.js file is loaded from /mod/valuemapdoc/scripts/
    return window.Tabulator;
});