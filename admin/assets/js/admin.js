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
            
            // Form submissions
            $(document).on('submit', '.sixlab-form', function(e) {
                e.preventDefault();
                SixLabAdmin.handleFormSubmission($(this));
            });
            
            // Tooltips
            $('[data-tooltip]').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
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
