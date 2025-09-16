/* eslint-env es6 */
/* eslint-disable max-len */
/* eslint-disable no-unused-vars */
/* eslint-disable no-console */
/* eslint-disable no-trailing-spaces */

/**
 * Value Maps manager with local Tabulator.js - inspired by mod_valuemapdoc
 */
define([
    'aitoolsub_valuemapdoc/tabulatorlib',
    'jquery', 
    'core/ajax', 
    'core/notification'
], function(
    Tabulator,
    $, 
    Ajax, 
    Notification
) {
    
    var table;
    var config = window.valuemapsConfig || {};
    var allData = [];
    var currentUsername = M.cfg.fullname || M.cfg.username || 'Me';

    return {
        init: function() {
            console.log('[valuemaps_manager] Module loaded with Tabulator');
            this.bindEvents();
            this.loadData();
        },

        bindEvents: function() {
            // Refresh data button
            $('#refresh-data').on('click', this.loadData.bind(this));
            
            // Export data button  
            $('#export-data').on('click', this.showExportOptions.bind(this));
            
            // Clear filters button
            $('#clear-filters').on('click', this.clearFilters.bind(this));
            
            // Toggle columns button
            $('#toggle-columns').on('click', this.showColumnModal.bind(this));
            
            // Filter controls
            $('#filter-course, #filter-activity, #filter-type').on('change', this.applyFilters.bind(this));
            $('#search-entries').on('input', this.applyFilters.bind(this));
            
            // Retry loading button
            $('#retry-loading').on('click', this.loadData.bind(this));
            
            // Column modal apply button
            $('#apply-columns').on('click', this.applyColumnVisibility.bind(this));
            
            // Fullscreen toggle (inspired by mod_valuemapdoc)
            $('#toggle-fullscreen').on('click', this.toggleFullscreen.bind(this));
        },

        loadData: function() {
            var self = this;
            
            // Show loading state
            this.showLoadingState();
            
            // Call AJAX service
            var promises = Ajax.call([{
                methodname: 'aitoolsub_valuemapdoc_get_all_entries_global',
                args: {
                    userid: config.userid || 0
                }
            }]);

            promises[0].done(function(data) {
                allData = data.entries || [];
                
                // Parse entry_data JSON strings back to objects
                allData = allData.map(function(entry) {
                    if (typeof entry.entry_data === 'string') {
                        try {
                            entry.entry_data = JSON.parse(entry.entry_data);
                        } catch (e) {
                            console.warn('Could not parse entry_data for entry', entry.id, e);
                            entry.entry_data = {};
                        }
                    }
                    return entry;
                });
                
                console.log('[valuemaps_manager] Entries loaded:', allData.length, 'records');
                
                // Update statistics
                self.updateStatistics(data.statistics);
                
                // Populate filter options
                self.populateFilterOptions(data);
                
                // Initialize or update table
                if (allData.length > 0) {
                    self.initializeTable();
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
        },

        updateStatistics: function(stats) {
            $('#stat-total-entries').html(stats.total_entries || 0);
            $('#stat-unique-courses').html(stats.unique_courses || 0);
            $('#stat-unique-activities').html(stats.unique_activities || 0);
        },

        populateFilterOptions: function(data) {
            // Populate course filter
            var courseSelect = $('#filter-course');
            courseSelect.empty().append('<option value="">All Courses</option>');
            if (data.statistics && data.statistics.courses_list) {
                data.statistics.courses_list.forEach(function(course) {
                    courseSelect.append($('<option>').val(course).text(course));
                });
            }
            
            // Populate activity filter
            var activitySelect = $('#filter-activity');
            activitySelect.empty().append('<option value="">All Activities</option>');
            if (data.statistics && data.statistics.activities_list) {
                data.statistics.activities_list.forEach(function(activity) {
                    activitySelect.append($('<option>').val(activity).text(activity));
                });
            }
        },

        initializeTable: function() {
            var self = this;
            
            // Destroy existing table if it exists
            if (table) {
                table.destroy();
            }

            // Enhanced columns similar to mod_valuemapdoc approach
            var columns = [
                {
                    title: config.strings.id || "ID",
                    field: "id",
                    width: 80,
                    frozen: true,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filter ID...",
                    headerSort: true
                },
                {
                    title: config.strings.course || "Course",
                    field: "course_name",
                    width: 200,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filter course...",
                    formatter: function(cell) {
                        var data = cell.getRow().getData();
                        return '<strong>' + data.course_name + '</strong><br>' +
                               '<small class="text-muted">(' + data.course_shortname + ')</small>';
                    }
                },
                {
                    title: config.strings.activity || "Activity", 
                    field: "activity_name",
                    width: 180,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filter activity..."
                },
                {
                    title: config.strings.entry_type || "Type",
                    field: "entry_type",
                    width: 130,
                    headerFilter: "select",
                    headerFilterParams: {
                        "customer_profile": "Customer Profile",
                        "value_proposition": "Value Proposition", 
                        "pain_analysis": "Pain Analysis",
                        "value_map": "Value Map",
                        "general": "General"
                    },
                    formatter: function(cell) {
                        var value = cell.getValue();
                        var badges = {
                            'customer_profile': 'bg-primary',
                            'value_proposition': 'bg-success',
                            'pain_analysis': 'bg-warning',
                            'value_map': 'bg-info',
                            'general': 'bg-secondary'
                        };
                        return '<span class="badge ' + (badges[value] || 'bg-secondary') + '">' + 
                               self.formatTypeName(value) + '</span>';
                    }
                },
                {
                    title: config.strings.preview || "Preview",
                    field: "entry_preview",
                    width: 300,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Search preview...",
                    formatter: function(cell) {
                        var preview = cell.getValue();
                        return '<div class="text-truncate" title="' + preview + '">' + preview + '</div>';
                    }
                },
                {
                    title: config.strings.created || "Created",
                    field: "timecreated_relative", 
                    width: 120,
                    sorter: function(a, b, aRow, bRow) {
                        return aRow.getData().timecreated - bRow.getData().timecreated;
                    },
                    formatter: function(cell) {
                        var data = cell.getRow().getData();
                        return '<span title="' + data.timecreated_formatted + '">' + 
                               data.timecreated_relative + '</span>';
                    }
                },
                {
                    title: config.strings.modified || "Modified",
                    field: "timemodified_relative",
                    width: 120,
                    sorter: function(a, b, aRow, bRow) {
                        return aRow.getData().timemodified - bRow.getData().timemodified;
                    },
                    formatter: function(cell) {
                        var data = cell.getRow().getData();
                        return '<span title="' + data.timemodified_formatted + '">' + 
                               data.timemodified_relative + '</span>';
                    }
                },
                {
                    title: config.strings.actions || "Actions",
                    field: "actions",
                    width: 160,
                    headerSort: false,
                    formatter: function(cell) {
                        var data = cell.getRow().getData();
                        return '<div class="btn-group btn-group-sm" role="group">' +
                               '<a href="' + data.view_url + '" class="btn btn-outline-primary btn-sm" title="View Entry">' +
                               '<i class="fa fa-eye"></i></a>' +
                               '<a href="' + data.edit_url + '" class="btn btn-outline-secondary btn-sm" title="Edit in Activity">' + 
                               '<i class="fa fa-edit"></i></a>' +
                               '<button class="btn btn-outline-info btn-sm" onclick="window.valuemapsManager.showEntryDetails(' + data.id + ')" title="Show Details">' +
                               '<i class="fa fa-info-circle"></i></button>' +
                               '</div>';
                    }
                }
            ];

            // Initialize Tabulator with settings similar to mod_valuemapdoc
            // eslint-disable-next-line no-undef
            table = new Tabulator("#valuemaps-table", {
                data: allData,
                columns: columns,
                layout: "fitDataTable",
                responsiveLayout: "collapse",
                height: "600px",
                pagination: "local",
                paginationSize: 20,
                paginationSizeSelector: [10, 20, 50, 100],
                movableColumns: true,
                resizableRows: false,
                selectable: false, // Różnica - brak selection w AI Tools
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
                }
            });

            // Wait for table to build
            table.on("tableBuilt", function(){
                console.log('[valuemaps_manager] Table built successfully');
                
                // Add user filter toggle (inspired by mod_valuemapdoc)
                self.addUserFilterToggle();
            });

            // Handle double-click to open edit in activity context (like mod_valuemapdoc)
            table.on("rowDblClick", function(e, row){
                var data = row.getData();
                var editUrl = data.edit_url;
                window.open(editUrl, '_blank');
            });

            // Make manager globally available for action buttons
            window.valuemapsManager = this;
        },

        addUserFilterToggle: function() {
            // Add toggle button similar to mod_valuemapdoc approach
            var toolbar = $('.card-header .table-controls');
            if (toolbar.length === 0) {
                return;
            }

            var toggleHtml = '<button type="button" class="btn btn-sm btn-outline-info ms-2" id="user-filter-toggle">' +
                           '<i class="fa fa-user"></i> My Entries</button>';
            
            toolbar.prepend(toggleHtml);

            var showingAll = true;
            $('#user-filter-toggle').on('click', function() {
                var $btn = $(this);
                
                if (showingAll) {
                    // Note: Since we don't have username in entries data,
                    // this would need to be implemented differently
                    // For now it's a placeholder
                    $btn.html('<i class="fa fa-users"></i> All Entries');
                    $btn.removeClass('btn-outline-info').addClass('btn-outline-secondary');
                    showingAll = false;
                } else {
                    // Show all entries
                    if (table) {
                        table.clearFilter();
                    }
                    $btn.html('<i class="fa fa-user"></i> My Entries'); 
                    $btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
                    showingAll = true;
                }
            });
        },

        toggleFullscreen: function() {
            // Similar to mod_valuemapdoc fullscreen toggle
            $('body').toggleClass('valuemapdoc-fullscreen');
            var $btn = $('#toggle-fullscreen');
            
            if ($('body').hasClass('valuemapdoc-fullscreen')) {
                $btn.html('<i class="fa fa-compress"></i> Exit Fullscreen');
                
                // Adjust table height in fullscreen
                if (table) {
                    table.redraw(true);
                }
            } else {
                $btn.html('<i class="fa fa-expand"></i> Fullscreen');
                
                // Reset table height
                if (table) {
                    table.redraw(true);
                }
            }
        },

        showEntryDetails: function(entryId) {
            // Find entry data
            var entryData = allData.find(function(entry) {
                return entry.id === entryId;
            });
            
            if (!entryData) {
                return;
            }

            // Create modal with entry details (similar to mod_valuemapdoc patterns)
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
                          '<div class="col-md-6"><strong>Type:</strong><br>' + 
                          '<span class="badge ' + this.getTypeBadgeClass(entryData.entry_type) + '">' + 
                          this.formatTypeName(entryData.entry_type) + '</span></div>' +
                          '<div class="col-md-6"><strong>Created:</strong><br>' + entryData.timecreated_formatted + '</div>' +
                          '</div>' +
                          '<div class="mb-3"><strong>Preview:</strong><br>' +
                          '<div class="border rounded p-3 bg-light">' + entryData.entry_preview + '</div></div>' +
                          '</div>' +
                          '<div class="modal-footer">' +
                          '<a href="' + entryData.edit_url + '" class="btn btn-primary" target="_blank">' +
                          '<i class="fa fa-edit"></i> Edit in Activity</a>' +
                          '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>' +
                          '</div>' +
                          '</div></div></div>';

            // Remove existing modal and add new one
            $('#entryDetailsModal').remove();
            $('body').append(modalHtml);
            $('#entryDetailsModal').modal('show');
        },

        getTypeBadgeClass: function(type) {
            var badges = {
                'customer_profile': 'bg-primary',
                'value_proposition': 'bg-success',
                'pain_analysis': 'bg-warning',
                'value_map': 'bg-info',
                'general': 'bg-secondary'
            };
            return badges[type] || 'bg-secondary';
        },

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

            // Apply filters (similar to mod_valuemapdoc approach)
            if (courseFilter) {
                table.addFilter("course_name", "=", courseFilter);
            }
            if (activityFilter) {
                table.addFilter("activity_name", "=", activityFilter);
            }
            if (typeFilter) {
                table.addFilter("entry_type", "=", typeFilter);
            }
            if (searchFilter) {
                table.addFilter([
                    {field: "entry_preview", type: "like", value: searchFilter},
                    {field: "course_name", type: "like", value: searchFilter},
                    {field: "activity_name", type: "like", value: searchFilter}
                ]);
            }
        },

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
            
            var exportBtn = $('#export-data');
            var offset = exportBtn.offset();
            
            $('body').append(exportMenu);
            exportMenu.css({
                position: 'absolute',
                top: offset.top + exportBtn.outerHeight(),
                left: offset.left + exportBtn.outerWidth() - exportMenu.outerWidth(),
                zIndex: 1050
            });
            
            exportMenu.on('click', '.dropdown-item', function(e) {
                var format = $(this).data('format');
                self.exportData(format);
                exportMenu.remove();
            });
            
            $(document).one('click', function() {
                exportMenu.remove();
            });
        },

        exportData: function(format) {
            if (!table) {
                return;
            }
            
            var filename = "valuemap_entries_" + new Date().toISOString().slice(0, 10);
            
            // Use Tabulator's built-in export functions (same as mod_valuemapdoc)
            switch (format) {
                case 'csv':
                    table.download("csv", filename + ".csv");
                    break;
                case 'json':
                    table.download("json", filename + ".json");
                    break;
                case 'xlsx':
                    table.download("xlsx", filename + ".xlsx", {sheetName: "Value Map Entries"});
                    break;
            }
        },

        showColumnModal: function() {
            this.setupColumnModal();
            $('#columnModal').modal('show');
        },

        setupColumnModal: function() {
            if (!table) {
                return;
            }
            
            var checkboxContainer = $('#column-checkboxes');
            checkboxContainer.empty();
            
            var columns = table.getColumnDefinitions();
            columns.forEach(function(col) {
                if (col.field && col.field !== 'actions') {
                    var isVisible = table.getColumn(col.field).isVisible();
                    var checkbox = $('<div class="form-check">' +
                        '<input class="form-check-input" type="checkbox" id="col-' + col.field + '" ' +
                        (isVisible ? 'checked' : '') + '>' +
                        '<label class="form-check-label" for="col-' + col.field + '">' + 
                        col.title + '</label>' +
                        '</div>');
                    checkboxContainer.append(checkbox);
                }
            });
        },

        applyColumnVisibility: function() {
            if (!table) {
                return;
            }
            
            $('#column-checkboxes .form-check-input').each(function() {
                var field = $(this).attr('id').replace('col-', '');
                var isChecked = $(this).is(':checked');
                var column = table.getColumn(field);
                
                if (column) {
                    if (isChecked) {
                        column.show();
                    } else {
                        column.hide();
                    }
                }
            });
            
            $('#columnModal').modal('hide');
        }
    };
});