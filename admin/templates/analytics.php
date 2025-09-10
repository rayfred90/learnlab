<?php
/**
 * Analytics Admin Page Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$date_range = $_GET['range'] ?? '7days';
?>

<div class="wrap">
    <h1><?php _e('6Lab Analytics', 'sixlab-tool'); ?></h1>
    
    <div class="sixlab-analytics-header">
        <div class="sixlab-date-selector">
            <label for="date-range"><?php _e('Date Range:', 'sixlab-tool'); ?></label>
            <select id="date-range" onchange="location.href='?page=sixlab-analytics&range=' + this.value">
                <option value="24hours" <?php selected($date_range, '24hours'); ?>>
                    <?php _e('Last 24 Hours', 'sixlab-tool'); ?>
                </option>
                <option value="7days" <?php selected($date_range, '7days'); ?>>
                    <?php _e('Last 7 Days', 'sixlab-tool'); ?>
                </option>
                <option value="30days" <?php selected($date_range, '30days'); ?>>
                    <?php _e('Last 30 Days', 'sixlab-tool'); ?>
                </option>
                <option value="90days" <?php selected($date_range, '90days'); ?>>
                    <?php _e('Last 90 Days', 'sixlab-tool'); ?>
                </option>
            </select>
        </div>
    </div>
    
    <div class="sixlab-analytics-grid">
        <!-- Summary Cards -->
        <div class="sixlab-summary-cards">
            <div class="sixlab-card">
                <div class="sixlab-card-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="sixlab-card-content">
                    <h3><?php echo esc_html($analytics_data['total_sessions'] ?? 0); ?></h3>
                    <p><?php _e('Total Sessions', 'sixlab-tool'); ?></p>
                </div>
            </div>
            
            <div class="sixlab-card">
                <div class="sixlab-card-icon">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="sixlab-card-content">
                    <h3><?php echo esc_html($analytics_data['active_users'] ?? 0); ?></h3>
                    <p><?php _e('Active Users', 'sixlab-tool'); ?></p>
                </div>
            </div>
            
            <div class="sixlab-card">
                <div class="sixlab-card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="sixlab-card-content">
                    <h3><?php echo esc_html($analytics_data['completed_sessions'] ?? 0); ?></h3>
                    <p><?php _e('Completed Sessions', 'sixlab-tool'); ?></p>
                </div>
            </div>
            
            <div class="sixlab-card">
                <div class="sixlab-card-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="sixlab-card-content">
                    <h3><?php echo esc_html(number_format($analytics_data['average_score'] ?? 0, 1)); ?>%</h3>
                    <p><?php _e('Average Score', 'sixlab-tool'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="sixlab-charts-row">
            <div class="sixlab-chart-container">
                <h3><?php _e('Session Activity', 'sixlab-tool'); ?></h3>
                <div id="sessions-chart" class="sixlab-chart">
                    <?php if (!empty($analytics_data['daily_sessions'])): ?>
                        <table class="sixlab-simple-chart">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Sessions', 'sixlab-tool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics_data['daily_sessions'] as $session): ?>
                                    <tr>
                                        <td><?php echo esc_html(date('M j', strtotime($session->date))); ?></td>
                                        <td>
                                            <div class="sixlab-bar">
                                                <div class="sixlab-bar-fill" style="width: <?php echo esc_attr(($session->count / max(array_column($analytics_data['daily_sessions'], 'count'))) * 100); ?>%"></div>
                                                <span class="sixlab-bar-value"><?php echo esc_html($session->count); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="sixlab-no-data"><?php _e('No session data available for this period.', 'sixlab-tool'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sixlab-chart-container">
                <h3><?php _e('Popular Providers', 'sixlab-tool'); ?></h3>
                <div id="providers-chart" class="sixlab-chart">
                    <?php if (!empty($analytics_data['popular_providers'])): ?>
                        <table class="sixlab-simple-chart">
                            <thead>
                                <tr>
                                    <th><?php _e('Provider', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Usage', 'sixlab-tool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_usage = max(array_column($analytics_data['popular_providers'], 'usage_count'));
                                foreach ($analytics_data['popular_providers'] as $provider): 
                                ?>
                                    <tr>
                                        <td><?php echo esc_html(ucfirst($provider->provider)); ?></td>
                                        <td>
                                            <div class="sixlab-bar">
                                                <div class="sixlab-bar-fill" style="width: <?php echo esc_attr(($provider->usage_count / $max_usage) * 100); ?>%"></div>
                                                <span class="sixlab-bar-value"><?php echo esc_html($provider->usage_count); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="sixlab-no-data"><?php _e('No provider usage data available.', 'sixlab-tool'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="sixlab-recent-activity">
            <h3><?php _e('Recent Lab Sessions', 'sixlab-tool'); ?></h3>
            <?php if (!empty($analytics_data['recent_sessions'])): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'sixlab-tool'); ?></th>
                            <th><?php _e('Lab', 'sixlab-tool'); ?></th>
                            <th><?php _e('Provider', 'sixlab-tool'); ?></th>
                            <th><?php _e('Status', 'sixlab-tool'); ?></th>
                            <th><?php _e('Score', 'sixlab-tool'); ?></th>
                            <th><?php _e('Started', 'sixlab-tool'); ?></th>
                            <th><?php _e('Duration', 'sixlab-tool'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics_data['recent_sessions'] as $session): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $user = get_user_by('id', $session->user_id);
                                    echo $user ? esc_html($user->display_name) : __('Unknown User', 'sixlab-tool');
                                    ?>
                                </td>
                                <td><?php echo esc_html($session->lab_id); ?></td>
                                <td><?php echo esc_html(ucfirst($session->provider)); ?></td>
                                <td>
                                    <span class="sixlab-status-badge status-<?php echo esc_attr($session->status); ?>">
                                        <?php echo esc_html(ucfirst($session->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($session->score !== null): ?>
                                        <?php echo esc_html(number_format($session->score, 1)); ?>%
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(human_time_diff(strtotime($session->created_at), current_time('timestamp')) . ' ago'); ?></td>
                                <td>
                                    <?php 
                                    if ($session->completed_at) {
                                        $duration = strtotime($session->completed_at) - strtotime($session->created_at);
                                        echo esc_html(gmdate('H:i:s', $duration));
                                    } else {
                                        echo __('In progress', 'sixlab-tool');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="sixlab-empty-state">
                    <h4><?php _e('No Recent Sessions', 'sixlab-tool'); ?></h4>
                    <p><?php _e('Lab sessions will appear here once users start accessing labs.', 'sixlab-tool'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Lab Templates Usage -->
        <div class="sixlab-templates-usage">
            <h3><?php _e('Lab Template Usage', 'sixlab-tool'); ?></h3>
            <?php if (!empty($analytics_data['template_usage'])): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Template', 'sixlab-tool'); ?></th>
                            <th><?php _e('Usage Count', 'sixlab-tool'); ?></th>
                            <th><?php _e('Avg. Score', 'sixlab-tool'); ?></th>
                            <th><?php _e('Completion Rate', 'sixlab-tool'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics_data['template_usage'] as $template): ?>
                            <tr>
                                <td><?php echo esc_html($template->name); ?></td>
                                <td><?php echo esc_html($template->usage_count); ?></td>
                                <td>
                                    <?php if ($template->average_score !== null): ?>
                                        <?php echo esc_html(number_format($template->average_score, 1)); ?>%
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $completion_rate = $template->completed_sessions / max($template->total_sessions, 1) * 100;
                                    echo esc_html(number_format($completion_rate, 1)) . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="sixlab-empty-state">
                    <h4><?php _e('No Template Usage Data', 'sixlab-tool'); ?></h4>
                    <p><?php _e('Template usage statistics will appear here once labs are accessed.', 'sixlab-tool'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.sixlab-analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 5px;
}

.sixlab-date-selector select {
    margin-left: 10px;
}

.sixlab-analytics-grid {
    display: grid;
    gap: 20px;
}

.sixlab-summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.sixlab-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 5px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.sixlab-card-icon {
    font-size: 24px;
    color: #0073aa;
}

.sixlab-card-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
    color: #23282d;
}

.sixlab-card-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.sixlab-charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.sixlab-chart-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 5px;
    padding: 20px;
}

.sixlab-chart-container h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.sixlab-simple-chart {
    width: 100%;
    border-collapse: collapse;
}

.sixlab-simple-chart th,
.sixlab-simple-chart td {
    text-align: left;
    padding: 8px;
    border-bottom: 1px solid #eee;
}

.sixlab-bar {
    position: relative;
    background: #f1f1f1;
    height: 25px;
    border-radius: 3px;
    min-width: 100px;
}

.sixlab-bar-fill {
    background: linear-gradient(90deg, #0073aa, #005177);
    height: 100%;
    border-radius: 3px;
    min-width: 2px;
}

.sixlab-bar-value {
    position: absolute;
    top: 50%;
    left: 5px;
    transform: translateY(-50%);
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

.sixlab-recent-activity,
.sixlab-templates-usage {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 5px;
    padding: 20px;
    margin-top: 20px;
}

.sixlab-recent-activity h3,
.sixlab-templates-usage h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.sixlab-status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    color: white;
}

.status-active { background: #0073aa; }
.status-completed { background: #28a745; }
.status-paused { background: #ffc107; color: #333; }
.status-expired { background: #6c757d; }
.status-error { background: #dc3545; }

.sixlab-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border: 1px dashed #dee2e6;
    border-radius: 5px;
}

.sixlab-no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
}

@media (max-width: 768px) {
    .sixlab-charts-row {
        grid-template-columns: 1fr;
    }
    
    .sixlab-summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>
