/**
 * ValueMaps Manager for AI Tools Subplugin - Fixed Version
 * 
 * @module aitoolsub_valuemapdoc/valuemaps_manager
 * @copyright 2024 Your Organization
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    
    var table = null;
    var config = window.valuemapsConfig || {};
    var allData = [];
    var processedData = [];
    var selectedRows = [];

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
            $('#filter-course, #filter-activity').on('change', function() {
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
                
                // Debug: Check first entry structure
                if (allData.length > 0) {
                    console.log('[valuemaps_manager] First entry data:', allData[0]);
                    console.log('[valuemaps_manager] Available fields:', Object.keys(allData[0]));
                    
                    // Debug specific fields
                    console.log('[valuemaps_manager] Entry market field:', allData[0].market);
                    console.log('[valuemaps_manager] Entry role field:', allData[0].role);
                    console.log('[valuemaps_manager] Entry entry_data field:', allData[0].entry_data);
                }
                
                // Update statistics
                self.updateStatistics(data.statistics);
                
                // Populate filter options
                self.populateFilterOptions(data);
                
                // Process data with group separators
                processedData = self.processDataWithSeparators(allData);
                
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
         * Process data and add group separators with edit buttons
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
                        activity_name: entry.activity_name,
                        cmid: entry.cmid,
                        // Fix: Create proper view URL for the module
                        edit_url: M.cfg.wwwroot + '/mod/valuemapdoc/view.php?id=' + entry.cmid
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
         * Prepare columns with checkbox selection and simplified data
         */
        prepareColumns: function(columns) {
            var self = this;
            var enhancedColumns = [];
            
            // Add checkbox column first (like in module)
            enhancedColumns.push({
                formatter: function(cell, formatterParams) {
                    var data = cell.getRow().getData();
                    if (data.isSeparator) {
                        // Show edit button in separator row
                        return '<a href="' + data.edit_url + '" class="btn btn-sm btn-primary" target="_blank">' +
                               '<i class="fa fa-edit"></i> Edit</a>';
                    } else {
                        // Show checkbox for data rows
                        return '<input type="checkbox" class="entry-checkbox" data-entry-id="' + data.id + '">';
                    }
                },
                width: 80,
                hozAlign: "center",
                headerSort: false,
                cellClick: function(e, cell) {
                    var data = cell.getRow().getData();
                    if (!data.isSeparator) {
                        e.stopPropagation();
                        self.toggleRowSelection(data.id);
                    }
                }
            });
            
            // Add mapped columns from user's field level
            columns.forEach(function(col) {
                enhancedColumns.push({
                    title: col.title,
                    field: col.field,
                    hozAlign: col.hozAlign || 'left',
                    headerSort: col.headerSort !== false,
                    width: col.width || 150,
                    headerFilter: "input",
                    editable: false,
                    formatter: function(cell) {
                        var row = cell.getRow();
                        var data = row.getData();
                        
                        if (data.isSeparator) {
                            return '';
                        }
                        
                        // Get value from the actual field
                        var value = data[col.field];
                        
                        // Debug first few entries
                        if (data.id <= 8) {
                            console.log('Entry', data.id, 'field', col.field, 'value:', value);
                        }
                        
                        if (value && value.length > 50) {
                            return '<div class="text-truncate" style="max-width: ' + (col.width - 20) + 'px;" title="' + 
                                   value + '">' + value + '</div>';
                        }
                        return value || '';
                    }
                });
            });
            
            // Add username column (simplified)
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
                    var data = row.getData();
                    
                    if (data.isSeparator) {
                        return '';
                    }
                    
                    var value = cell.getValue();
                    if (data.ismaster === 1) {
                        return '<i class="fa fa-star text-warning" title="Master entry"></i> ' + value;
                    }
                    return value;
                }
            });

            return enhancedColumns;
        },

        /**
         * Toggle row selection
         */
        toggleRowSelection: function(entryId) {
            var checkbox = $('input[data-entry-id="' + entryId + '"]');
            var isSelected = checkbox.prop('checked');
            
            if (isSelected) {
                selectedRows = selectedRows.filter(function(id) {
                    return id !== entryId;
                });
            } else {
                selectedRows.push(entryId);
            }
            
            checkbox.prop('checked', !isSelected);
            this.updateSelectionUI();
        },

        /**
         * Update selection UI
         */
        updateSelectionUI: function() {
            var count = selectedRows.length;
            var selectionInfo = $('.selection-info');
            
            if (count > 0) {
                if (selectionInfo.length === 0) {
                    $('.table-controls').prepend(
                        '<div class="selection-info alert alert-info py-2 px-3 me-2">' +
                        '<span class="selection-count">' + count + '</span> entries selected ' +
                        '<button class="btn btn-sm btn-primary ms-2" id="generate-docs">Generate Documents</button>' +
                        '<button class="btn btn-sm btn-secondary ms-1" id="clear-selection">Clear</button>' +
                        '</div>'
                    );
                    
                    // Bind events
                    $('#generate-docs').on('click', this.generateDocuments.bind(this));
                    $('#clear-selection').on('click', this.clearSelection.bind(this));
                } else {
                    selectionInfo.find('.selection-count').text(count);
                }
            } else {
                selectionInfo.remove();
            }
        },

        /**
         * Clear selection
         */
        clearSelection: function() {
            selectedRows = [];
            $('.entry-checkbox').prop('checked', false);
            this.updateSelectionUI();
        },

        /**
         * Generate documents (placeholder)
         */
        generateDocuments: function() {
            if (selectedRows.length === 0) {
                alert('Please select entries first');
                return;
            }
            
            console.log('Generating documents for entries:', selectedRows);
            alert('Document generation will be implemented here\nSelected entries: ' + selectedRows.length);
        },

        /**
         * Initialize Tabulator table
         */
        initializeTable: function(columns, processedData) {
            var self = this;
            
            var enhancedColumns = this.prepareColumns(columns);

            // Initialize table
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
                selectable: false, // We handle selection manually
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
                        row.getElement().style.borderBottom = '1px solid #dee2e6';
                        
                        // Set content for separator row - show title across all columns
                        var cells = row.getCells();
                        if (cells.length > 1) {
                            // First cell gets the button, second cell gets the title
                            cells[1].getElement().innerHTML = '<i class="fa fa-folder-open me-2"></i>' + data.groupTitle;
                            cells[1].getElement().style.fontWeight = 'bold';
                            
                            // Hide remaining cells
                            for (var i = 2; i < cells.length; i++) {
                                cells[i].getElement().style.display = 'none';
                            }
                            // Expand second cell to cover hidden ones
                            cells[1].getElement().colSpan = cells.length - 1;
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
                
                // Add user filter toggle
                self.addUserFilterToggle(table);
            });

            // Double click to edit (for data rows only)
            table.on("rowDblClick", function(e, row) {
                var data = row.getData();
                if (!data.isSeparator) {
                    window.open(data.edit_url, '_blank');
                }
            });

            // Make manager globally available
            window.valuemapsManager = this;
        },

        /**
         * Add user filter toggle button
         */
        addUserFilterToggle: function(table) {
            var self = this;
            var toolbar = $('.card-header .table-controls');
            if (toolbar.length === 0) {
                return;
            }

            toolbar.append('<button type="button" class="btn btn-sm btn-outline-info ms-2" id="user-filter-toggle">' +
                          '<i class="fa fa-user"></i> My Entries</button>');
            
            var showingOnlyMine = true;
            $('#user-filter-toggle').on('click', function() {
                var $btn = $(this);
                if (showingOnlyMine) {
                    // Show all entries (including separators)
                    table.clearHeaderFilter("username");
                    $btn.html('<i class="fa fa-users"></i> All Entries');
                    $btn.removeClass('btn-outline-info').addClass('btn-outline-secondary');
                    showingOnlyMine = false;
                } else {
                    // Show only user entries but keep separators visible
                    self.filterToUserEntries(table);
                    $btn.html('<i class="fa fa-user"></i> My Entries');
                    $btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
                    showingOnlyMine = true;
                }
            });
            
            // Start with user entries filtered
            this.filterToUserEntries(table);
        },

        /**
         * Filter to user entries but keep separators
         */
        filterToUserEntries: function(table) {
            var currentUserId = M.cfg.userid;
            
            table.setFilter(function(data) {
                // Always show separators
                if (data.isSeparator) {
                    return true;
                }
                // Show only current user's entries
                return data.userid == currentUserId;
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
            var searchFilter = $('#search-entries').val();

            // Build filter function
            var filters = [];
            
            if (courseFilter) {
                filters.push(function(data) {
                    return data.isSeparator || data.course_name === courseFilter;
                });
            }
            
            if (activityFilter) {
                filters.push(function(data) {
                    return data.isSeparator || data.activity_name === activityFilter;
                });
            }
            
            if (searchFilter) {
                filters.push(function(data) {
                    if (data.isSeparator) {
                        return data.groupTitle.toLowerCase().includes(searchFilter.toLowerCase());
                    }
                    // Search in various fields
                    var searchFields = ['course_name', 'activity_name', 'username'];
                    return searchFields.some(function(field) {
                        var value = data[field];
                        return value && value.toLowerCase().includes(searchFilter.toLowerCase());
                    });
                });
            }

            // Apply all filters
            if (filters.length > 0) {
                table.setFilter(function(data) {
                    return filters.every(function(filter) {
                        return filter(data);
                    });
                });
            } else {
                // If no filters, show according to user toggle
                var isShowingAll = $('#user-filter-toggle').hasClass('btn-outline-secondary');
                if (isShowingAll) {
                    table.clearFilter();
                } else {
                    this.filterToUserEntries(table);
                }
            }
        },

        /**
         * Clear all filters
         */
        clearFilters: function() {
            $('#filter-course, #filter-activity').val('');
            $('#search-entries').val('');
            
            if (table) {
                // Reset to user entries view
                this.filterToUserEntries(table);
                
                // Reset user toggle
                var $btn = $('#user-filter-toggle');
                $btn.html('<i class="fa fa-user"></i> My Entries');
                $btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
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
                '</div>');

            var exportBtn = $('#export-data');
            var offset = exportBtn.offset();
            exportMenu.css({
                'position': 'absolute',
                'top': offset.top + exportBtn.outerHeight(),
                'left': offset.left - 150,
                'z-index': 1050
            });

            exportMenu.find('[data-format]').on('click', function() {
                var format = $(this).data('format');
                self.exportData(format);
                exportMenu.remove();
            });

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