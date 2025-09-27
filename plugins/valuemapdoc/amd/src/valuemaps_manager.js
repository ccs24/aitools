/**
 * ValueMaps Manager for AI Tools Subplugin
 * 
 * Manages the value maps table interface with Tabulator (similar to module but with groups)
 * 
 * @module aitoolsub_valuemapdoc/valuemaps_manager
 * @copyright 2024 Your Organization
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    
    var table = null;
    var config = window.valuemapsConfig || {};
    var allData = [];
    var username = M.cfg.fullname || M.cfg.username;

    return {
        /**
         * Initialize the valuemaps manager
         */
        init: function() {
            console.log('[valuemaps_manager] Module loaded');
            
            // Get columns from element (same pattern as module)
            var columnsElement = document.querySelector('#valuemap-columns');
            if (!columnsElement || !columnsElement.textContent) {
                console.warn('[valuemaps_manager] Columns not found');
                return;
            }

            var columns;
            try {
                columns = JSON.parse(columnsElement.textContent);
                console.log('[valuemaps_manager] Parsed columns:', columns);
            } catch (e) {
                console.error('[valuemaps_manager] Error parsing columns:', e);
                return;
            }

            if (!Array.isArray(columns)) {
                console.error('[valuemaps_manager] Columns is not an array');
                return;
            }

            // Wait for Tabulator to be available
            this.waitForTabulator(columns);
        },

        /**
         * Wait for Tabulator library to be loaded
         */
        waitForTabulator: function(columns) {
            var self = this;
            
            if (typeof window.Tabulator !== 'undefined') {
                console.log('[valuemaps_manager] Tabulator available, initializing...');
                this.bindEvents();
                this.loadData(columns);
            } else {
                console.log('[valuemaps_manager] Waiting for Tabulator...');
                setTimeout(function() {
                    self.waitForTabulator(columns);
                }, 100);
            }
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Refresh data
            $('#refresh-data').on('click', function() {
                self.init();
            });
            
            // Export data
            $('#export-data').on('click', function() {
                self.showExportOptions();
            });
            
            // Clear filters
            $('#clear-filters').on('click', function() {
                self.clearFilters();
            });
            
            // Filter controls
            $('#filter-course, #filter-activity, #filter-type').on('change', function() {
                self.applyFilters();
            });
            
            $('#search-entries').on('input', function() {
                self.applyFilters();
            });
            
            // Retry loading
            $('#retry-loading').on('click', function() {
                self.init();
            });
            
            // Fullscreen toggle
            $('#toggle-fullscreen').on('click', function() {
                self.toggleFullscreen();
            });
        },

        /**
         * Load data from server
         */
        loadData: function(columns) {
            var self = this;
            this.showLoadingState();
            
            Ajax.call([{
                methodname: 'aitoolsub_valuemapdoc_get_all_entries_global',
                args: {
                    userid: config.userid || 0
                }
            }])[0].done(function(data) {
                allData = data.entries || [];
                
                console.log('[valuemaps_manager] Entries loaded:', allData, 'records');
                
                // Update statistics
                self.updateStatistics(data.statistics);
                
                // Populate filter options
                self.populateFilterOptions(data);
                
                // Process data with group separators
                var processedData = self.processDataWithSeparators(allData);
                
                // Initialize or update table
                if (processedData.length > 0) {
                    self.initializeTable(columns, processedData);
                    self.showTableState();
                } else {
                    self.showEmptyState();
                }
                
            }).fail(function(error) {
                console.error('[valuemaps_manager] Failed to load entries:', error);
                self.showErrorState(error.message || 'Unknown error occurred');
                Notification.exception(error);
            });
        },

        /**
         * Process data and add group separators
         */
        processDataWithSeparators: function(entries) {
            if (entries.length === 0) {
                return [];
            }

            var processedData = [];
            var currentGroup = null;

            // Data is already sorted by course_name, activity_name, timemodified
            entries.forEach(function(entry) {
                var groupKey = entry.course_name + ' → ' + entry.activity_name;
                
                // Add separator row when group changes
                if (currentGroup !== groupKey) {
                    processedData.push({
                        id: 'separator_' + processedData.length,
                        isSeparator: true,
                        groupTitle: groupKey,
                        course_name: entry.course_name,
                        activity_name: entry.activity_name
                    });
                    currentGroup = groupKey;
                }
                
                // Add actual entry
                processedData.push(entry);
            });

            return processedData;
        },

        /**
         * Update statistics display
         */
        updateStatistics: function(stats) {
            $('#stat-total-entries').text(stats.total_entries || 0);
            $('#stat-unique-courses').text(stats.unique_courses || 0);
            $('#stat-unique-activities').text(stats.unique_activities || 0);
        },

        /**
         * Populate filter dropdowns
         */
        populateFilterOptions: function(data) {
            var stats = data.statistics || {};
            
            // Courses
            var courseSelect = $('#filter-course');
            courseSelect.find('option:not(:first)').remove();
            if (stats.courses_list) {
                stats.courses_list.forEach(function(course) {
                    courseSelect.append('<option value="' + course + '">' + course + '</option>');
                });
            }
            
            // Activities
            var activitySelect = $('#filter-activity');
            activitySelect.find('option:not(:first)').remove();
            if (stats.activities_list) {
                stats.activities_list.forEach(function(activity) {
                    activitySelect.append('<option value="' + activity + '">' + activity + '</option>');
                });
            }
        },

        /**
         * Get username from response data for current user
         */
        getUsernameFromResponse: function(response) {
            var currentUserId = M.cfg.userid;
            var userEntry = response.find(function(entry) {
                return entry.userid == currentUserId;
            });
            
            if (userEntry && userEntry.username) {
                return userEntry.username;
            }
            
            return M.cfg.fullname || M.cfg.username || 'Ja';
        },

        /**
         * Prepare columns like in module (adapted for subplugin)
         */
        prepareColumns: function(columns) {
            var self = this;
            var enhancedColumns = [];
            
            // Add mapped columns from user's field level
            columns.forEach(function(col) {
                enhancedColumns.push({
                    title: col.title,
                    field: col.field,
                    hozAlign: col.hozAlign || 'left',
                    headerSort: col.headerSort !== false,
                    width: col.width || 150,
                    headerFilter: "input",
                    editable: false, // View-only in subplugin
                    formatter: function(cell) {
                        var row = cell.getRow();
                        if (row.getData().isSeparator) {
                            return ''; // Empty for separator rows
                        }
                        
                        var value = cell.getValue();
                        if (value && value.length > 50) {
                            return '<div class="text-truncate" style="max-width: ' + (col.width - 20) + 'px;" title="' + 
                                   value + '">' + value + '</div>';
                        }
                        return value || '';
                    }
                });
            });
            
            // Add username column (like in module)
            enhancedColumns.push({
                title: "Author",
                field: "username", 
                hozAlign: "left",
                headerSort: true,
                width: 120,
                headerFilter: "input",
                editable: false,
                formatter: function(cell) {
                    var row = cell.getRow();
                    if (row.getData().isSeparator) {
                        return '';
                    }
                    
                    var value = cell.getValue();
                    var data = row.getData();
                    if (data.ismaster === 1) {
                        return '<i class="fa fa-star text-warning" title="Master entry"></i> ' + value;
                    }
                    return value;
                }
            });

            // Add actions column
            enhancedColumns.push({
                title: "Actions",
                field: "actions",
                width: 120,
                headerSort: false,
                formatter: function(cell) {
                    var row = cell.getRow();
                    var data = row.getData();
                    
                    if (data.isSeparator) {
                        return '';
                    }
                    
                    return '<div class="btn-group btn-group-sm" role="group">' +
                           '<a href="' + data.view_url + '" class="btn btn-outline-primary btn-sm" title="View Entry">' +
                           '<i class="fa fa-eye"></i></a>' +
                           '<a href="' + data.edit_url + '" class="btn btn-outline-secondary btn-sm" title="Edit in Activity" target="_blank">' +
                           '<i class="fa fa-edit"></i></a>' +
                           '</div>';
                }
            });

            return enhancedColumns;
        },

        /**
         * Initialize Tabulator table
         */
        initializeTable: function(columns, processedData) {
            var self = this;
            
            var enhancedColumns = this.prepareColumns(columns);
            var currentUsername = this.getUsernameFromResponse(allData);

            // Initialize table (similar to module)
            table = new window.Tabulator("#valuemaps-table", {
                data: processedData,
                columns: enhancedColumns,
                layout: "fitDataTable",
                height: "600px",
                pagination: "local",
                paginationSize: 20,
                paginationSizeSelector: [10, 20, 50, 100],
                movableColumns: true,
                resizableRows: false,
                selectable: false, // No selection in subplugin
                tooltips: true,
                placeholder: "No entries to display",
                persistence: {
                    sort: true,
                    filter: true,
                    columns: true,
                    page: true
                },
                persistenceID: "ai-tools-valuemaps-table",
                locale: true,
                langs: {
                    "default": {
                        "pagination": {
                            "page_size": "Page Size",
                            "first": "First",
                            "prev": "Prev", 
                            "next": "Next",
                            "last": "Last"
                        },
                        "headerFilters": {
                            "default": "Filter column..."
                        }
                    }
                },
                rowFormatter: function(row) {
                    var data = row.getData();
                    
                    // Style separator rows
                    if (data.isSeparator) {
                        row.getElement().style.backgroundColor = '#f8f9fa';
                        row.getElement().style.fontWeight = 'bold';
                        row.getElement().style.borderTop = '2px solid #dee2e6';
                        
                        // Set content for separator row
                        var cells = row.getCells();
                        if (cells.length > 0) {
                            var firstCell = cells[0];
                            firstCell.getElement().colSpan = cells.length;
                            firstCell.getElement().innerHTML = '<i class="fa fa-folder-open me-2"></i>' + data.groupTitle;
                            
                            // Hide other cells in separator row
                            for (var i = 1; i < cells.length; i++) {
                                cells[i].getElement().style.display = 'none';
                            }
                        }
                        return;
                    }
                    
                    // Style master entries
                    if (data.ismaster === 1) {
                        row.getElement().style.backgroundColor = '#eaffea';
                        row.getElement().classList.add('ismaster');
                    }
                }
            });

            // Table events
            table.on("tableBuilt", function() {
                console.log('[valuemaps_manager] Table built successfully');
                
                // Set default filter to show only current user's entries (like module)
                table.setHeaderFilterValue("username", currentUsername);
                
                // Add user filter toggle
                self.addUserFilterToggle(table, currentUsername);
            });

            // Double click to edit (like module)
            table.on("rowDblClick", function(e, row) {
                var data = row.getData();
                if (!data.isSeparator) {
                    window.open(data.edit_url, '_blank');
                }
            });

            // Make manager globally available for button callbacks
            window.valuemapsManager = this;
        },

        /**
         * Add user filter toggle button (like in module)
         */
        addUserFilterToggle: function(table, currentUsername) {
            var toolbar = $('.card-header .table-controls');
            if (toolbar.length === 0) {
                return;
            }

            toolbar.prepend('<button type="button" class="btn btn-sm btn-outline-info ms-2" id="user-filter-toggle">' +
                           '<i class="fa fa-user"></i> My Entries</button>');
            
            var showingOnlyMine = true;
            $('#user-filter-toggle').on('click', function() {
                var $btn = $(this);
                if (showingOnlyMine) {
                    // Show all entries
                    table.clearHeaderFilter("username");
                    $btn.html('<i class="fa fa-users"></i> All Entries');
                    $btn.removeClass('btn-outline-info').addClass('btn-outline-secondary');
                    showingOnlyMine = false;
                } else {
                    // Filter to user entries only
                    table.setHeaderFilterValue("username", currentUsername);
                    $btn.html('<i class="fa fa-user"></i> My Entries');
                    $btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
                    showingOnlyMine = true;
                }
            });
        },

        /**
         * Apply filters from form controls
         */
        applyFilters: function() {
            if (!table) {
                return;
            }
            
            var courseFilter = $('#filter-course').val();
            var activityFilter = $('#filter-activity').val();
            var typeFilter = $('#filter-type').val();
            var searchFilter = $('#search-entries').val();

            // Clear existing filters
            table.clearFilter();

            // Apply filters (skip separator rows)
            var filters = [];
            
            if (courseFilter) {
                filters.push({field: "course_name", type: "=", value: courseFilter});
            }
            if (activityFilter) {
                filters.push({field: "activity_name", type: "=", value: activityFilter});
            }
            
            if (searchFilter) {
                filters.push([
                    {field: "course_name", type: "like", value: searchFilter},
                    {field: "activity_name", type: "like", value: searchFilter}
                ]);
            }

            // Add filter to exclude separator rows from normal filtering
            filters.push(function(data) {
                return !data.isSeparator;
            });

            if (filters.length > 0) {
                table.setFilter(filters);
            }
        },

        /**
         * Clear all filters
         */
        clearFilters: function() {
            // Clear form filters
            $('#filter-course, #filter-activity, #filter-type').val('');
            $('#search-entries').val('');
            
            // Clear table filters
            if (table) {
                table.clearFilter();
                table.clearHeaderFilter();
            }
        },

        /**
         * Show export options menu
         */
        showExportOptions: function() {
            var self = this;
            
            var exportMenu = $('<div class="dropdown-menu dropdown-menu-end show">' +
                '<h6 class="dropdown-header">Export Options</h6>' +
                '<button class="dropdown-item" data-format="csv">' +
                '<i class="fa fa-file-csv me-2"></i>Export as CSV</button>' +
                '<button class="dropdown-item" data-format="json">' +
                '<i class="fa fa-file-code me-2"></i>Export as JSON</button>' +
                '<button class="dropdown-item" data-format="xlsx">' +
                '<i class="fa fa-file-excel me-2"></i>Export as Excel</button>' +
                '</div>');

            // Position menu
            var exportBtn = $('#export-data');
            var offset = exportBtn.offset();
            exportMenu.css({
                'position': 'absolute',
                'top': offset.top + exportBtn.outerHeight(),
                'left': offset.left - 150,
                'z-index': 1050
            });

            // Handle clicks
            exportMenu.find('[data-format]').on('click', function() {
                var format = $(this).data('format');
                self.exportData(format);
                exportMenu.remove();
            });

            // Remove on outside click
            $(document).one('click', function() {
                exportMenu.remove();
            });

            $('body').append(exportMenu);
        },

        /**
         * Export table data (filter out separators)
         */
        exportData: function(format) {
            if (!table) {
                return;
            }

            var filename = 'valuemap_entries_' + new Date().toISOString().slice(0,10);

            // Custom download to exclude separator rows
            var exportData = table.getData().filter(function(row) {
                return !row.isSeparator;
            });

            if (format === 'csv') {
                table.download("csv", filename + ".csv", {}, "active");
            } else if (format === 'json') {
                var dataStr = JSON.stringify(exportData, null, 2);
                var dataBlob = new Blob([dataStr], {type: 'application/json'});
                var url = URL.createObjectURL(dataBlob);
                var link = document.createElement('a');
                link.href = url;
                link.download = filename + '.json';
                link.click();
                URL.revokeObjectURL(url);
            } else if (format === 'xlsx') {
                table.download("xlsx", filename + ".xlsx", {sheetName: "Value Map Entries"}, "active");
            }
        },

        /**
         * Toggle fullscreen mode
         */
        toggleFullscreen: function() {
            $('body').toggleClass('valuemapdoc-fullscreen');
            
            var $btn = $('#toggle-fullscreen');
            if ($('body').hasClass('valuemapdoc-fullscreen')) {
                $btn.html('<i class="fa fa-compress"></i> Exit Fullscreen');
                if (table) {
                    table.redraw(true);
                }
            } else {
                $btn.html('<i class="fa fa-expand"></i> Fullscreen');
                if (table) {
                    table.redraw(true);
                }
            }
        },

        /**
         * Show entry details modal
         */
        showEntryDetails: function(entryId) {
            var entryData = allData.find(function(entry) {
                return entry.id === entryId && !entry.isSeparator;
            });

            if (!entryData) {
                return;
            }

            var modalHtml = '<div class="modal fade" id="entryDetailsModal" tabindex="-1">' +
                '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">Entry Details</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="row mb-3">' +
                '<div class="col-md-6"><strong>Course:</strong><br>' + entryData.course_name + '</div>' +
                '<div class="col-md-6"><strong>Activity:</strong><br>' + entryData.activity_name + '</div>' +
                '</div>' +
                '<div class="row mb-3">' +
                '<div class="col-md-6"><strong>Author:</strong><br>' + entryData.user_fullname + '</div>' +
                '</div>' +
                '<div class="row mb-3">' +
                '<div class="col-md-6"><strong>Created:</strong><br>' + entryData.timecreated_formatted + '</div>' +
                '<div class="col-md-6"><strong>Modified:</strong><br>' + entryData.timemodified_formatted + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<a href="' + entryData.edit_url + '" class="btn btn-primary" target="_blank">' +
                '<i class="fa fa-edit"></i> Edit in Activity</a>' +
                '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

            // Remove existing modal
            $('#entryDetailsModal').remove();
            
            // Add and show new modal
            $('body').append(modalHtml);
            var modal = new bootstrap.Modal(document.getElementById('entryDetailsModal'));
            modal.show();
        },

        /**
         * Format type name for display
         */
        formatTypeName: function(type) {
            var names = {
                'customer_profile': 'Customer Profile',
                'value_proposition': 'Value Proposition',
                'pain_analysis': 'Pain Analysis', 
                'value_map': 'Value Map',
                'general': 'General',
                'unknown': 'Unknown'
            };
            return names[type] || type;
        },

        /**
         * Show different states
         */
        showLoadingState: function() {
            $('#loading-overlay').show();
            $('#valuemaps-table').hide();
            $('#empty-state').hide();
            $('#error-state').hide();
        },

        showTableState: function() {
            $('#loading-overlay').hide();
            $('#valuemaps-table').show();
            $('#empty-state').hide();
            $('#error-state').hide();
        },

        showEmptyState: function() {
            $('#loading-overlay').hide();
            $('#valuemaps-table').hide();
            $('#empty-state').show();
            $('#error-state').hide();
        },

        showErrorState: function(message) {
            $('#loading-overlay').hide();
            $('#valuemaps-table').hide();
            $('#empty-state').hide();
            $('#error-state').show();
            $('#error-message').text(message);
        }
    };
});