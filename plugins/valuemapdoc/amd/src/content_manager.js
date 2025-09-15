/**
 * Content manager for ValueMapDoc AI Tools
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    
    return {
        init: function() {
            this.bindEvents();
            this.initializeFilters();
        },
        
        bindEvents: function() {
            // Search functionality
            $('#content-search').on('input', this.handleSearch.bind(this));
            
            // Filter functionality
            $('#template-filter, #status-filter').on('change', this.handleFilter.bind(this));
            
            // Toggle panels
            $('#search-toggle, #filter-toggle').on('click', function() {
                $('#search-filter-panel').slideToggle();
            });
            
            // Accordion improvements
            $('.collapse').on('show.bs.collapse', function() {
                $(this).prev().find('.fa-chevron-down')
                    .removeClass('fa-chevron-down')
                    .addClass('fa-chevron-up');
            });
            
            $('.collapse').on('hide.bs.collapse', function() {
                $(this).prev().find('.fa-chevron-up')
                    .removeClass('fa-chevron-up')
                    .addClass('fa-chevron-down');
            });
            
            // Content item hover effects
            $('.content-item').hover(
                function() {
                    $(this).addClass('shadow-sm').css('transform', 'translateY(-2px)');
                },
                function() {
                    $(this).removeClass('shadow-sm').css('transform', 'translateY(0)');
                }
            );
        },
        
        handleSearch: function() {
            var searchTerm = $('#content-search').val().toLowerCase();
            
            $('.content-item').each(function() {
                var contentText = $(this).text().toLowerCase();
                var parentColumn = $(this).closest('.col-md-6');
                
                if (searchTerm === '' || contentText.includes(searchTerm)) {
                    parentColumn.show();
                } else {
                    parentColumn.hide();
                }
            });
            
            this.updateActivityVisibility();
        },
        
        handleFilter: function() {
            var templateFilter = $('#template-filter').val();
            var statusFilter = $('#status-filter').val();
            
            $('.content-item').each(function() {
                var show = true;
                var parentColumn = $(this).closest('.col-md-6');
                
                if (templateFilter) {
                    var templateText = $(this).find('.card-text:first').text();
                    if (!templateText.toLowerCase().includes(templateFilter.toLowerCase())) {
                        show = false;
                    }
                }
                
                if (statusFilter) {
                    var statusBadge = $(this).find('.badge').first();
                    if (!statusBadge.text().toLowerCase().includes(statusFilter.toLowerCase())) {
                        show = false;
                    }
                }
                
                if (show) {
                    parentColumn.show();
                } else {
                    parentColumn.hide();
                }
            });
            
            this.updateActivityVisibility();
        },
        
        updateActivityVisibility: function() {
            // Hide activities with no visible content
            $('.activity-section').each(function() {
                var visibleContent = $(this).find('.col-md-6:visible').length;
                if (visibleContent === 0) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
            
            // Hide courses with no visible activities
            $('.course-card').each(function() {
                var visibleActivities = $(this).find('.activity-section:visible').length;
                if (visibleActivities === 0) {
                    $(this).hide();
                    // Also hide from accordion
                    var courseId = $(this).find('[data-bs-target]').attr('data-bs-target').replace('#collapse-', '');
                    $('#collapse-' + courseId).removeClass('show');
                } else {
                    $(this).show();
                }
            });
        },
        
        initializeFilters: function() {
            // Populate template filter options
            var templates = new Set();
            $('.content-item').each(function() {
                var templateText = $(this).find('.card-text:first').text();
                var template = templateText.replace('Template: ', '').trim();
                if (template) {
                    templates.add(template);
                }
            });
            
            templates.forEach(function(template) {
                $('#template-filter').append(
                    $('<option>').val(template).text(template)
                );
            });
        },
        
        refreshContent: function() {
            // AJAX call to refresh content
            var promises = Ajax.call([{
                methodname: 'aitoolsub_valuemapdoc_get_user_content_global',
                args: {}
            }]);
            
            promises[0].done(function(data) {
                // Update page content
                location.reload(); // Simple refresh for now
            }).fail(function(error) {
                Notification.exception(error);
            });
        }
    };
});