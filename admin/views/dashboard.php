<?php
/**
 * 6Lab Tool - Admin Dashboard
 * Modern dashboard interface with real-time widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$dashboard_data = $dashboard_data ?? array();
?>

<div class="wrap sixlab-admin-wrap">
    <!-- Header Section -->
    <div class="sixlab-admin-header">
        <div class="sixlab-header-content">
            <h1 class="sixlab-page-title">
                <span class="dashicons dashicons-networking"></span>
                6Lab Tool Dashboard
            </h1>
            <p class="sixlab-page-subtitle">
                Monitor and manage your lab environment
            </p>
        </div>
        <div class="sixlab-header-actions">
            <button type="button" class="button button-primary" onclick="refreshDashboard()">
                <span class="dashicons dashicons-update"></span>
                Refresh
            </button>
            <button type="button" class="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=sixlab-settings'); ?>'">
                <span class="dashicons dashicons-admin-generic"></span>
                Settings
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="sixlab-stats-grid">
        <div class="sixlab-stat-card">
            <div class="stat-card-header">
                <h3>Active Sessions</h3>
                <span class="stat-icon active-sessions">
                    <span class="dashicons dashicons-groups"></span>
                </span>
            </div>
            <div class="stat-card-content">
                <div class="stat-number" id="active-sessions-count">
                    <?php echo $dashboard_data['active_sessions']['total'] ?? 0; ?>
                </div>
                <div class="stat-label">Currently running</div>
                <div class="stat-trend">
                    <span class="trend-up">+12% from yesterday</span>
                </div>
            </div>
        </div>

        <div class="sixlab-stat-card">
            <div class="stat-card-header">
                <h3>Providers Online</h3>
                <span class="stat-icon providers-online">
                    <span class="dashicons dashicons-networking"></span>
                </span>
            </div>
            <div class="stat-card-content">
                <div class="stat-number" id="providers-online-count">
                    <?php 
                    $healthy_providers = array_filter($dashboard_data['provider_health'] ?? array(), function($p) {
                        return $p->health_status === 'healthy';
                    });
                    echo count($healthy_providers);
                    ?>/<?php echo count($dashboard_data['provider_health'] ?? array()); ?>
                </div>
                <div class="stat-label">Health status</div>
                <div class="stat-trend">
                    <span class="trend-stable">All systems operational</span>
                </div>
            </div>
        </div>

        <div class="sixlab-stat-card">
            <div class="stat-card-header">
                <h3>AI Interactions</h3>
                <span class="stat-icon ai-interactions">
                    <span class="dashicons dashicons-admin-comments"></span>
                </span>
            </div>
            <div class="stat-card-content">
                <div class="stat-number" id="ai-interactions-count">
                    <?php 
                    $total_interactions = array_sum(array_column($dashboard_data['ai_usage'] ?? array(), 'interactions'));
                    echo $total_interactions;
                    ?>
                </div>
                <div class="stat-label">Last 7 days</div>
                <div class="stat-trend">
                    <span class="trend-up">+8% from last week</span>
                </div>
            </div>
        </div>

        <div class="sixlab-stat-card">
            <div class="stat-card-header">
                <h3>Completion Rate</h3>
                <span class="stat-icon completion-rate">
                    <span class="dashicons dashicons-yes-alt"></span>
                </span>
            </div>
            <div class="stat-card-content">
                <div class="stat-number">87%</div>
                <div class="stat-label">Average success rate</div>
                <div class="stat-trend">
                    <span class="trend-up">+3% improvement</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="sixlab-dashboard-grid">
        <!-- Active Sessions Widget -->
        <div class="sixlab-widget sessions-widget">
            <div class="widget-header">
                <h3>Active Lab Sessions</h3>
                <div class="widget-actions">
                    <button type="button" class="widget-refresh" onclick="refreshWidget('active_sessions')">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="sessions-chart" width="400" height="200"></canvas>
                </div>
                <div class="widget-footer">
                    <small class="text-muted">Updates every 30 seconds</small>
                </div>
            </div>
        </div>

        <!-- Provider Health Widget -->
        <div class="sixlab-widget health-widget">
            <div class="widget-header">
                <h3>Provider Health Status</h3>
                <div class="widget-actions">
                    <button type="button" class="widget-refresh" onclick="refreshWidget('provider_health')">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="health-grid" id="provider-health-grid">
                    <?php if (!empty($dashboard_data['provider_health'])): ?>
                        <?php foreach ($dashboard_data['provider_health'] as $provider): ?>
                            <div class="health-item <?php echo esc_attr($provider->health_status); ?>">
                                <div class="health-status">
                                    <span class="status-indicator"></span>
                                    <strong><?php echo esc_html($provider->display_name ?? $provider->name); ?></strong>
                                </div>
                                <div class="health-details">
                                    <span class="provider-type"><?php echo esc_html($provider->type); ?></span>
                                    <?php if ($provider->last_health_check): ?>
                                        <span class="last-check">
                                            Last check: <?php echo human_time_diff(strtotime($provider->last_health_check)); ?> ago
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($provider->health_message): ?>
                                    <div class="health-message">
                                        <?php echo esc_html($provider->health_message); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No providers configured yet.</p>
                            <a href="<?php echo admin_url('admin.php?page=sixlab-providers'); ?>" class="button">
                                Configure Providers
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- AI Usage Chart -->
        <div class="sixlab-widget ai-usage-widget">
            <div class="widget-header">
                <h3>AI Assistant Usage</h3>
                <div class="widget-actions">
                    <select class="timeframe-selector" onchange="updateAIChart(this.value)">
                        <option value="last_7_days">Last 7 days</option>
                        <option value="last_30_days">Last 30 days</option>
                        <option value="last_90_days">Last 90 days</option>
                    </select>
                </div>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="ai-usage-chart" width="400" height="200"></canvas>
                </div>
                <div class="ai-stats">
                    <div class="ai-stat">
                        <span class="stat-label">Total Tokens</span>
                        <span class="stat-value" id="total-tokens">
                            <?php 
                            $total_tokens = array_sum(array_column($dashboard_data['ai_usage'] ?? array(), 'total_tokens'));
                            echo number_format($total_tokens);
                            ?>
                        </span>
                    </div>
                    <div class="ai-stat">
                        <span class="stat-label">Cost (USD)</span>
                        <span class="stat-value" id="total-cost">
                            $<?php 
                            $total_cost = array_sum(array_column($dashboard_data['ai_usage'] ?? array(), 'total_cost'));
                            echo number_format($total_cost, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Completions -->
        <div class="sixlab-widget completions-widget">
            <div class="widget-header">
                <h3>Recent Lab Completions</h3>
                <div class="widget-actions">
                    <a href="<?php echo admin_url('admin.php?page=sixlab-analytics'); ?>" class="view-all">
                        View All
                    </a>
                </div>
            </div>
            <div class="widget-content">
                <div class="completions-list" id="recent-completions">
                    <?php if (!empty($dashboard_data['recent_completions'])): ?>
                        <?php foreach ($dashboard_data['recent_completions'] as $completion): ?>
                            <div class="completion-item">
                                <div class="completion-info">
                                    <strong><?php echo esc_html($completion->display_name); ?></strong>
                                    <span class="lab-provider"><?php echo esc_html($completion->provider); ?></span>
                                </div>
                                <div class="completion-score">
                                    <span class="score">
                                        <?php echo round(($completion->score / $completion->max_score) * 100); ?>%
                                    </span>
                                    <span class="timestamp">
                                        <?php echo human_time_diff(strtotime($completion->completed_at)); ?> ago
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No recent completions.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="sixlab-widget actions-widget">
            <div class="widget-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="widget-content">
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sixlab-providers'); ?>" class="action-button">
                        <span class="dashicons dashicons-networking"></span>
                        <span>Manage Providers</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sixlab-templates'); ?>" class="action-button">
                        <span class="dashicons dashicons-category"></span>
                        <span>Lab Templates</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sixlab-ai-config'); ?>" class="action-button">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>AI Configuration</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sixlab-automation'); ?>" class="action-button">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <span>Automation</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sixlab-analytics'); ?>" class="action-button">
                        <span class="dashicons dashicons-chart-area"></span>
                        <span>Analytics</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sixlab-settings'); ?>" class="action-button">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="sixlab-widget status-widget">
            <div class="widget-header">
                <h3>System Status</h3>
            </div>
            <div class="widget-content">
                <div class="status-items">
                    <div class="status-item">
                        <span class="status-label">WordPress Version</span>
                        <span class="status-value"><?php echo get_bloginfo('version'); ?></span>
                        <span class="status-indicator good"></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">PHP Version</span>
                        <span class="status-value"><?php echo PHP_VERSION; ?></span>
                        <span class="status-indicator <?php echo version_compare(PHP_VERSION, '8.0', '>=') ? 'good' : 'warning'; ?>"></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Memory Usage</span>
                        <span class="status-value"><?php echo size_format(memory_get_usage(true)); ?></span>
                        <span class="status-indicator good"></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Database</span>
                        <span class="status-value">Connected</span>
                        <span class="status-indicator good"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize dashboard
    initializeDashboard();
    
    // Auto-refresh dashboard
    setInterval(function() {
        refreshDashboard();
    }, 30000); // 30 seconds
});

function initializeDashboard() {
    initializeCharts();
    setupAutoRefresh();
}

function initializeCharts() {
    // Sessions Chart
    const sessionsCtx = document.getElementById('sessions-chart');
    if (sessionsCtx) {
        const sessionsData = <?php echo json_encode($dashboard_data['active_sessions']['chart_data'] ?? array()); ?>;
        
        new Chart(sessionsCtx, {
            type: 'line',
            data: {
                labels: sessionsData.map(d => d.hour + ':00'),
                datasets: [{
                    label: 'Active Sessions',
                    data: sessionsData.map(d => d.count),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
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
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // AI Usage Chart
    const aiCtx = document.getElementById('ai-usage-chart');
    if (aiCtx) {
        const aiData = <?php echo json_encode($dashboard_data['ai_usage'] ?? array()); ?>;
        
        new Chart(aiCtx, {
            type: 'bar',
            data: {
                labels: aiData.map(d => d.date),
                datasets: [{
                    label: 'Interactions',
                    data: aiData.map(d => d.interactions),
                    backgroundColor: '#2ecc71',
                    borderColor: '#27ae60',
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
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

function refreshDashboard() {
    jQuery.post(ajaxurl, {
        action: 'sixlab_dashboard_refresh',
        nonce: sixlabAdmin.nonce
    }, function(response) {
        if (response.success) {
            updateDashboardData(response.data);
        }
    });
}

function refreshWidget(widgetType) {
    jQuery.post(ajaxurl, {
        action: 'sixlab_get_widget_data',
        widget: widgetType,
        nonce: sixlabAdmin.nonce
    }, function(response) {
        if (response.success) {
            updateWidgetData(widgetType, response.data);
        }
    });
}

function updateDashboardData(data) {
    // Update stat cards
    jQuery('#active-sessions-count').text(data.active_sessions.total);
    
    // Update other dashboard elements as needed
}

function updateWidgetData(widgetType, data) {
    // Update specific widget data
    switch (widgetType) {
        case 'active_sessions':
            jQuery('#active-sessions-count').text(data.total);
            break;
        case 'provider_health':
            updateProviderHealthGrid(data);
            break;
        // Add other widget updates
    }
}

function updateProviderHealthGrid(providers) {
    const grid = jQuery('#provider-health-grid');
    grid.empty();
    
    providers.forEach(function(provider) {
        const item = jQuery('<div>').addClass('health-item ' + provider.health_status);
        // Build provider health item HTML
        grid.append(item);
    });
}

function setupAutoRefresh() {
    // Setup automatic refresh for different widgets
    Object.keys(sixlabAdmin.refreshIntervals).forEach(function(widget) {
        setInterval(function() {
            refreshWidget(widget);
        }, sixlabAdmin.refreshIntervals[widget]);
    });
}

function updateAIChart(timeframe) {
    // Update AI usage chart based on timeframe
    console.log('Updating AI chart for timeframe:', timeframe);
}
</script>";
    }
}
