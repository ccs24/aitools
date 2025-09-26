<?php
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Debug information
error_log('Cluster index.php: Starting initialization');

// Check login and capabilities
require_login();

$context = context_system::instance();
require_capability('local/aitools:view', $context);

// Check if plugin class exists
if (!class_exists('\aitoolsub_cluster\plugin')) {
    throw new moodle_exception('errorloadingdata', 'aitoolsub_cluster', '', 'Plugin class not found');
}

// Check plugin access
$plugin = new \aitoolsub_cluster\plugin();
if (!$plugin->has_access()) {
    throw new moodle_exception('noaccess', 'aitoolsub_cluster');
}

error_log('Cluster index.php: Access checks passed');

// Page setup
$PAGE->set_context($context);
$PAGE->set_url('/local/aitools/plugins/cluster/index.php');
$PAGE->set_title(get_string('clustermanagement', 'aitoolsub_cluster'));
$PAGE->set_heading(get_string('clustermanagement', 'aitoolsub_cluster'));
$PAGE->set_pagelayout('standard');

// Navigation
$PAGE->navbar->add(get_string('aitools', 'local_aitools'), new moodle_url('/local/aitools/index.php'));
$PAGE->navbar->add(get_string('clustermanagement', 'aitoolsub_cluster'));

// Include required JavaScript and CSS
$PAGE->requires->js_call_amd('aitoolsub_cluster/cluster_manager', 'init');

// Add Tabulator CSS and JS from mod_valuemapdoc (same approach as valuemapdoc subplugin)
$PAGE->requires->css('/mod/valuemapdoc/styles/tabulator_bootstrap5.min.css');
$PAGE->requires->js('/mod/valuemapdoc/scripts/tabulator.min.js', true);

// Add our custom CSS for cluster management
$PAGE->requires->css('/local/aitools/plugins/cluster/styles/cluster.css');

// Add strings for JavaScript
$PAGE->requires->string_for_js('clustermanagement', 'aitoolsub_cluster');
$PAGE->requires->string_for_js('createcluster', 'aitoolsub_cluster');
$PAGE->requires->string_for_js('editcluster', 'aitoolsub_cluster');
$PAGE->requires->string_for_js('deletecluster', 'aitoolsub_cluster');

echo $OUTPUT->header();

?>

<div class="cluster-management-container">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fa fa-bullseye"></i> Sales Cluster Management</h2>
            <p class="text-muted">Manage strategic sales campaigns, companies, and contacts with AI-powered messaging.</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" id="create-cluster-btn">
                <i class="fa fa-plus"></i> Create New Cluster
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="total-clusters">-</h4>
                            <p class="card-text">Total Clusters</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-bullseye fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="active-clusters">-</h4>
                            <p class="card-text">Active Clusters</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-play fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="total-companies">-</h4>
                            <p class="card-text">Companies</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-building fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="total-persons">-</h4>
                            <p class="card-text">Contacts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa fa-filter"></i> Filters</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-select" id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="market-filter" class="form-label">Market</label>
                    <select class="form-select" id="market-filter">
                        <option value="">All Markets</option>
                        <!-- Options loaded via AJAX -->
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search-input" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search-input" placeholder="Search clusters...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="button" class="btn btn-outline-secondary" id="clear-filters-btn">
                            <i class="fa fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clusters Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-table"></i> My Clusters</h5>
            <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check" name="view-mode" id="table-view" checked>
                <label class="btn btn-outline-primary" for="table-view">
                    <i class="fa fa-table"></i> Table
                </label>
                <input type="radio" class="btn-check" name="view-mode" id="card-view">
                <label class="btn btn-outline-primary" for="card-view">
                    <i class="fa fa-th"></i> Cards
                </label>
            </div>
        </div>
        <div class="card-body">
            <!-- Table View -->
            <div id="clusters-table-container">
                <div id="clusters-table"></div>
            </div>
            
            <!-- Card View (initially hidden) -->
            <div id="clusters-cards-container" style="display: none;">
                <div class="row" id="clusters-cards"></div>
            </div>
            
            <!-- Loading State -->
            <div id="loading-state" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading clusters...</p>
            </div>
            
            <!-- Empty State -->
            <div id="empty-state" class="text-center py-5" style="display: none;">
                <i class="fa fa-bullseye fa-3x text-muted mb-3"></i>
                <h4>No Clusters Found</h4>
                <p class="text-muted">Create your first sales cluster to get started with strategic campaign management.</p>
                <button type="button" class="btn btn-primary" id="create-first-cluster-btn">
                    <i class="fa fa-plus"></i> Create Your First Cluster
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Cluster Modal -->
<div class="modal fade" id="cluster-modal" tabindex="-1" aria-labelledby="cluster-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cluster-modal-label">Create New Cluster</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cluster-form">
                <div class="modal-body">
                    <input type="hidden" id="cluster-id" name="cluster_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="cluster-name" class="form-label">Cluster Name *</label>
                                <input type="text" class="form-control" id="cluster-name" name="name" required 
                                       placeholder="e.g., Enterprise Software Q4 2025">
                                <div class="form-text">Choose a descriptive name for your sales campaign</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cluster-status" class="form-label">Status</label>
                                <select class="form-select" id="cluster-status" name="status">
                                    <option value="planning">Planning</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cluster-market" class="form-label">Market</label>
                        <div class="input-group">
                            <select class="form-select" id="cluster-market" name="market">
                                <option value="">Select or type custom market...</option>
                                <!-- Options loaded via AJAX from ValueMapDoc -->
                            </select>
                            <button class="btn btn-outline-secondary" type="button" id="refresh-markets-btn" 
                                    title="Refresh markets from ValueMapDoc">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            Markets are loaded from ValueMapDoc. You can also type a custom market.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cluster-description" class="form-label">Description</label>
                        <textarea class="form-control" id="cluster-description" name="description" rows="4"
                                  placeholder="Describe your sales strategy, target criteria, and campaign goals..."></textarea>
                        <div class="form-text">Optional: Add details about your campaign strategy and objectives</div>
                    </div>
                    
                    <!-- AI Suggestions (if editing existing cluster) -->
                    <div id="ai-suggestions" class="alert alert-info" style="display: none;">
                        <h6><i class="fa fa-lightbulb-o"></i> AI Suggestions</h6>
                        <div id="ai-suggestions-content"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="save-cluster-btn">
                        <i class="fa fa-save"></i> Save Cluster
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cluster Details Modal -->
<div class="modal fade" id="cluster-details-modal" tabindex="-1" aria-labelledby="cluster-details-label" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cluster-details-label">Cluster Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cluster-details-content">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" id="manage-companies-btn">
                        <i class="fa fa-building"></i> Manage Companies
                    </button>
                    <button type="button" class="btn btn-info" id="manage-messages-btn">
                        <i class="fa fa-comments"></i> Messages
                    </button>
                    <button type="button" class="btn btn-success" id="convert-to-opportunity-btn">
                        <i class="fa fa-arrow-right"></i> Convert to Opportunity
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="delete-cluster-modal" tabindex="-1" aria-labelledby="delete-cluster-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="delete-cluster-label">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this cluster?</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will also delete all associated companies, contacts, and messages. 
                    This action cannot be undone.
                </div>
                <p><strong>Cluster:</strong> <span id="delete-cluster-name"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="fa fa-trash"></i> Delete Cluster
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Pass PHP data to JavaScript
window.ClusterConfig = {
    wwwroot: '<?php echo $CFG->wwwroot; ?>',
    sesskey: '<?php echo sesskey(); ?>',
    userid: <?php echo $USER->id; ?>,
    contextid: <?php echo $context->id; ?>
};
</script>

<?php
echo $OUTPUT->footer();