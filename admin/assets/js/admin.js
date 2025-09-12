/**
 * 6Lab Tool - Admin Interface JavaScript
 * Modern admin interface interactions and real-time updates
 */

(function($) {
    'use strict';

    // Global admin object
    window.SixLabAdmin = {
        charts: {},
        refreshTimers: {},
        
        /**
         * Initialize admin interface
         */
        init: function() {
            this.initializePage();
            this.setupEventListeners();
            this.initializeCharts();
            this.setupAutoRefresh();
            this.initializeNotifications();
        },
        
        /**
         * Initialize current page
         */
        initializePage: function() {
            const currentPage = this.getCurrentPage();
            
            switch (currentPage) {
                case 'dashboard':
                    this.initializeDashboard();
                    break;
                case 'providers':
                    this.initializeProviders();
                    break;
                case 'ai-config':
                    this.initializeAIConfig();
                    break;
                case 'analytics':
                    this.initializeAnalytics();
                    break;
                case 'automation':
                    this.initializeAutomation();
                    break;
            }
        },
        
        /**
         * Get current admin page
         */
        getCurrentPage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page');
            
            if (page && page.startsWith('sixlab-')) {
                return page.replace('sixlab-', '');
            }
            
            return 'dashboard';
        },
        
        /**
         * Setup global event listeners
         */
        setupEventListeners: function() {
            // Global refresh button
            $(document).on('click', '[data-action="refresh"]', function(e) {
                e.preventDefault();
                SixLabAdmin.refreshDashboard();
            });
            
            // Widget refresh buttons
            $(document).on('click', '.widget-refresh', function(e) {
                e.preventDefault();
                const widget = $(this).data('widget');
                if (widget) {
                    SixLabAdmin.refreshWidget(widget);
                }
            });
            
            // Modal handlers
            $(document).on('click', '[data-modal-toggle]', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal-toggle');
                SixLabAdmin.toggleModal(modalId);
            });
            
            // Form submissions (exclude template forms)
            $(document).on('submit', '.sixlab-form:not([method="post"])', function(e) {
                e.preventDefault();
                SixLabAdmin.handleFormSubmission($(this));
            });
            
            // Tooltips
            $('[data-tooltip]').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
            });
            
            // Shortcode copy functionality
            $(document).on('click', '.sixlab-copy-shortcode', function(e) {
                e.preventDefault();
                SixLabAdmin.copyShortcode($(this));
            });
            
            // Template management handlers
            $(document).on('click', '.sixlab-delete-template', function(e) {
                e.preventDefault();
                SixLabAdmin.deleteTemplate($(this));
            });
            
            $(document).on('click', '.sixlab-activate-template', function(e) {
                e.preventDefault();
                SixLabAdmin.toggleTemplateStatus($(this), 'activate');
            });
            
            $(document).on('click', '.sixlab-deactivate-template', function(e) {
                e.preventDefault();
                SixLabAdmin.toggleTemplateStatus($(this), 'deactivate');
            });
            
            $(document).on('click', '.sixlab-duplicate-template', function(e) {
                e.preventDefault();
                SixLabAdmin.duplicateTemplate($(this));
            });
        },
        
        /**
         * Initialize dashboard
         */
        initializeDashboard: function() {
            this.loadDashboardData();
            this.initializeCharts();
            this.setupDashboardRefresh();
        },
        
        /**
         * Initialize providers page
         */
        initializeProviders: function() {
            this.loadProvidersList();
            this.setupProviderActions();
        },
        
        /**
         * Initialize AI config page
         */
        initializeAIConfig: function() {
            this.loadAIProviders();
            this.setupAIConfigActions();
        },
        
        /**
         * Initialize analytics page
         */
        initializeAnalytics: function() {
            this.loadAnalyticsData();
            this.initializeAnalyticsCharts();
        },
        
        /**
         * Initialize automation page
         */
        initializeAutomation: function() {
            this.loadAutomationScripts();
            this.setupAutomationActions();
        },
        
        /**
         * Initialize charts
         */
        initializeCharts: function() {
            const currentPage = this.getCurrentPage();
            
            if (currentPage === 'dashboard') {
                this.initializeDashboardCharts();
            } else if (currentPage === 'analytics') {
                this.initializeAnalyticsCharts();
            }
        },
        
        /**
         * Initialize dashboard charts
         */
        initializeDashboardCharts: function() {
            // Sessions Chart
            const sessionsCtx = document.getElementById('sessions-chart');
            if (sessionsCtx) {
                this.charts.sessions = this.createSessionsChart(sessionsCtx);
            }
            
            // AI Usage Chart
            const aiCtx = document.getElementById('ai-usage-chart');
            if (aiCtx) {
                this.charts.aiUsage = this.createAIUsageChart(aiCtx);
            }
            
            // Provider Health Chart
            const healthCtx = document.getElementById('provider-health-chart');
            if (healthCtx) {
                this.charts.providerHealth = this.createProviderHealthChart(healthCtx);
            }
        },
        
        /**
         * Create sessions chart
         */
        createSessionsChart: function(ctx) {
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Active Sessions',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(203, 213, 225, 0.5)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Create AI usage chart
         */
        createAIUsageChart: function(ctx) {
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Interactions',
                        data: [],
                        backgroundColor: '#10b981',
                        borderColor: '#059669',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(203, 213, 225, 0.5)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Setup auto refresh
         */
        setupAutoRefresh: function() {
            if (typeof sixlabAdmin === 'undefined') return;
            
            Object.keys(sixlabAdmin.refreshIntervals || {}).forEach(widget => {
                this.refreshTimers[widget] = setInterval(() => {
                    this.refreshWidget(widget);
                }, sixlabAdmin.refreshIntervals[widget]);
            });
        },
        
        /**
         * Setup dashboard refresh
         */
        setupDashboardRefresh: function() {
            // Refresh dashboard every 30 seconds
            this.refreshTimers.dashboard = setInterval(() => {
                this.refreshDashboard();
            }, 30000);
        },
        
        /**
         * Refresh dashboard
         */
        refreshDashboard: function() {
            this.showLoadingState();
            
            $.post(ajaxurl, {
                action: 'sixlab_dashboard_refresh',
                nonce: sixlabAdmin.nonce
            })
            .done(response => {
                if (response.success) {
                    this.updateDashboardData(response.data);
                    this.showNotification('Dashboard refreshed', 'success');
                } else {
                    this.showNotification('Failed to refresh dashboard', 'error');
                }
            })
            .fail(() => {
                this.showNotification('Network error occurred', 'error');
            })
            .always(() => {
                this.hideLoadingState();
            });
        },
        
        /**
         * Refresh specific widget
         */
        refreshWidget: function(widgetType) {
            const $widget = $(`.${widgetType}-widget`);
            $widget.addClass('loading');
            
            $.post(ajaxurl, {
                action: 'sixlab_get_widget_data',
                widget: widgetType,
                nonce: sixlabAdmin.nonce
            })
            .done(response => {
                if (response.success) {
                    this.updateWidgetData(widgetType, response.data);
                } else {
                    this.showNotification(`Failed to refresh ${widgetType} widget`, 'error');
                }
            })
            .fail(() => {
                this.showNotification('Network error occurred', 'error');
            })
            .always(() => {
                $widget.removeClass('loading');
            });
        },
        
        /**
         * Update dashboard data
         */
        updateDashboardData: function(data) {
            // Update stat cards
            if (data.active_sessions) {
                $('#active-sessions-count').text(data.active_sessions.total);
                if (this.charts.sessions) {
                    this.updateSessionsChart(data.active_sessions.chart_data);
                }
            }
            
            if (data.provider_health) {
                this.updateProviderHealthGrid(data.provider_health);
            }
            
            if (data.ai_usage) {
                this.updateAIUsageData(data.ai_usage);
                if (this.charts.aiUsage) {
                    this.updateAIUsageChart(data.ai_usage);
                }
            }
            
            if (data.recent_completions) {
                this.updateRecentCompletions(data.recent_completions);
            }
        },
        
        /**
         * Update widget data
         */
        updateWidgetData: function(widgetType, data) {
            switch (widgetType) {
                case 'active_sessions':
                    $('#active-sessions-count').text(data.total);
                    if (this.charts.sessions) {
                        this.updateSessionsChart(data.chart_data);
                    }
                    break;
                    
                case 'provider_health':
                    this.updateProviderHealthGrid(data);
                    break;
                    
                case 'ai_usage':
                    this.updateAIUsageData(data);
                    if (this.charts.aiUsage) {
                        this.updateAIUsageChart(data);
                    }
                    break;
                    
                case 'recent_completions':
                    this.updateRecentCompletions(data);
                    break;
            }
        },
        
        /**
         * Update sessions chart
         */
        updateSessionsChart: function(chartData) {
            if (!this.charts.sessions || !chartData) return;
            
            const labels = chartData.map(d => d.hour + ':00');
            const data = chartData.map(d => parseInt(d.count));
            
            this.charts.sessions.data.labels = labels;
            this.charts.sessions.data.datasets[0].data = data;
            this.charts.sessions.update('none');
        },
        
        /**
         * Update AI usage chart
         */
        updateAIUsageChart: function(usageData) {
            if (!this.charts.aiUsage || !usageData) return;
            
            const labels = usageData.map(d => d.date);
            const data = usageData.map(d => parseInt(d.interactions));
            
            this.charts.aiUsage.data.labels = labels;
            this.charts.aiUsage.data.datasets[0].data = data;
            this.charts.aiUsage.update('none');
        },
        
        /**
         * Update provider health grid
         */
        updateProviderHealthGrid: function(providers) {
            const $grid = $('#provider-health-grid');
            $grid.empty();
            
            if (!providers || providers.length === 0) {
                $grid.html(`
                    <div class="no-data">
                        <p>No providers configured yet.</p>
                        <a href="${sixlabAdmin.urls.providers}" class="button">Configure Providers</a>
                    </div>
                `);
                return;
            }
            
            providers.forEach(provider => {
                const $item = $(`
                    <div class="health-item ${provider.health_status}">
                        <div class="health-status">
                            <span class="status-indicator"></span>
                            <strong>${provider.display_name || provider.name}</strong>
                        </div>
                        <div class="health-details">
                            <span class="provider-type">${provider.type}</span>
                            ${provider.last_health_check ? 
                                `<span class="last-check">Last check: ${this.timeAgo(provider.last_health_check)}</span>` : 
                                '<span class="last-check">Never checked</span>'
                            }
                        </div>
                        ${provider.health_message ? 
                            `<div class="health-message">${provider.health_message}</div>` : 
                            ''
                        }
                    </div>
                `);
                $grid.append($item);
            });
        },
        
        /**
         * Update AI usage data
         */
        updateAIUsageData: function(usageData) {
            if (!usageData) return;
            
            const totalTokens = usageData.reduce((sum, d) => sum + parseInt(d.total_tokens || 0), 0);
            const totalCost = usageData.reduce((sum, d) => sum + parseFloat(d.total_cost || 0), 0);
            
            $('#total-tokens').text(this.formatNumber(totalTokens));
            $('#total-cost').text('$' + totalCost.toFixed(2));
        },
        
        /**
         * Update recent completions
         */
        updateRecentCompletions: function(completions) {
            const $list = $('#recent-completions');
            $list.empty();
            
            if (!completions || completions.length === 0) {
                $list.html('<div class="no-data"><p>No recent completions.</p></div>');
                return;
            }
            
            completions.forEach(completion => {
                const score = Math.round((completion.score / completion.max_score) * 100);
                const $item = $(`
                    <div class="completion-item">
                        <div class="completion-info">
                            <strong>${completion.display_name}</strong>
                            <span class="lab-provider">${completion.provider}</span>
                        </div>
                        <div class="completion-score">
                            <span class="score">${score}%</span>
                            <span class="timestamp">${this.timeAgo(completion.completed_at)}</span>
                        </div>
                    </div>
                `);
                $list.append($item);
            });
        },
        
        /**
         * Handle form submission
         */
        handleFormSubmission: function($form) {
            const formData = new FormData($form[0]);
            const action = $form.data('action');
            
            formData.append('action', action);
            formData.append('nonce', sixlabAdmin.nonce);
            
            this.showLoadingState();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(response => {
                if (response.success) {
                    this.showNotification(response.data.message || 'Settings saved successfully', 'success');
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                } else {
                    this.showNotification(response.data.message || 'An error occurred', 'error');
                }
            })
            .fail(() => {
                this.showNotification('Network error occurred', 'error');
            })
            .always(() => {
                this.hideLoadingState();
            });
        },
        
        /**
         * Show loading state
         */
        showLoadingState: function() {
            $('.sixlab-admin-wrap').addClass('loading');
        },
        
        /**
         * Hide loading state
         */
        hideLoadingState: function() {
            $('.sixlab-admin-wrap').removeClass('loading');
        },
        
        /**
         * Initialize notifications
         */
        initializeNotifications: function() {
            // Create notification container if it doesn't exist
            if (!$('#sixlab-notifications').length) {
                $('body').append('<div id="sixlab-notifications"></div>');
            }
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info', duration = 5000) {
            const $notification = $(`
                <div class="sixlab-notification ${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="$(this).parent().remove()">&times;</button>
                </div>
            `);
            
            $('#sixlab-notifications').append($notification);
            
            // Auto-remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    $notification.fadeOut(() => $notification.remove());
                }, duration);
            }
        },
        
        /**
         * Toggle modal
         */
        toggleModal: function(modalId) {
            const $modal = $('#' + modalId);
            if ($modal.length) {
                $modal.toggle();
            }
        },
        
        /**
         * Utility: Format large numbers
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },
        
        /**
         * Utility: Time ago formatter
         */
        timeAgo: function(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) {
                return 'just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
            } else {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days} day${days !== 1 ? 's' : ''} ago`;
            }
        },
        
        /**
         * Cleanup timers when page unloads
         */
        cleanup: function() {
            Object.values(this.refreshTimers).forEach(timer => {
                clearInterval(timer);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SixLabAdmin.init();
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        SixLabAdmin.cleanup();
    });

})(jQuery);

// Provider Management JavaScript
(function($) {
    'use strict';

    // Extend SixLabAdmin with provider management functions
    $.extend(window.SixLabAdmin, {
        
        /**
         * Setup provider actions
         */
        setupProviderActions: function() {
            this.setupProviderTestConnection();
            this.setupProviderDelete();
            this.setupProviderSetDefault();
            this.setupProviderFormValidation();
        },
        
        /**
         * Setup test connection functionality
         */
        setupProviderTestConnection: function() {
            // Test single provider configuration
            $(document).on('click', '.sixlab-test-single-provider', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const providerId = $button.data('provider-id');
                const providerType = $button.data('provider-type');
                
                SixLabAdmin.testProviderConnection($button, providerId, providerType);
            });
            
            // Test default provider for type
            $(document).on('click', '.sixlab-test-provider', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const providerType = $button.data('provider');
                
                SixLabAdmin.testDefaultProviderConnection($button, providerType);
            });
        },
        
        /**
         * Test individual provider connection
         */
        testProviderConnection: function($button, providerId, providerType) {
            const originalText = $button.text();
            const $resultDiv = $button.closest('.sixlab-provider-instance').find('.sixlab-test-result');
            
            // Remove existing result div
            $resultDiv.remove();
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: sixlab_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_test_provider_by_id',
                    provider_id: providerId,
                    nonce: sixlab_admin.nonce
                },
                success: function(response) {
                    SixLabAdmin.showTestResult($button, response, originalText);
                },
                error: function() {
                    SixLabAdmin.showTestResult($button, {
                        success: false,
                        data: { message: 'Connection test failed. Please try again.' }
                    }, originalText);
                }
            });
        },
        
        /**
         * Test default provider connection
         */
        testDefaultProviderConnection: function($button, providerType) {
            const originalText = $button.text();
            const $resultDiv = $button.closest('.sixlab-provider-card').find('.sixlab-test-result');
            
            // Remove existing result div
            $resultDiv.remove();
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Testing...');
            
            // Get current configuration from form if available
            const config = {};
            $(`input[name^="provider_config["], select[name^="provider_config["], textarea[name^="provider_config["]`).each(function() {
                const name = $(this).attr('name');
                const matches = name.match(/provider_config\[([^\]]+)\]/);
                if (matches) {
                    const fieldName = matches[1];
                    if ($(this).attr('type') === 'checkbox') {
                        config[fieldName] = $(this).is(':checked') ? '1' : '';
                    } else {
                        config[fieldName] = $(this).val();
                    }
                }
            });
            
            $.ajax({
                url: sixlab_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_test_provider',
                    provider_type: providerType,
                    config: config,
                    nonce: sixlab_admin.nonce
                },
                success: function(response) {
                    SixLabAdmin.showTestResult($button, response, originalText);
                },
                error: function() {
                    SixLabAdmin.showTestResult($button, {
                        success: false,
                        data: { message: 'Connection test failed. Please try again.' }
                    }, originalText);
                }
            });
        },
        
        /**
         * Show test result
         */
        showTestResult: function($button, response, originalText) {
            $button.prop('disabled', false).text(originalText);
            
            const $container = $button.closest('.sixlab-provider-instance, .sixlab-provider-card');
            let $resultDiv = $container.find('.sixlab-test-result');
            
            if ($resultDiv.length === 0) {
                $resultDiv = $('<div class="sixlab-test-result"></div>');
                $button.closest('.sixlab-instance-actions, .sixlab-provider-actions').after($resultDiv);
            }
            
            if (response.success) {
                const result = response.data;
                $resultDiv.removeClass('error').addClass('success')
                         .html('<strong>✓</strong> ' + result.message);
            } else {
                const errorMessage = response.data && response.data.message ? response.data.message : 'Connection test failed';
                $resultDiv.removeClass('success').addClass('error')
                         .html('<strong>✗</strong> ' + errorMessage);
            }
            
            $resultDiv.show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $resultDiv.fadeOut();
            }, 5000);
        },
        
        /**
         * Setup provider deletion
         */
        setupProviderDelete: function() {
            $(document).on('click', '.sixlab-delete-provider', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const providerId = $button.data('provider-id');
                const providerName = $button.data('provider-name');
                
                if (confirm(`Are you sure you want to delete the provider configuration "${providerName}"? This action cannot be undone.`)) {
                    SixLabAdmin.deleteProvider(providerId, $button);
                }
            });
        },
        
        /**
         * Delete provider configuration
         */
        deleteProvider: function(providerId, $button) {
            const $instance = $button.closest('.sixlab-provider-instance');
            
            $button.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: sixlab_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_delete_provider',
                    provider_id: providerId,
                    nonce: sixlab_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $instance.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if this was the last provider
                            if ($('.sixlab-provider-instance').length === 0) {
                                location.reload(); // Reload to show "no configurations" message
                            }
                        });
                    } else {
                        alert('Failed to delete provider: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert('Failed to delete provider. Please try again.');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        },
        
        /**
         * Setup set as default functionality
         */
        setupProviderSetDefault: function() {
            $(document).on('click', '.sixlab-set-default-provider', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const providerId = $button.data('provider-id');
                
                SixLabAdmin.setDefaultProvider(providerId, $button);
            });
        },
        
        /**
         * Set provider as default
         */
        setDefaultProvider: function(providerId, $button) {
            $button.prop('disabled', true).text('Setting...');
            
            $.ajax({
                url: sixlab_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_set_default_provider',
                    provider_id: providerId,
                    nonce: sixlab_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove all existing default badges
                        $('.badge.default').remove();
                        
                        // Add default badge to current provider
                        const $badges = $button.closest('.sixlab-provider-instance').find('.sixlab-instance-badges');
                        $badges.prepend('<span class="badge default">Default</span>');
                        
                        // Hide the "Set as Default" button
                        $button.hide();
                        
                        // Show other "Set as Default" buttons
                        $('.sixlab-set-default-provider').not($button).show();
                    } else {
                        alert('Failed to set as default: ' + (response.data.message || 'Unknown error'));
                    }
                    $button.prop('disabled', false).text('Set as Default');
                },
                error: function() {
                    alert('Failed to set as default. Please try again.');
                    $button.prop('disabled', false).text('Set as Default');
                }
            });
        },
        
        /**
         * Setup form validation
         */
        setupProviderFormValidation: function() {
            $('#sixlab-provider-form').on('submit', function(e) {
                const $form = $(this);
                let isValid = true;
                
                // Check required fields
                $form.find('input[required], select[required], textarea[required]').each(function() {
                    const $field = $(this);
                    if (!$field.val().trim()) {
                        $field.addClass('error');
                        isValid = false;
                    } else {
                        $field.removeClass('error');
                    }
                });
                
                // Validate URLs
                $form.find('input[type="url"]').each(function() {
                    const $field = $(this);
                    const url = $field.val().trim();
                    
                    if (url && !SixLabAdmin.isValidUrl(url)) {
                        $field.addClass('error');
                        isValid = false;
                    } else {
                        $field.removeClass('error');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields with valid values.');
                }
            });
        },
        
        /**
         * Validate URL
         */
        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },
        
        /**
         * Copy shortcode to clipboard
         */
        copyShortcode: function($button) {
            const shortcode = $button.data('shortcode');
            
            if (!shortcode) {
                return;
            }
            
            // Try to use the modern clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    SixLabAdmin.showCopySuccess($button);
                }).catch(function() {
                    // Fallback to older method
                    SixLabAdmin.fallbackCopyShortcode(shortcode, $button);
                });
            } else {
                // Fallback for older browsers
                SixLabAdmin.fallbackCopyShortcode(shortcode, $button);
            }
        },
        
        /**
         * Fallback copy method for older browsers
         */
        fallbackCopyShortcode: function(shortcode, $button) {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                document.execCommand('copy');
                SixLabAdmin.showCopySuccess($button);
            } catch (err) {
                console.error('Could not copy shortcode: ', err);
                alert('Shortcode: ' + shortcode + '\n\nPlease copy manually.');
            }
            
            $temp.remove();
        },
        
        /**
         * Show copy success animation
         */
        showCopySuccess: function($button) {
            const originalContent = $button.html();
            
            $button.addClass('sixlab-copy-success');
            
            // Show a brief toast notification
            this.showToast('Shortcode copied to clipboard!', 'success');
            
            setTimeout(function() {
                $button.removeClass('sixlab-copy-success');
            }, 1500);
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type = 'info') {
            const $toast = $('<div class="sixlab-toast sixlab-toast-' + type + '">' + message + '</div>');
            
            // Add to page if container doesn't exist
            if ($('.sixlab-toast-container').length === 0) {
                $('body').append('<div class="sixlab-toast-container"></div>');
            }
            
            $('.sixlab-toast-container').append($toast);
            
            // Animate in
            setTimeout(function() {
                $toast.addClass('sixlab-toast-show');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(function() {
                $toast.removeClass('sixlab-toast-show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        },
        
        /**
         * Delete template
         */
        deleteTemplate: function($button) {
            const templateId = $button.data('template-id');
            const templateName = $button.data('template-name');
            
            if (!confirm('Are you sure you want to permanently delete the template "' + templateName + '"? This action cannot be undone.')) {
                return;
            }
            
            $button.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sixlab_delete_template',
                    template_id: templateId,
                    nonce: sixlabAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                        SixLabAdmin.showToast('Template deleted successfully', 'success');
                    } else {
                        alert('Error deleting template: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert('Network error occurred while deleting template');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        },
        
        /**
         * Toggle template status (activate/deactivate)
         */
        toggleTemplateStatus: function($button, action) {
            const templateId = $button.data('template-id');
            const isActivating = action === 'activate';
            
            $button.prop('disabled', true).text(isActivating ? 'Activating...' : 'Deactivating...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sixlab_toggle_template_status',
                    template_id: templateId,
                    status: action,
                    nonce: sixlabAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to update the interface
                    } else {
                        alert('Error updating template status: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text(isActivating ? 'Activate' : 'Deactivate');
                    }
                },
                error: function() {
                    alert('Network error occurred while updating template status');
                    $button.prop('disabled', false).text(isActivating ? 'Activate' : 'Deactivate');
                }
            });
        },
        
        /**
         * Duplicate template
         */
        duplicateTemplate: function($button) {
            const templateId = $button.data('template-id');
            
            $button.prop('disabled', true).text('Duplicating...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sixlab_duplicate_template',
                    template_id: templateId,
                    nonce: sixlabAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to show the duplicated template
                    } else {
                        alert('Error duplicating template: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text('Duplicate');
                    }
                },
                error: function() {
                    alert('Network error occurred while duplicating template');
                    $button.prop('disabled', false).text('Duplicate');
                }
            });
        }
    });

})(jQuery);

// Global functions for onclick handlers
window.refreshDashboard = function() {
    SixLabAdmin.refreshDashboard();
};

window.refreshWidget = function(widgetType) {
    SixLabAdmin.refreshWidget(widgetType);
};

window.updateAIChart = function(timeframe) {
    // Implementation for updating AI chart timeframe
    console.log('Updating AI chart for timeframe:', timeframe);
};

// Initialize when document is ready
$(document).ready(function() {
    SixLabAdmin.init();
});
