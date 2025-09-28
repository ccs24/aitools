// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ValueMaps Manager JavaScript Module
 * Optimized for cross-course/activity value map entries display
 * Handles 1-10 activities with 5-50 entries each (~500 max entries)
 *
 * @copyright  2024 Local AI Tools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    var table = null;
    var config = window.valuemapsConfig || {};
    var allData = [];
    var processedData = [];
    var selectedRows = [];
    var currentStatistics = {};


    return {
        /**
         * Initialize the module
         */
        init: function() {
            // Get columns configuration from DOM
            var columnsElement = document.querySelector('#valuemap-columns');
            if (columnsElement && columnsElement.textContent) {
                var columns;
                try {
                    columns = JSON.parse(columnsElement.textContent);
                } catch (e) {
                    this.showErrorState('Invalid column configuration');
                    return;
                }

                if (Array.isArray(columns)) {
                    this.waitForTabulator(columns);
                } else {
                    this.showErrorState('Column configuration is not valid');
                }
            } else {
                this.showErrorState('Column configuration not found');
            }
        },

        /**
         * Wait for Tabulator to be available and then initialize
         * @param {Array} columns Column configuration from DOM
         */
        waitForTabulator: function(columns) {
            var self = this;
            if (typeof window.Tabulator !== 'undefined') {
                this.bindEvents();
                this.loadData(columns);
            } else {
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
         * Load data from server - use static columns from DOM
         * @param {Array} columns Column configuration from DOM
         */
        loadData: function(columns) {
            var self = this;
            this.showLoadingState();

            Ajax.call([{
                methodname: 'aitoolsub_valuemapdoc_get_all_entries_global',
                args: {
                    userid: config.userid || 0,
                    page: 0,
                    limit: 0
                }
            }])[0].done(function(data) {
                // Store raw data
                allData = data.entries || [];
                currentStatistics = data.statistics || {};

                // Update UI with statistics
                self.updateStatistics(currentStatistics);
                self.populateFilterOptions(currentStatistics);

                // Use columns from DOM (static configuration)
                console.log('Using static columns from DOM:', columns);
                console.log('Static columns count:', columns.length);

                if (allData.length > 0) {
                    var groupedData = self.processDataWithNativeGrouping(allData);
                    self.initializeTableWithGrouping(columns, groupedData);
                    self.showTableState();
                } else {
                    self.showEmptyState();
                }

            }).fail(function(error) {
                self.showErrorState(error.message || 'Failed to load entries');
                Notification.exception(error);
            });
        },

        /**
         * Build user-specific columns based on field level (7/13/20 fields)
         * @param {Array} userFields Array of field names for user's level
         * @param {Object} levelConfig User's level configuration
         * @return {Array} Column definitions for Tabulator
         */
        buildUserColumns: function(userFields, levelConfig) {
            // Field to title mapping
            var fieldTitles = {
                'market': 'Market',
                'industry': 'Industry', 
                'role': 'Role',
                'businessgoal': 'Business Goal',
                'strategy': 'Strategy',
                'difficulty': 'Difficulty',
                'situation': 'Situation',
                'statusquo': 'Status Quo',
                'coi': 'Cost of Inaction',
                'differentiator': 'Differentiator',
                'impact': 'Impact',
                'newstate': 'New State',
                'successmetric': 'Success Metric',
                'impactstrategy': 'Impact Strategy',
                'impactbusinessgoal': 'Impact Business Goal',
                'impactothers': 'Impact Others',
                'proof': 'Proof',
                'time2results': 'Time to Results',
                'quote': 'Quote',
                'clientname': 'Client Name'
            };

            var columns = [];

            // Build columns from user fields
            userFields.forEach(function(fieldName) {
                columns.push({
                    title: fieldTitles[fieldName] || fieldName,
                    field: fieldName,
                    hozAlign: 'left',
                    headerSort: true,
                    width: 150,
                    headerFilter: 'input',
                    headerFilterPlaceholder: 'Filter...'
                });
            });

            return columns;
        },

        /**
         * OPTION 2: Process data for native Tabulator grouping
         * @param {Array} entries Array of entries from server  
         * @return {Array} Processed data for grouping
         */
        processDataWithNativeGrouping: function(entries) {
            if (entries.length === 0) {
                return [];
            }

            // Add grouping field and proper URLs to each entry
            entries.forEach(function(entry) {
                entry.course_activity_group = entry.course_name + ' → ' + entry.activity_name;
                
                // Fix undefined URLs - build proper activity URLs
                entry.view_activity_url = M.cfg.wwwroot + '/mod/valuemapdoc/view.php?id=' + entry.cmid;
                entry.edit_url = M.cfg.wwwroot + '/mod/valuemapdoc/edit.php?id=' + entry.cmid + '&entryid=' + entry.id;
            });

            return entries;
        },

        /**
         * Update statistics display with cross-course data
         * @param {Object} stats Statistics object from server
         */
        updateStatistics: function(stats) {
            $('#stat-total-entries').text(stats.total_entries || 0);
            $('#stat-unique-courses').text(stats.unique_courses || 0);
            $('#stat-unique-activities').text(stats.unique_activities || 0);

            // Update dashboard summary if present
            if ($('.dashboard-summary').length > 0) {
                $('.dashboard-summary .entries-count').text(stats.total_entries || 0);
                $('.dashboard-summary .courses-count').text(stats.unique_courses || 0);
                $('.dashboard-summary .activities-count').text(stats.unique_activities || 0);
            }
        },

        /**
         * Populate filter dropdown options from statistics
         * @param {Object} stats Statistics containing lists
         */
        populateFilterOptions: function(stats) {
            // Populate course filter
            var courseSelect = $('#filter-course');
            courseSelect.find('option:not(:first)').remove();
            if (stats.courses_list && stats.courses_list.length > 0) {
                stats.courses_list.forEach(function(course) {
                    courseSelect.append('<option value="' + course + '">' + course + '</option>');
                });
            }

            // Populate activity filter
            var activitySelect = $('#filter-activity');
            activitySelect.find('option:not(:first)').remove();
            if (stats.activities_list && stats.activities_list.length > 0) {
                stats.activities_list.forEach(function(activity) {
                    activitySelect.append('<option value="' + activity + '">' + activity + '</option>');
                });
            }
        },

        /**
         * OPTION 1: Prepare columns for custom separators (CSS positioning)
         * @param {Array} userColumns User's field columns (7/13/20)
         * @return {Array} Enhanced columns for Tabulator
         */
        prepareColumns: function(userColumns) {
            var self = this;
            var enhancedColumns = [];

            // 1. Checkbox column - ONLY CHECKBOX, no HTML
            enhancedColumns.push({
                title: '',
                field: 'checkbox',
                width: 40,
                hozAlign: "center",
                headerSort: false,
                formatter: function(cell) {
                    var data = cell.getRow().getData();
                    if (data.isSeparator) {
                        return '';
                    }
                    return '<input type="checkbox" class="entry-checkbox" data-entry-id="' + data.id + '">';
                },
                cellClick: function(e, cell) {
                    e.stopPropagation();
                    var data = cell.getRow().getData();
                    if (!data.isSeparator) {
                        self.toggleRowSelection(data.id);
                    }
                }
            });

            // 2. User's field columns - PLAIN TEXT ONLY
            userColumns.forEach(function(col) {
                enhancedColumns.push({
                    title: col.title,
                    field: col.field,
                    hozAlign: 'left',
                    headerSort: true,
                    width: col.width || 150,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filter " + col.title + "...",
                    editable: false,
                    formatter: function(cell) {
                        var data = cell.getRow().getData();
                        if (data.isSeparator) {
                            return '';
                        }
                        // PLAIN TEXT - no HTML formatting
                        return data[col.field] || '';
                    }
                });
            });

            return enhancedColumns;
        },

        /**
         * OPTION 2: Prepare columns for native grouping
         * @param {Array} userColumns User's field columns (7/13/20)
         * @return {Array} Enhanced columns for Tabulator
         */
        prepareColumnsForGrouping: function(userColumns) {
            var self = this;
            var enhancedColumns = [];

            // 1. Checkbox column
            enhancedColumns.push({
                title: '',
                field: 'checkbox',
                width: 40,
                hozAlign: "center",
                headerSort: false,
                formatter: function(cell) {
                    return '<input type="checkbox" class="entry-checkbox" data-entry-id="' + cell.getRow().getData().id + '">';
                },
                cellClick: function(e, cell) {
                    e.stopPropagation();
                    self.toggleRowSelection(cell.getRow().getData().id);
                }
            });

            // 2. User's field columns
            userColumns.forEach(function(col) {
                enhancedColumns.push({
                    title: col.title,
                    field: col.field,
                    hozAlign: 'left',
                    headerSort: true,
                    width: col.width || 150,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filter " + col.title + "...",
                    editable: false,
                    formatter: function(cell) {
                        return cell.getRow().getData()[col.field] || '';
                    }
                });
            });

            return enhancedColumns;
        },


        /**
         * OPTION 2: Initialize table with native Tabulator grouping
         * @param {Array} userColumns User's field columns
         * @param {Array} data Table data
         */
        initializeTableWithGrouping: function(userColumns, data) {
            var self = this;

            if (table) {
                table.destroy();
            }

            var enhancedColumns = this.prepareColumnsForGrouping(userColumns);
            
            // DEBUG: Sprawdź kolumny przed utworzeniem tabeli
//            console.log('Enhanced columns before Tabulator:', enhancedColumns);
//            console.log('Enhanced columns count:', enhancedColumns.length);
//            console.log('First few columns:', enhancedColumns.slice(0, 5));

            // eslint-disable-next-line no-undef
            table = new Tabulator("#valuemaps-table", {
                data: data,
                columns: enhancedColumns,
                height: "100%",
                layout: "fitColumns",
                //responsiveLayout: "hide",
                placeholder: "No entries found",
                pagination: "local",
                paginationSize: 25,
                paginationSizeSelector: [10, 25, 50, 100],
                movableColumns: true,
                resizableRows: false,
                selectable: false,
                tooltipsHeader: true,
                
                // Native grouping approach
                groupBy: "course_activity_group",
                groupHeader: function(value, count, data, group) {
                    // Extract course and activity info from first item
                    var firstItem = data[0];
                    var viewUrl = firstItem.view_activity_url || (M.cfg.wwwroot + '/mod/valuemapdoc/view.php?id=' + firstItem.cmid);
                    
                    return '<span style="font-weight: bold; color: #007bff; cursor: pointer; display: block; padding: 8px; background: #f8f9fa; border-top: 2px solid #007bff;" ' +
                           'onclick="window.open(\'' + viewUrl + '\', \'_blank\')" ' +
                           'title="Click to open activity">' +
                           value + ' (' + count + ' entries)</span>';
                },
                groupStartOpen: true,
                groupToggleElement: "header"
            });

            // Row double-click to edit
            table.on("rowDblClick", function(e, row) {
                var data = row.getData();
                window.open(data.edit_url, '_blank');
            });

            this.initializeSelectionTracking();
        },

        /**
         * Initialize selection tracking for bulk operations
         */
        initializeSelectionTracking: function() {
            var self = this;
            
            // Handle checkbox changes
            $(document).on('change', '.entry-checkbox', function() {
                var entryId = parseInt($(this).data('entry-id'));
                var isChecked = $(this).prop('checked');
                
                if (isChecked) {
                    if (selectedRows.indexOf(entryId) === -1) {
                        selectedRows.push(entryId);
                    }
                } else {
                    selectedRows = selectedRows.filter(function(id) {
                        return id !== entryId;
                    });
                }
                
                self.updateSelectionUI();
            });
        },

        /**
         * Toggle row selection
         * @param {Number} entryId Entry ID to toggle
         */
        toggleRowSelection: function(entryId) {
            var checkbox = $('.entry-checkbox[data-entry-id="' + entryId + '"]');
            var isChecked = checkbox.prop('checked');
            
            checkbox.prop('checked', !isChecked);
            
            if (!isChecked) {
                if (selectedRows.indexOf(entryId) === -1) {
                    selectedRows.push(entryId);
                }
            } else {
                selectedRows = selectedRows.filter(function(id) {
                    return id !== entryId;
                });
            }
            
            this.updateSelectionUI();
        },

        /**
         * Update selection UI for bulk operations
         */
        updateSelectionUI: function() {
            var count = selectedRows.length;
            var selectionInfo = $('.selection-info');
            
            if (count > 0) {
                if (selectionInfo.length === 0) {
                    $('.table-controls').prepend(
                        '<div class="selection-info alert alert-info d-flex align-items-center me-2">' +
                        '<span class="selection-count">' + count + '</span>' +
                        '<span class="ms-1 me-2">entries selected</span>' +
                        '<button class="btn btn-sm btn-primary me-1" id="bulk-export">Export Selected</button>' +
                        '<button class="btn btn-sm btn-outline-secondary" id="clear-selection">Clear</button>' +
                        '</div>'
                    );
                    
                    // Bind bulk actions
                    $('#bulk-export').on('click', function() {
                        this.exportSelected();
                    }.bind(this));
                    
                    $('#clear-selection').on('click', function() {
                        this.clearSelection();
                    }.bind(this));
                } else {
                    selectionInfo.find('.selection-count').text(count);
                }
            } else {
                selectionInfo.remove();
            }
        },

        /**
         * Apply filters to the table with cross-course context
         */
        applyFilters: function() {
            if (!table) {
                return;
            }

            // Clear existing filters
            table.clearFilter();

            // Get filter values
            var courseFilter = $('#filter-course').val();
            var activityFilter = $('#filter-activity').val();
            var searchFilter = $('#search-entries').val();

            // Apply course filter
            if (courseFilter) {
                table.addFilter("course_name", "=", courseFilter);
            }

            // Apply activity filter
            if (activityFilter) {
                table.addFilter("activity_name", "=", activityFilter);
            }

            // Apply search filter across multiple fields
            if (searchFilter) {
                table.addFilter([
                    {field: "market", type: "like", value: searchFilter},
                    {field: "industry", type: "like", value: searchFilter},
                    {field: "role", type: "like", value: searchFilter},
                    {field: "businessgoal", type: "like", value: searchFilter},
                    {field: "course_name", type: "like", value: searchFilter},
                    {field: "activity_name", type: "like", value: searchFilter},
                    {field: "username", type: "like", value: searchFilter}
                ]);
            }
        },

        /**
         * Clear all filters
         */
        clearFilters: function() {
            if (!table) {
                return;
            }

            table.clearFilter();
            $('#filter-course').val('');
            $('#filter-activity').val('');
            $('#search-entries').val('');
        },

        /**
         * Clear selection
         */
        clearSelection: function() {
            selectedRows = [];
            $('.entry-checkbox').prop('checked', false);
            $('.selection-info').remove();
        },

        /**
         * Export selected entries
         */
        exportSelected: function() {
            if (!table || selectedRows.length === 0) {
                return;
            }

            // Filter table data to selected entries
            var selectedData = table.getData().filter(function(row) {
                return selectedRows.indexOf(row.id) !== -1;
            });

            // Create temporary table for export
            var tempDiv = $('<div id="temp-export-table" style="display: none;"></div>');
            $('body').append(tempDiv);

            // eslint-disable-next-line no-undef
            var tempTable = new Tabulator("#temp-export-table", {
                data: selectedData,
                columns: this.prepareColumns([]), // Reuse column config
                layout: "fitData"
            });

            // Export and cleanup
            tempTable.download("csv", "selected-valuemap-entries.csv");
            setTimeout(function() {
                tempTable.destroy();
                tempDiv.remove();
            }, 1000);
        },

        /**
         * Show export options
         */
        showExportOptions: function() {
            if (!table) {
                return;
            }

            // Simple export all visible data
            table.download("csv", "valuemap-entries-" + new Date().toISOString().split('T')[0] + ".csv");
        },

        /**
         * Toggle fullscreen mode
         */
        toggleFullscreen: function() {
            var container = $('#valuemaps-container');
            container.toggleClass('fullscreen');

            if (table) {
                setTimeout(function() {
                    table.redraw();
                }, 100);
            }
        },

        /**
         * Show loading state
         */
        showLoadingState: function() {
            $('#loading-state').show();
            $('#table-state').hide();
            $('#empty-state').hide();
            $('#error-state').hide();
        },

        /**
         * Show table state
         */
        showTableState: function() {
            $('#loading-state').hide();
            $('#table-state').show();
            $('#empty-state').hide();
            $('#error-state').hide();
        },

        /**
         * Show empty state
         */
        showEmptyState: function() {
            $('#loading-state').hide();
            $('#table-state').hide();
            $('#empty-state').show();
            $('#error-state').hide();
        },

        /**
         * Show error state
         * @param {String} message Error message to display
         */
        showErrorState: function(message) {
            $('#loading-state').hide();
            $('#table-state').hide();
            $('#empty-state').hide();
            $('#error-state').show();
            $('#error-message').text(message || 'An error occurred while loading data.');
        }
    };
});
