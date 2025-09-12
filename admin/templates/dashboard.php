<?php
/**
 * Admin Dashboard Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Dashboard Overview Cards -->
    <div class="sixlab-dashboard-cards">
        <div class="sixlab-card">
            <div class="sixlab-card-icon">
                <span class="dashicons dashicons-desktop"></span>
            </div>
            <div class="sixlab-card-content">
                <h3><?php _e('Active Sessions', 'sixlab-tool'); ?></h3>
                <div class="sixlab-card-number"><?php echo esc_html($system_info['active_sessions'] ?? 0); ?></div>
                <p class="sixlab-card-description"><?php _e('Currently running lab sessions', 'sixlab-tool'); ?></p>
            </div>
        </div>
        
        <div class="sixlab-card">
            <div class="sixlab-card-icon">
                <span class="dashicons dashicons-analytics"></span>
            </div>
            <div class="sixlab-card-content">
                <h3><?php _e('Total Sessions', 'sixlab-tool'); ?></h3>
                <div class="sixlab-card-number"><?php echo esc_html($system_info['total_sessions'] ?? 0); ?></div>
                <p class="sixlab-card-description"><?php _e('All-time lab sessions created', 'sixlab-tool'); ?></p>
            </div>
        </div>
        
        <div class="sixlab-card">
            <div class="sixlab-card-icon">
                <span class="dashicons dashicons-admin-page"></span>
            </div>
            <div class="sixlab-card-content">
                <h3><?php _e('Lab Templates', 'sixlab-tool'); ?></h3>
                <div class="sixlab-card-number"><?php echo esc_html($system_info['total_templates'] ?? 0); ?></div>
                <p class="sixlab-card-description"><?php _e('Available lab templates', 'sixlab-tool'); ?></p>
            </div>
        </div>
        
        <div class="sixlab-card">
            <div class="sixlab-card-icon">
                <span class="dashicons dashicons-wordpress"></span>
            </div>
            <div class="sixlab-card-content">
                <h3><?php _e('Plugin Version', 'sixlab-tool'); ?></h3>
                <div class="sixlab-card-number"><?php echo esc_html($system_info['plugin_version'] ?? '1.0.0'); ?></div>
                <p class="sixlab-card-description"><?php _e('Current plugin version', 'sixlab-tool'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Provider Status Section -->
    <div class="sixlab-dashboard-section">
        <h2><?php _e('Provider Status', 'sixlab-tool'); ?></h2>
        
        <div class="sixlab-providers-grid">
            <!-- Lab Providers -->
            <div class="sixlab-provider-group">
                <h3><?php _e('Lab Providers', 'sixlab-tool'); ?></h3>
                
                <?php if (!empty($providers_status)): ?>
                    <?php foreach ($providers_status as $provider_type => $status): ?>
                        <div class="sixlab-provider-status <?php echo $status['healthy'] ? 'healthy' : 'unhealthy'; ?>">
                            <div class="sixlab-provider-info">
                                <strong><?php echo esc_html(ucfirst($provider_type)); ?></strong>
                                <span class="sixlab-status-indicator">
                                    <?php if ($status['healthy']): ?>
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Healthy', 'sixlab-tool'); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php _e('Issues', 'sixlab-tool'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($status['message'])): ?>
                                <p class="sixlab-status-message"><?php echo esc_html($status['message']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sixlab-no-providers"><?php _e('No lab providers configured yet.', 'sixlab-tool'); ?>
                        <a href="<?php echo admin_url('admin.php?page=sixlab-providers'); ?>"><?php _e('Configure providers', 'sixlab-tool'); ?></a>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- AI Providers -->
            <div class="sixlab-provider-group">
                <h3><?php _e('AI Providers', 'sixlab-tool'); ?></h3>
                
                <?php if (!empty($ai_providers_status)): ?>
                    <?php foreach ($ai_providers_status as $provider_type => $status): ?>
                        <div class="sixlab-provider-status <?php echo $status['success'] ? 'healthy' : 'unhealthy'; ?>">
                            <div class="sixlab-provider-info">
                                <strong><?php echo esc_html(ucfirst($provider_type)); ?></strong>
                                <span class="sixlab-status-indicator">
                                    <?php if ($status['success']): ?>
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Connected', 'sixlab-tool'); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php _e('Issues', 'sixlab-tool'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($status['message'])): ?>
                                <p class="sixlab-status-message"><?php echo esc_html($status['message']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sixlab-no-providers"><?php _e('No AI providers configured yet.', 'sixlab-tool'); ?>
                        <a href="<?php echo admin_url('admin.php?page=sixlab-ai-providers'); ?>"><?php _e('Configure AI providers', 'sixlab-tool'); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div class="sixlab-dashboard-section">
        <h2><?php _e('Recent Lab Sessions', 'sixlab-tool'); ?></h2>
        
        <?php if (!empty($recent_sessions)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'sixlab-tool'); ?></th>
                        <th><?php _e('Lab ID', 'sixlab-tool'); ?></th>
                        <th><?php _e('Provider', 'sixlab-tool'); ?></th>
                        <th><?php _e('Status', 'sixlab-tool'); ?></th>
                        <th><?php _e('Progress', 'sixlab-tool'); ?></th>
                        <th><?php _e('Created', 'sixlab-tool'); ?></th>
                        <th><?php _e('Actions', 'sixlab-tool'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_sessions as $session): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($session->user_name ?? 'Unknown'); ?></strong>
                            </td>
                            <td>
                                <code><?php echo esc_html($session->lab_id); ?></code>
                            </td>
                            <td>
                                <span class="sixlab-provider-badge"><?php echo esc_html(ucfirst($session->provider)); ?></span>
                            </td>
                            <td>
                                <span class="sixlab-status-badge status-<?php echo esc_attr($session->status); ?>">
                                    <?php echo esc_html(ucfirst($session->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $progress = $session->total_steps > 0 ? 
                                    round(($session->current_step / $session->total_steps) * 100) : 0;
                                ?>
                                <div class="sixlab-progress-bar">
                                    <div class="sixlab-progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    <span class="sixlab-progress-text"><?php echo $session->current_step; ?>/<?php echo $session->total_steps; ?></span>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html(mysql2date('Y/m/d g:i a', $session->created_at)); ?>
                            </td>
                            <td>
                                <?php if (in_array($session->status, ['active', 'started', 'in_progress'])): ?>
                                    <button type="button" class="button button-small button-secondary stop-session-btn" 
                                            data-session-id="<?php echo esc_attr($session->id); ?>"
                                            data-user-name="<?php echo esc_attr($session->user_name ?? 'Unknown'); ?>">
                                        <i class="dashicons dashicons-no"></i>
                                        <?php _e('Stop Session', 'sixlab-tool'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="<?php esc_attr_e('Session Completed', 'sixlab-tool'); ?>"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sixlab-no-data"><?php _e('No recent lab sessions found.', 'sixlab-tool'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- System Information -->
    <div class="sixlab-dashboard-section">
        <h2><?php _e('System Information', 'sixlab-tool'); ?></h2>
        
        <table class="sixlab-system-info">
            <tr>
                <td><?php _e('WordPress Version', 'sixlab-tool'); ?></td>
                <td><?php echo esc_html($system_info['wp_version'] ?? 'Unknown'); ?></td>
            </tr>
            <tr>
                <td><?php _e('PHP Version', 'sixlab-tool'); ?></td>
                <td><?php echo esc_html($system_info['php_version'] ?? 'Unknown'); ?></td>
            </tr>
            <tr>
                <td><?php _e('Database Version', 'sixlab-tool'); ?></td>
                <td><?php echo esc_html($system_info['database_version'] ?? 'Unknown'); ?></td>
            </tr>
            <tr>
                <td><?php _e('LearnDash Active', 'sixlab-tool'); ?></td>
                <td>
                    <?php if (class_exists('SFWD_LMS')): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <?php _e('Yes', 'sixlab-tool'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                        <?php _e('Not installed', 'sixlab-tool'); ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Quick Actions -->
    <div class="sixlab-dashboard-section">
        <h2><?php _e('Quick Actions', 'sixlab-tool'); ?></h2>
        
        <div class="sixlab-quick-actions">
            <a href="<?php echo admin_url('admin.php?page=sixlab-providers'); ?>" class="button button-primary">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Configure Providers', 'sixlab-tool'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=sixlab-ai-providers'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-buddicons-activity"></span>
                <?php _e('Setup AI Assistant', 'sixlab-tool'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=sixlab-templates'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-admin-page"></span>
                <?php _e('Manage Templates', 'sixlab-tool'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=sixlab-analytics'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('View Analytics', 'sixlab-tool'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.sixlab-dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.sixlab-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-card-icon {
    margin-right: 15px;
}

.sixlab-card-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #2271b1;
}

.sixlab-card-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #646970;
}

.sixlab-card-number {
    font-size: 28px;
    font-weight: 600;
    line-height: 1;
    color: #1d2327;
}

.sixlab-card-description {
    margin: 5px 0 0 0;
    font-size: 13px;
    color: #646970;
}

.sixlab-dashboard-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-dashboard-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #c3c4c7;
}

.sixlab-providers-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.sixlab-provider-group h3 {
    margin-bottom: 15px;
    color: #1d2327;
}

.sixlab-provider-status {
    padding: 12px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 10px;
}

.sixlab-provider-status.healthy {
    border-color: #00a32a;
    background-color: #f0f9f0;
}

.sixlab-provider-status.unhealthy {
    border-color: #d63638;
    background-color: #f9f0f0;
}

.sixlab-provider-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sixlab-status-indicator {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
}

.sixlab-status-indicator .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.sixlab-provider-status.healthy .dashicons {
    color: #00a32a;
}

.sixlab-provider-status.unhealthy .dashicons {
    color: #d63638;
}

.sixlab-status-message {
    margin: 8px 0 0 0;
    font-size: 13px;
    color: #646970;
}

.sixlab-no-providers, .sixlab-no-data {
    color: #646970;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.sixlab-provider-badge {
    background: #2271b1;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    text-transform: uppercase;
}

.sixlab-status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 600;
}

.status-active { background: #00a32a; color: white; }
.status-paused { background: #dba617; color: white; }
.status-completed { background: #2271b1; color: white; }
.status-expired { background: #646970; color: white; }
.status-error { background: #d63638; color: white; }

.sixlab-progress-bar {
    position: relative;
    background: #f0f0f1;
    border-radius: 3px;
    height: 20px;
    width: 100px;
    overflow: hidden;
}

.sixlab-progress-fill {
    background: #2271b1;
    height: 100%;
    transition: width 0.3s ease;
}

.sixlab-progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    color: #1d2327;
    font-weight: 600;
}

.sixlab-system-info {
    width: 100%;
    border-collapse: collapse;
}

.sixlab-system-info td {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f1;
}

.sixlab-system-info td:first-child {
    font-weight: 600;
    width: 200px;
}

.sixlab-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.sixlab-quick-actions .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.sixlab-quick-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

@media (max-width: 768px) {
    .sixlab-providers-grid {
        grid-template-columns: 1fr;
    }
    
    .sixlab-quick-actions {
        flex-direction: column;
    }
    
    .sixlab-quick-actions .button {
        justify-content: center;
    }
}

.stop-session-btn {
    background-color: #d63638 !important;
    color: white !important;
    border-color: #d63638 !important;
}

.stop-session-btn:hover {
    background-color: #b32d2e !important;
    border-color: #b32d2e !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle stop session button clicks
    $('.stop-session-btn').on('click', function() {
        const sessionId = $(this).data('session-id');
        const userName = $(this).data('user-name');
        const button = $(this);
        
        if (confirm('<?php esc_js(__('Are you sure you want to stop the lab session for', 'sixlab-tool')); ?> ' + userName + '?')) {
            button.prop('disabled', true).html('<i class="dashicons dashicons-update"></i> <?php esc_js(__('Stopping...', 'sixlab-tool')); ?>');
            
            $.post(ajaxurl, {
                action: 'sixlab_admin_stop_session',
                session_id: sessionId,
                nonce: '<?php echo wp_create_nonce('sixlab_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    button.closest('tr').find('.sixlab-status-badge')
                          .removeClass('status-active status-started status-in_progress')
                          .addClass('status-expired')
                          .text('<?php esc_js(__('Stopped', 'sixlab-tool')); ?>');
                    
                    button.replaceWith('<span class="dashicons dashicons-dismiss" style="color: #d63638;" title="<?php esc_attr_e('Session Stopped', 'sixlab-tool'); ?>"></span>');
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p><?php esc_js(__('Lab session stopped successfully.', 'sixlab-tool')); ?></p></div>')
                        .insertAfter('.wp-header-end').delay(3000).fadeOut();
                } else {
                    alert('<?php esc_js(__('Error stopping session:', 'sixlab-tool')); ?> ' + response.data.message);
                    button.prop('disabled', false).html('<i class="dashicons dashicons-no"></i> <?php esc_js(__('Stop Session', 'sixlab-tool')); ?>');
                }
            }).fail(function() {
                alert('<?php esc_js(__('Network error occurred while stopping the session.', 'sixlab-tool')); ?>');
                button.prop('disabled', false).html('<i class="dashicons dashicons-no"></i> <?php esc_js(__('Stop Session', 'sixlab-tool')); ?>');
            });
        }
    });
});
</script>
