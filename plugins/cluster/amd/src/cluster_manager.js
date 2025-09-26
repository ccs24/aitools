/**
 * Cluster Management JavaScript
 * 
 * Handles cluster CRUD operations, filtering, and UI interactions
 * Uses Tabulator.js from mod_valuemapdoc module
 */
define([
    'aitoolsub_cluster/tabulatorlib',
    'jquery', 
    'core/ajax', 
    'core/notification'
], function(
    Tabulator,
    $, 
    Ajax, 
    Notification
) {
    
    var ClusterManager = {
        
        // Configuration
        config: {
            contextid: 0,
            wwwroot: '',
            sesskey: '',
            userid: 0
        },
        
        // Data tables
        clustersTable: null,
        
        // Data cache
        valuemapData: {},
        currentCluster: null,
        
        /**
         * Initialize the cluster manager
         */
        init: function() {
            console.log('ClusterManager: Initializing...');
            
            // Get configuration from window object
            if (window.ClusterConfig) {
                this.config = window.ClusterConfig;
            }
            
            // Initialize components
            this.initEventHandlers();
            this.loadValueMapData();
            this.initClustersTable();
            this.loadStats();
            
            console.log('ClusterManager: Initialized successfully');
        },
        
        /**
         * Initialize event handlers
         */
        initEventHandlers: function() {
            var self = this;
            
            // Create cluster button
            $('#create-cluster-btn, #create-first-cluster-btn').on('click', function() {
                self.showCreateClusterModal();
            });
            
            // Filter controls
            $('#status-filter, #market-filter').on('change', function() {
                self.applyFilters();
            });
            
            $('#search-input').on('input', this.debounce(function() {
                self.applyFilters();
            }, 500));
            
            // Clear filters
            $('#clear-filters-btn').on('click', function() {
                self.clearFilters();
            });
            
            // View mode toggle
            $('input[name="view-mode"]').on('change', function() {
                self.toggleViewMode($(this).attr('id'));
            });
            
            // Cluster form submission
            $('#cluster-form').on('submit', function(e) {
                e.preventDefault();
                self.saveCluster();
            });
            
            // Refresh markets button
            $('#refresh-markets-btn').on('click', function() {
                self.loadValueMapData(true);
            });
        },
        
        /**
         * Load ValueMapDoc data for dropdowns
         */
        loadValueMapData: function(forceRefresh) {
            var self = this;
            
            if (!forceRefresh && Object.keys(this.valuemapData).length > 0) {
                this.populateDropdowns();
                return;
            }
            
            console.log('ClusterManager: Loading ValueMapDoc data...');
            
            var request = {
                methodname: 'aitoolsub_cluster_get_valuemap_data',
                args: {
                    fields: ['market', 'industry', 'role'],
                    search: '',
                    limit: 100
                }
            };
            
            Ajax.call([request])[0]
                .done(function(response) {
                    console.log('ClusterManager: ValueMapDoc data loaded', response);
                    
                    // Check for error in response
                    if (response.error) {
                        console.warn('ClusterManager: ValueMapDoc error:', response.error);
                        Notification.addNotification({
                            message: 'ValueMapDoc warning: ' + response.error,
                            type: 'warning'
                        });
                        // Continue with empty data
                        self.valuemapData = { fields: {}, context_data: [], statistics: {} };
                    } else {
                        self.valuemapData = response;
                    }
                    
                    self.populateDropdowns();
                })
                .fail(function(error) {
                    console.error('ClusterManager: Error loading ValueMapDoc data', error);
                    
                    // Set empty data to prevent further errors
                    self.valuemapData = { fields: {}, context_data: [], statistics: {} };
                    self.populateDropdowns();
                    
                    // Show user-friendly error
                    Notification.addNotification({
                        message: 'Could not load market data. You can still type custom values.',
                        type: 'warning'
                    });
                });
        },
        
        /**
         * Populate dropdown lists with ValueMapDoc data
         */
        populateDropdowns: function() {
            console.log('ClusterManager: Populating dropdowns...');
            
            // Market filter dropdown
            var marketFilter = $('#market-filter');
            marketFilter.empty().append('<option value="">All Markets</option>');
            
            // Cluster modal market dropdown
            var clusterMarket = $('#cluster-market');
            clusterMarket.empty().append('<option value="">Select or type custom market...</option>');
            
            if (this.valuemapData.fields && this.valuemapData.fields.market) {
                this.valuemapData.fields.market.forEach(function(item) {
                    var option = $('<option></option>')
                        .attr('value', item.value)
                        .text(item.display);
                    
                    marketFilter.append(option.clone());
                    clusterMarket.append(option.clone());
                });
            }
            
            // Make cluster market dropdown editable
            this.makeSelectEditable('#cluster-market');
        },
        
        /**
         * Make select dropdown editable (allow custom values)
         */
        makeSelectEditable: function(selector) {
            var $select = $(selector);
            var $wrapper = $('<div class="editable-select-wrapper"></div>');
            var $input = $('<input type="text" class="form-control editable-select-input">');
            
            $select.wrap($wrapper);
            $select.after($input);
            
            // Show input when "other" is needed
            $select.on('change', function() {
                if ($(this).val() === '' || $(this).find('option:selected').length === 0) {
                    $input.show().focus();
                    $select.hide();
                }
            });
            
            // Show select when input loses focus
            $input.on('blur', function() {
                if ($(this).val().trim() === '') {
                    $input.hide();
                    $select.show();
                } else {
                    // Add custom option if not exists
                    var customValue = $(this).val().trim();
                    if ($select.find('option[value="' + customValue + '"]').length === 0) {
                        $select.append($('<option></option>')
                            .attr('value', customValue)
                            .text(customValue + ' (custom)'));
                    }
                    $select.val(customValue);
                }
            });
            
            $input.hide();
        },
        
        /**
         * Initialize clusters table with Tabulator
         */
        initClustersTable: function() {
            var self = this;
            
            console.log('ClusterManager: Initializing clusters table...');
            
            if (typeof Tabulator === 'undefined') {
                console.error('ClusterManager: Tabulator not loaded, using simple table');
                this.loadClustersSimple();
                return;
            }
            
            this.clustersTable = new Tabulator("#clusters-table", {
                height: 500,
                layout: "fitColumns",
                placeholder: "No clusters found",
                pagination: "local",
                paginationSize: 25,
                paginationSizeSelector: [10, 25, 50, 100],
                movableColumns: true,
                resizableRows: true,
                responsiveLayout: "hide",
                columns: [
                    {
                        title: "Name", 
                        field: "name", 
                        width: 200,
                        headerFilter: "input",
                        formatter: function(cell) {
                            var data = cell.getData();
                            var html = '<strong>' + data.name + '</strong>';
                            if (data.description) {
                                html += '<br><small class="text-muted">' + 
                                       data.description.substring(0, 60) + 
                                       (data.description.length > 60 ? '...' : '') + '</small>';
                            }
                            return html;
                        }
                    },
                    {
                        title: "Market", 
                        field: "market", 
                        width: 150,
                        headerFilter: "select",
                        headerFilterParams: {
                            values: true
                        }
                    },
                    {
                        title: "Status", 
                        field: "status", 
                        width: 120,
                        headerFilter: "select",
                        headerFilterParams: {
                            "": "All",
                            "planning": "Planning",
                            "active": "Active", 
                            "paused": "Paused",
                            "completed": "Completed"
                        },
                        formatter: function(cell) {
                            var status = cell.getValue();
                            var badges = {
                                'planning': 'secondary',
                                'active': 'success',
                                'paused': 'warning',
                                'completed': 'info'
                            };
                            return '<span class="badge bg-' + badges[status] + '">' + 
                                   status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
                        }
                    },
                    {
                        title: "Companies", 
                        field: "company_count", 
                        width: 100,
                        hozAlign: "center",
                        formatter: function(cell) {
                            return '<span class="badge bg-info">' + cell.getValue() + '</span>';
                        }
                    },
                    {
                        title: "Contacts", 
                        field: "person_count", 
                        width: 100,
                        hozAlign: "center",
                        formatter: function(cell) {
                            return '<span class="badge bg-warning">' + cell.getValue() + '</span>';
                        }
                    },
                    {
                        title: "Messages", 
                        field: "message_count", 
                        width: 100,
                        hozAlign: "center",
                        formatter: function(cell) {
                            return '<span class="badge bg-primary">' + cell.getValue() + '</span>';
                        }
                    },
                    {
                        title: "Modified", 
                        field: "modified_date", 
                        width: 120,
                        formatter: function(cell) {
                            return self.formatDate(cell.getValue());
                        }
                    },
                    {
                        title: "Actions", 
                        field: "actions", 
                        width: 200,
                        hozAlign: "center",
                        headerSort: false,
                        formatter: function(cell) {
                            var data = cell.getData();
                            return self.renderActionButtons(data);
                        }
                    }
                ],
                rowClick: function(e, row) {
                    self.viewCluster(row.getData().id);
                }
            });
            
            // Load data
            this.loadClusters();
        },
        
        /**
         * Render action buttons for table row
         */
        renderActionButtons: function(data) {
            var html = '<div class="btn-group btn-group-sm" role="group">';
            
            html += '<button type="button" class="btn btn-outline-primary btn-sm" ' +
                   'onclick="ClusterManager.viewCluster(' + data.id + ')" title="View Details">' +
                   '<i class="fa fa-eye"></i></button>';
            
            if (data.access_level === 'manage' || data.is_owner) {
                html += '<button type="button" class="btn btn-outline-secondary btn-sm" ' +
                       'onclick="ClusterManager.editCluster(' + data.id + ')" title="Edit">' +
                       '<i class="fa fa-edit"></i></button>';
                
                html += '<button type="button" class="btn btn-outline-danger btn-sm" ' +
                       'onclick="ClusterManager.deleteCluster(' + data.id + ', \'' + 
                       data.name.replace(/'/g, "\\'") + '\')" title="Delete">' +
                       '<i class="fa fa-trash"></i></button>';
            }
            
            html += '</div>';
            return html;
        },
        
        /**
         * Load clusters data
         */
        loadClusters: function() {
            var self = this;
            
            console.log('ClusterManager: Loading clusters...');
            $('#loading-state').show();
            $('#empty-state').hide();
            
            var filters = this.getCurrentFilters();
            
            var request = {
                methodname: 'aitoolsub_cluster_get_clusters',
                args: {
                    filters: filters
                }
            };
            
            Ajax.call([request])[0]
                .done(function(response) {
                    console.log('ClusterManager: Clusters loaded', response);
                    
                    $('#loading-state').hide();
                    
                    if (response.clusters.length === 0) {
                        $('#empty-state').show();
                        if (self.clustersTable) {
                            self.clustersTable.clearData();
                        }
                    } else {
                        $('#empty-state').hide();
                        if (self.clustersTable) {
                            self.clustersTable.setData(response.clusters);
                        } else {
                            self.renderClustersSimple(response.clusters);
                        }
                    }
                    
                    // Update pagination info
                    self.updatePaginationInfo(response.pagination);
                })
                .fail(function(error) {
                    console.error('ClusterManager: Error loading clusters', error);
                    $('#loading-state').hide();
                    Notification.addNotification({
                        message: 'Error loading clusters: ' + error.message,
                        type: 'error'
                    });
                });
        },
        
        /**
         * Load simple clusters without Tabulator
         */
        loadClustersSimple: function() {
            console.log('ClusterManager: Loading clusters (simple mode)...');
            this.loadClusters();
        },
        
        /**
         * Render clusters in simple table format
         */
        renderClustersSimple: function(clusters) {
            var self = this;
            var html = '<div class="table-responsive">';
            html += '<table class="table table-striped table-hover">';
            html += '<thead><tr>';
            html += '<th>Name</th><th>Market</th><th>Status</th>';
            html += '<th>Companies</th><th>Contacts</th><th>Messages</th>';
            html += '<th>Modified</th><th>Actions</th>';
            html += '</tr></thead><tbody>';
            
            clusters.forEach(function(cluster) {
                html += '<tr>';
                html += '<td><strong>' + cluster.name + '</strong>';
                if (cluster.description) {
                    html += '<br><small class="text-muted">' + 
                           cluster.description.substring(0, 60) + 
                           (cluster.description.length > 60 ? '...' : '') + '</small>';
                }
                html += '</td>';
                html += '<td>' + (cluster.market || '-') + '</td>';
                html += '<td><span class="badge bg-' + self.getStatusBadgeClass(cluster.status) + '">' + 
                       cluster.status.charAt(0).toUpperCase() + cluster.status.slice(1) + '</span></td>';
                html += '<td><span class="badge bg-info">' + cluster.company_count + '</span></td>';
                html += '<td><span class="badge bg-warning">' + cluster.person_count + '</span></td>';
                html += '<td><span class="badge bg-primary">' + cluster.message_count + '</span></td>';
                html += '<td>' + self.formatDate(cluster.modified_date) + '</td>';
                html += '<td>' + self.renderActionButtons(cluster) + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            $('#clusters-table-container').html(html);
        },
        
        /**
         * Get current filter values
         */
        getCurrentFilters: function() {
            return {
                status: $('#status-filter').val(),
                market: $('#market-filter').val(),
                search: $('#search-input').val().trim(),
                limit: 50,
                offset: 0
            };
        },
        
        /**
         * Apply filters to clusters table
         */
        applyFilters: function() {
            console.log('ClusterManager: Applying filters...');
            this.loadClusters();
        },
        
        /**
         * Clear all filters
         */
        clearFilters: function() {
            console.log('ClusterManager: Clearing filters...');
            $('#status-filter').val('');
            $('#market-filter').val('');
            $('#search-input').val('');
            this.loadClusters();
        },
        
        /**
         * Toggle between table and card view
         */
        toggleViewMode: function(mode) {
            console.log('ClusterManager: Switching to ' + mode);
            
            if (mode === 'card-view') {
                $('#clusters-table-container').hide();
                $('#clusters-cards-container').show();
                this.loadClustersCards();
            } else {
                $('#clusters-cards-container').hide();
                $('#clusters-table-container').show();
            }
        },
        
        /**
         * Load clusters in card view
         */
        loadClustersCards: function() {
            console.log('ClusterManager: Loading card view...');
            $('#clusters-cards').html('<p class="text-center">Card view coming soon...</p>');
        },
        
        /**
         * Show create cluster modal
         */
        showCreateClusterModal: function() {
            console.log('ClusterManager: Showing create cluster modal...');
            
            this.currentCluster = null;
            $('#cluster-modal-label').text('Create New Cluster');
            $('#cluster-form')[0].reset();
            $('#cluster-id').val('');
            $('#save-cluster-btn').html('<i class="fa fa-save"></i> Save Cluster');
            
            var modal = new bootstrap.Modal(document.getElementById('cluster-modal'));
            modal.show();
        },
        
        /**
         * Show edit cluster modal
         */
        editCluster: function(clusterId) {
            console.log('ClusterManager: Editing cluster ' + clusterId);
            
            var self = this;
            
            // Get cluster data
            var request = {
                methodname: 'aitoolsub_cluster_get_clusters',
                args: {
                    filters: { cluster_id: clusterId }
                }
            };
            
            Ajax.call([request])[0]
                .done(function(response) {
                    if (response.clusters.length > 0) {
                        var cluster = response.clusters[0];
                        self.currentCluster = cluster;
                        
                        // Populate form
                        $('#cluster-modal-label').text('Edit Cluster');
                        $('#cluster-id').val(cluster.id);
                        $('#cluster-name').val(cluster.name);
                        $('#cluster-market').val(cluster.market);
                        $('#cluster-description').val(cluster.description);
                        $('#cluster-status').val(cluster.status);
                        $('#save-cluster-btn').html('<i class="fa fa-save"></i> Update Cluster');
                        
                        var modal = new bootstrap.Modal(document.getElementById('cluster-modal'));
                        modal.show();
                    }
                })
                .fail(function(error) {
                    console.error('ClusterManager: Error loading cluster for edit', error);
                    Notification.addNotification({
                        message: 'Error loading cluster data: ' + error.message,
                        type: 'error'
                    });
                });
        },
        
        /**
         * Save cluster (create or update)
         */
        saveCluster: function() {
            console.log('ClusterManager: Saving cluster...');
            
            var self = this;
            var formData = this.getFormData('#cluster-form');
            var isEdit = $('#cluster-id').val() !== '';
            
            // Validate required fields
            if (!formData.name.trim()) {
                Notification.addNotification({
                    message: 'Cluster name is required',
                    type: 'error'
                });
                return;
            }
            
            var methodname = isEdit ? 'aitoolsub_cluster_update_cluster' : 'aitoolsub_cluster_create_cluster';
            var args = {
                name: formData.name.trim(),
                market: formData.market.trim(),
                description: formData.description.trim(),
                status: formData.status
            };
            
            if (isEdit) {
                args.cluster_id = parseInt(formData.cluster_id);
            }
            
            $('#save-cluster-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            
            var request = {
                methodname: methodname,
                args: args
            };
            
            Ajax.call([request])[0]
                .done(function(response) {
                    console.log('ClusterManager: Cluster saved', response);
                    
                    Notification.addNotification({
                        message: response.message,
                        type: 'success'
                    });
                    
                    // Close modal and refresh data
                    bootstrap.Modal.getInstance(document.getElementById('cluster-modal')).hide();
                    self.loadClusters();
                    self.loadStats();
                })
                .fail(function(error) {
                    console.error('ClusterManager: Error saving cluster', error);
                    Notification.addNotification({
                        message: 'Error saving cluster: ' + error.message,
                        type: 'error'
                    });
                })
                .always(function() {
                    $('#save-cluster-btn').prop('disabled', false).html('<i class="fa fa-save"></i> Save Cluster');
                });
        },
        
        /**
         * View cluster details
         */
        viewCluster: function(clusterId) {
            console.log('ClusterManager: Viewing cluster ' + clusterId);
            
            // Navigate to cluster details page
            window.location.href = this.config.wwwroot + '/local/aitools/plugins/cluster/view.php?id=' + clusterId;
        },
        
        /**
         * Delete cluster
         */
        deleteCluster: function(clusterId, clusterName) {
            console.log('ClusterManager: Deleting cluster ' + clusterId);
            
            var self = this;
            
            // Show confirmation modal
            $('#delete-cluster-name').text(clusterName);
            
            var modal = new bootstrap.Modal(document.getElementById('delete-cluster-modal'));
            modal.show();
            
            // Handle confirmation
            $('#confirm-delete-btn').off('click').on('click', function() {
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deleting...');
                
                var request = {
                    methodname: 'aitoolsub_cluster_delete_cluster',
                    args: {
                        cluster_id: clusterId
                    }
                };
                
                Ajax.call([request])[0]
                    .done(function(response) {
                        console.log('ClusterManager: Cluster deleted', response);
                        
                        Notification.addNotification({
                            message: response.message,
                            type: 'success'
                        });
                        
                        modal.hide();
                        self.loadClusters();
                        self.loadStats();
                    })
                    .fail(function(error) {
                        console.error('ClusterManager: Error deleting cluster', error);
                        Notification.addNotification({
                            message: 'Error deleting cluster: ' + error.message,
                            type: 'error'
                        });
                    })
                    .always(function() {
                        $('#confirm-delete-btn').prop('disabled', false).html('<i class="fa fa-trash"></i> Delete Cluster');
                    });
            });
        },
        
        /**
         * Load dashboard statistics
         */
        loadStats: function() {
            var self = this;
            
            console.log('ClusterManager: Loading stats...');
            
            var request = {
                methodname: 'aitoolsub_cluster_get_clusters',
                args: {
                    filters: { summary_only: true }
                }
            };
            
            Ajax.call([request])[0]
                .done(function(response) {
                    console.log('ClusterManager: Stats loaded', response);
                    
                    // Update stats cards
                    var totalClusters = response.clusters.length;
                    var activeClusters = response.clusters.filter(function(c) { 
                        return c.status === 'active'; 
                    }).length;
                    var totalCompanies = response.clusters.reduce(function(sum, c) { 
                        return sum + c.company_count; 
                    }, 0);
                    var totalPersons = response.clusters.reduce(function(sum, c) { 
                        return sum + c.person_count; 
                    }, 0);
                    
                    $('#total-clusters').text(totalClusters);
                    $('#active-clusters').text(activeClusters);
                    $('#total-companies').text(totalCompanies);
                    $('#total-persons').text(totalPersons);
                })
                .fail(function(error) {
                    console.error('ClusterManager: Error loading stats', error);
                });
        },
        
        /**
         * Update pagination information
         */
        updatePaginationInfo: function(pagination) {
            console.log('ClusterManager: Pagination info', pagination);
            // Implementation for pagination info updates can be added here
        },
        
        /**
         * Get form data as object
         */
        getFormData: function(formSelector) {
            var formData = {};
            $(formSelector).serializeArray().forEach(function(field) {
                formData[field.name] = field.value;
            });
            return formData;
        },
        
        /**
         * Format timestamp to readable date
         */
        formatDate: function(timestamp) {
            if (!timestamp) return '-';
            
            var date = new Date(timestamp * 1000);
            var now = new Date();
            var diffTime = now - date;
            var diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                return 'Today';
            } else if (diffDays === 1) {
                return 'Yesterday';
            } else if (diffDays < 7) {
                return diffDays + 'd ago';
            } else {
                return date.toLocaleDateString();
            }
        },
        
        /**
         * Get badge class for status
         */
        getStatusBadgeClass: function(status) {
            var classes = {
                'planning': 'secondary',
                'active': 'success',
                'paused': 'warning',
                'completed': 'info'
            };
            return classes[status] || 'secondary';
        },
        
        /**
         * Debounce function for search input
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Expose ClusterManager globally for button onclick handlers
    window.ClusterManager = ClusterManager;
    
    return {
        init: ClusterManager.init.bind(ClusterManager)
    };
});