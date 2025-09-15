/**
 * AI Tools Dashboard JavaScript
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    return {
        init: function() {
            this.bindEvents();
            this.initializeBlocks();
        },

        bindEvents: function() {
            // Tool card hover effects
            $('.tool-card').hover(
                function() {
                    $(this).addClass('shadow-lg').css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
                }
            );

            // Dashboard block interactions
            $('.dashboard-block').on('click', '.refresh-block', this.refreshBlock.bind(this));

            // Statistics animation
            this.animateStatistics();
        },

        initializeBlocks: function() {
            // Initialize any interactive blocks
            $('.dashboard-block').each(function() {
                var plugin = $(this).data('plugin');
                //var blockKey = $(this).data('block');

                // Plugin-specific initialization could go here
                if (plugin === 'aitoolsub_valuemapdoc') {
                    // Initialize ValueMapDoc specific features
                }
            });
        },

        refreshBlock: function(event) {
            var block = $(event.target).closest('.dashboard-block');
            var plugin = block.data('plugin');
            var blockKey = block.data('block');

            // Add loading state
            block.addClass('loading');

            // Refresh block content via AJAX
            var promises = Ajax.call([{
                methodname: 'local_aitools_refresh_block',
                args: {
                    plugin: plugin,
                    block_key: blockKey
                }
            }]);

            promises[0].done(function(data) {
                // Update block content
                block.find('.card-body').html(data.content);
                block.removeClass('loading');
            }).fail(function(error) {
                Notification.exception(error);
                block.removeClass('loading');
            });
        },

        animateStatistics: function() {
            // Animate statistics cards on load
            $('.card h3').each(function() {
                var $this = $(this);
                var countTo = parseInt($this.text());

                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(countTo);
                    }
                });
            });
        }
    };
});