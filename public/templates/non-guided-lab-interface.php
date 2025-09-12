<?php
/**
 * Non-Guided Lab Interface Template
 * 
 * Provides open-ended interface for non-guided labs with verification functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get template data
$template_id = get_query_var('template_id');
if (!$template_id) {
    wp_die(__('Template not found.', 'sixlab-tool'));
}

global $wpdb;
$template = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sixlab_lab_templates WHERE id = %d AND is_active = 1",
    $template_id
));

if (!$template || $template->template_type !== 'non_guided') {
    wp_die(__('Non-guided lab template not found.', 'sixlab-tool'));
}

// Get current user session if exists
$user_id = get_current_user_id();
$current_session = null;

if ($user_id) {
    $current_session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sixlab_sessions 
         WHERE user_id = %d AND lab_id = %d AND status IN ('started', 'active', 'in_progress') 
         ORDER BY created_at DESC LIMIT 1",
        $user_id, $template_id
    ));
}

$session_id = $current_session ? $current_session->id : null;
$lab_started = $session_id !== null;

get_header();
?>

<div class="sixlab-nonguided-interface">
    <div class="lab-header">
        <div class="header-content">
            <h1><?php echo esc_html($template->name); ?></h1>
            <div class="lab-meta">
                <span class="difficulty-badge difficulty-<?php echo esc_attr($template->difficulty_level); ?>">
                    <?php echo esc_html(ucfirst($template->difficulty_level)); ?>
                </span>
                <?php if ($template->estimated_duration): ?>
                    <span class="duration-badge">
                        <i class="dashicons dashicons-clock"></i>
                        <?php printf(__('%d minutes', 'sixlab-tool'), $template->estimated_duration); ?>
                    </span>
                <?php endif; ?>
                <span class="status-badge <?php echo $lab_started ? 'status-active' : 'status-ready'; ?>">
                    <?php echo $lab_started ? __('Lab Active', 'sixlab-tool') : __('Ready to Start', 'sixlab-tool'); ?>
                </span>
            </div>
            
            <div class="lab-description">
                <p><?php echo esc_html($template->description); ?></p>
            </div>
        </div>
    </div>

    <div class="lab-content">
        <div class="content-grid">
            <div class="instructions-panel">
                <div class="panel-header">
                    <h2><?php _e('Lab Instructions', 'sixlab-tool'); ?></h2>
                </div>
                
                <div class="instructions-content">
                    <?php if ($template->instructions_content): ?>
                        <?php echo wp_kses_post($template->instructions_content); ?>
                    <?php else: ?>
                        <div class="instruction-content">
                            <?php echo wpautop(esc_html($template->instructions)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($template->learning_objectives): ?>
                    <div class="learning-objectives">
                        <h3><?php _e('Learning Objectives', 'sixlab-tool'); ?></h3>
                        <ul>
                            <?php 
                            $objectives = json_decode($template->learning_objectives, true);
                            if (is_array($objectives)) {
                                foreach ($objectives as $objective) {
                                    echo '<li>' . esc_html($objective) . '</li>';
                                }
                            } else {
                                $objectives_lines = explode("\n", $template->learning_objectives);
                                foreach ($objectives_lines as $objective) {
                                    if (trim($objective)) {
                                        echo '<li>' . esc_html(trim($objective)) . '</li>';
                                    }
                                }
                            }
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lab-workspace">
                <div class="workspace-header">
                    <h2><?php _e('Lab Workspace', 'sixlab-tool'); ?></h2>
                    <div class="workspace-actions">
                        <?php if (!$lab_started): ?>
                            <button type="button" class="button button-primary button-large" id="start-lab-btn" data-template-id="<?php echo esc_attr($template_id); ?>">
                                <i class="dashicons dashicons-controls-play"></i>
                                <?php _e('Start Lab', 'sixlab-tool'); ?>
                            </button>
                        <?php else: ?>
                            <div class="session-info">
                                <span class="session-time" data-start-time="<?php echo esc_attr($current_session->created_at); ?>">
                                    <i class="dashicons dashicons-clock"></i>
                                    <span class="elapsed-time">00:00:00</span>
                                </span>
                                <button type="button" class="button verify-work-btn" id="verify-work-btn">
                                    <i class="dashicons dashicons-yes"></i>
                                    <?php _e('Verify Work', 'sixlab-tool'); ?>
                                </button>
                                <button type="button" class="button button-secondary reset-lab" 
                                        data-session-id="<?php echo esc_attr($session_id); ?>" 
                                        data-template-id="<?php echo esc_attr($template_id); ?>"
                                        title="<?php esc_attr_e('Reset lab environment to start over', 'sixlab-tool'); ?>">
                                    <i class="dashicons dashicons-update"></i>
                                    <?php _e('Reset Lab', 'sixlab-tool'); ?>
                                </button>
                                <button type="button" class="button button-secondary end-lab-btn" id="end-lab-btn">
                                    <i class="dashicons dashicons-no"></i>
                                    <?php _e('End Lab', 'sixlab-tool'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($lab_started): ?>
                    <div class="workspace-content">
                        <div class="lab-environment">
                            <div class="environment-status">
                                <h3><?php _e('Lab Environment', 'sixlab-tool'); ?></h3>
                                <div class="status-indicator">
                                    <span class="status-dot active"></span>
                                    <span class="status-text"><?php _e('Environment Active', 'sixlab-tool'); ?></span>
                                </div>
                            </div>
                            
                            <div class="environment-details">
                                <p><?php _e('Your lab environment has been set up and is ready for use. You can now begin working on the lab objectives.', 'sixlab-tool'); ?></p>
                                
                                <?php if ($template->provider_type === 'guacamole'): ?>
                                    <div class="access-panel">
                                        <h4><?php _e('Remote Desktop Access', 'sixlab-tool'); ?></h4>
                                        <button type="button" class="button button-primary access-desktop-btn">
                                            <i class="dashicons dashicons-desktop"></i>
                                            <?php _e('Access Desktop', 'sixlab-tool'); ?>
                                        </button>
                                    </div>
                                <?php elseif ($template->provider_type === 'gns3'): ?>
                                    <div class="access-panel">
                                        <h4><?php _e('Network Simulation Access', 'sixlab-tool'); ?></h4>
                                        <button type="button" class="button button-primary access-gns3-btn">
                                            <i class="dashicons dashicons-networking"></i>
                                            <?php _e('Open GNS3', 'sixlab-tool'); ?>
                                        </button>
                                    </div>
                                <?php elseif ($template->provider_type === 'eveng'): ?>
                                    <div class="access-panel">
                                        <h4><?php _e('EVE-NG Lab Access', 'sixlab-tool'); ?></h4>
                                        <button type="button" class="button button-primary access-eveng-btn">
                                            <i class="dashicons dashicons-networking"></i>
                                            <?php _e('Open EVE-NG', 'sixlab-tool'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="progress-tracking">
                            <h3><?php _e('Progress Notes', 'sixlab-tool'); ?></h3>
                            <div class="notes-container">
                                <textarea id="progress-notes" placeholder="<?php esc_attr_e('Take notes about your progress, findings, and any issues you encounter...', 'sixlab-tool'); ?>"></textarea>
                                <button type="button" class="button save-notes-btn">
                                    <i class="dashicons dashicons-saved"></i>
                                    <?php _e('Save Notes', 'sixlab-tool'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="verification-panel" id="verification-panel" style="display: none;">
                            <h3><?php _e('Work Verification', 'sixlab-tool'); ?></h3>
                            <div class="verification-content">
                                <div class="verification-status">
                                    <div class="status-message" id="verification-status-message"></div>
                                    <div class="verification-progress" id="verification-progress" style="display: none;">
                                        <div class="progress-bar">
                                            <div class="progress-fill"></div>
                                        </div>
                                        <p><?php _e('Verifying your work...', 'sixlab-tool'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="verification-results" id="verification-results" style="display: none;">
                                    <div class="results-content"></div>
                                </div>
                                
                                <div class="ai-feedback" id="ai-feedback" style="display: none;">
                                    <h4><?php _e('AI Assistant Feedback', 'sixlab-tool'); ?></h4>
                                    <div class="feedback-content"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="lab-preview">
                        <div class="preview-content">
                            <h3><?php _e('Lab Overview', 'sixlab-tool'); ?></h3>
                            <p><?php _e('This is a non-guided lab where you will work independently to achieve the learning objectives. Once you start the lab, your environment will be automatically configured.', 'sixlab-tool'); ?></p>
                            
                            <div class="lab-features">
                                <h4><?php _e('What you will have access to:', 'sixlab-tool'); ?></h4>
                                <ul>
                                    <li><i class="dashicons dashicons-yes"></i> <?php _e('Pre-configured lab environment', 'sixlab-tool'); ?></li>
                                    <li><i class="dashicons dashicons-yes"></i> <?php _e('Detailed instructions and resources', 'sixlab-tool'); ?></li>
                                    <li><i class="dashicons dashicons-yes"></i> <?php _e('Progress tracking and notes', 'sixlab-tool'); ?></li>
                                    <li><i class="dashicons dashicons-yes"></i> <?php _e('AI-powered work verification', 'sixlab-tool'); ?></li>
                                    <li><i class="dashicons dashicons-yes"></i> <?php _e('Instant feedback and guidance', 'sixlab-tool'); ?></li>
                                </ul>
                            </div>
                            
                            <?php if ($template->provider_type): ?>
                                <div class="provider-info">
                                    <h4><?php _e('Lab Provider', 'sixlab-tool'); ?></h4>
                                    <p><?php printf(__('This lab uses %s as the lab provider.', 'sixlab-tool'), esc_html(ucfirst($template->provider_type))); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.sixlab-nonguided-interface {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.lab-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.lab-header h1 {
    margin: 0 0 15px 0;
    font-size: 2.8em;
    font-weight: 300;
}

.lab-meta {
    margin: 20px 0;
}

.difficulty-badge, .duration-badge, .status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 25px;
    font-size: 0.9em;
    margin-right: 12px;
    font-weight: 500;
}

.difficulty-beginner { background: rgba(255,255,255,0.2); }
.difficulty-intermediate { background: rgba(255,193,7,0.8); color: #856404; }
.difficulty-advanced { background: rgba(220,53,69,0.8); color: white; }

.duration-badge { background: rgba(255,255,255,0.2); }
.status-ready { background: rgba(40,167,69,0.8); }
.status-active { background: rgba(0,123,255,0.8); }

.lab-description {
    margin-top: 20px;
    font-size: 1.1em;
    opacity: 0.9;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    align-items: start;
}

.instructions-panel,
.lab-workspace {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.panel-header,
.workspace-header {
    background: #f8f9fa;
    padding: 20px 30px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h2,
.workspace-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.5em;
}

.instructions-content {
    padding: 30px;
    line-height: 1.7;
}

.instructions-content h3 {
    color: #2c3e50;
    margin-top: 30px;
    margin-bottom: 15px;
}

.instructions-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.learning-objectives {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin-top: 30px;
    border-left: 4px solid #007cba;
}

.learning-objectives h3 {
    margin-top: 0;
    color: #007cba;
}

.learning-objectives ul {
    margin: 15px 0 0 0;
    padding-left: 20px;
}

.learning-objectives li {
    margin: 8px 0;
    color: #495057;
}

.workspace-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.session-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.session-time {
    background: #e7f3ff;
    color: #0056b3;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: 500;
}

.workspace-content {
    padding: 30px;
}

.lab-environment,
.progress-tracking,
.verification-panel {
    margin-bottom: 30px;
    padding: 25px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.environment-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.environment-status h3 {
    margin: 0;
    color: #2c3e50;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6c757d;
}

.status-dot.active {
    background: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-text {
    font-weight: 500;
    color: #28a745;
}

.access-panel {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-top: 15px;
}

.access-panel h4 {
    margin-top: 0;
    color: #2c3e50;
}

.notes-container {
    margin-top: 15px;
}

#progress-notes {
    width: 100%;
    min-height: 120px;
    padding: 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-family: inherit;
    resize: vertical;
}

.save-notes-btn {
    margin-top: 10px;
}

.verification-panel {
    background: #f8f9fa;
    border-color: #007cba;
}

.verification-progress .progress-bar {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin: 15px 0;
}

.verification-progress .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007cba, #28a745);
    animation: loading 2s infinite;
}

@keyframes loading {
    0% { width: 0%; }
    50% { width: 100%; }
    100% { width: 0%; }
}

.verification-results {
    background: white;
    padding: 20px;
    border-radius: 6px;
    margin-top: 15px;
}

.ai-feedback {
    background: #e7f3ff;
    padding: 20px;
    border-radius: 6px;
    margin-top: 15px;
    border-left: 4px solid #007cba;
}

.ai-feedback h4 {
    margin-top: 0;
    color: #007cba;
}

.lab-preview {
    padding: 30px;
    text-align: center;
}

.preview-content h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.lab-features {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin: 25px 0;
    text-align: left;
}

.lab-features h4 {
    margin-top: 0;
    color: #2c3e50;
}

.lab-features ul {
    list-style: none;
    padding: 0;
    margin: 15px 0 0 0;
}

.lab-features li {
    padding: 8px 0;
    color: #495057;
}

.lab-features .dashicons {
    color: #28a745;
    margin-right: 8px;
}

.provider-info {
    background: #e7f3ff;
    padding: 20px;
    border-radius: 6px;
    margin-top: 20px;
    border-left: 4px solid #007cba;
}

.provider-info h4 {
    margin-top: 0;
    color: #007cba;
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .workspace-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .session-info {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 768px) {
    .lab-header {
        padding: 25px;
    }
    
    .lab-header h1 {
        font-size: 2.2em;
    }
    
    .panel-header,
    .workspace-header {
        padding: 15px 20px;
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .instructions-content,
    .workspace-content {
        padding: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let sessionId = <?php echo $session_id ? $session_id : 'null'; ?>;
    
    // Update elapsed time
    function updateElapsedTime() {
        const startTimeStr = $('.session-time').data('start-time');
        if (startTimeStr) {
            const startTime = new Date(startTimeStr);
            const now = new Date();
            const elapsed = Math.floor((now - startTime) / 1000);
            
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            
            const timeStr = [hours, minutes, seconds]
                .map(t => t.toString().padStart(2, '0'))
                .join(':');
            
            $('.elapsed-time').text(timeStr);
        }
    }
    
    // Update time every second
    if (sessionId) {
        setInterval(updateElapsedTime, 1000);
        updateElapsedTime();
    }
    
    // Start lab session
    $('#start-lab-btn').on('click', function() {
        const templateId = $(this).data('template-id');
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="dashicons dashicons-update"></i> Starting...');
        
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_start_nonguided_session',
            template_id: templateId,
            nonce: sixlab_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error starting lab: ' + response.data.message);
                button.prop('disabled', false).html('<i class="dashicons dashicons-controls-play"></i> Start Lab');
            }
        });
    });
    
    // Verify work
    $('#verify-work-btn').on('click', function() {
        if (!sessionId) {
            alert('<?php esc_js(__('No active session found.', 'sixlab-tool')); ?>');
            return;
        }
        
        const button = $(this);
        const panel = $('#verification-panel');
        const progress = $('#verification-progress');
        const results = $('#verification-results');
        const feedback = $('#ai-feedback');
        
        button.prop('disabled', true);
        panel.show();
        progress.show();
        results.hide();
        feedback.hide();
        
        $('#verification-status-message').html('<strong><?php esc_js(__('Running verification script...', 'sixlab-tool')); ?></strong>');
        
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_run_verification_script',
            session_id: sessionId,
            template_id: <?php echo $template_id; ?>,
            nonce: '<?php echo wp_create_nonce('sixlab_verification_script'); ?>'
        }, function(response) {
            progress.hide();
            
            if (response.success) {
                const data = response.data;
                
                // Show verification results
                results.find('.results-content').html(data.verification_output || '<?php esc_js(__('Verification completed.', 'sixlab-tool')); ?>');
                results.show();
                
                // Show AI feedback if available
                if (data.ai_feedback) {
                    feedback.find('.feedback-content').html(data.ai_feedback);
                    feedback.show();
                }
                
                $('#verification-status-message').html('<strong style="color: #28a745;"><?php esc_js(__('Verification completed successfully!', 'sixlab-tool')); ?></strong>');
                
                if (data.score !== undefined) {
                    $('#verification-status-message').append('<br><span><?php esc_js(__('Score:', 'sixlab-tool')); ?> ' + data.score + '%</span>');
                }
                
            } else {
                $('#verification-status-message').html('<strong style="color: #dc3545;"><?php esc_js(__('Verification failed:', 'sixlab-tool')); ?> ' + response.data.message + '</strong>');
            }
            
            button.prop('disabled', false);
        }).fail(function() {
            progress.hide();
            $('#verification-status-message').html('<strong style="color: #dc3545;"><?php esc_js(__('Network error occurred during verification.', 'sixlab-tool')); ?></strong>');
            button.prop('disabled', false);
        });
    });
    
    // Save notes
    $('.save-notes-btn').on('click', function() {
        if (!sessionId) {
            alert('<?php esc_js(__('No active session found.', 'sixlab-tool')); ?>');
            return;
        }
        
        const notes = $('#progress-notes').val();
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="dashicons dashicons-update"></i> Saving...');
        
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_save_session_notes',
            session_id: sessionId,
            notes: notes,
            nonce: sixlab_ajax.nonce
        }, function(response) {
            if (response.success) {
                button.html('<i class="dashicons dashicons-yes"></i> Saved!');
                setTimeout(function() {
                    button.prop('disabled', false).html('<i class="dashicons dashicons-saved"></i> Save Notes');
                }, 2000);
            } else {
                alert('Error saving notes: ' + response.data.message);
                button.prop('disabled', false).html('<i class="dashicons dashicons-saved"></i> Save Notes');
            }
        });
    });
    
    // Reset lab
    $('.reset-lab').on('click', function() {
        const sessionId = $(this).data('session-id');
        const templateId = $(this).data('template-id');
        
        if (confirm('<?php esc_js(__('Are you sure you want to reset the lab environment? This will clear all progress and start over.', 'sixlab-tool')); ?>')) {
            const button = $(this);
            button.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-spin"></i> <?php esc_js(__('Resetting...', 'sixlab-tool')); ?>');
            
            $.post(sixlab_ajax.ajax_url, {
                action: 'sixlab_run_reset_script',
                session_id: sessionId,
                template_id: templateId,
                nonce: '<?php echo wp_create_nonce('sixlab_reset_script'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php esc_js(__('Lab environment has been reset successfully. Reloading...', 'sixlab-tool')); ?>');
                    location.reload();
                } else {
                    alert('Error resetting lab: ' + response.data.message);
                    button.prop('disabled', false).html('<i class="dashicons dashicons-update"></i> <?php esc_js(__('Reset Lab', 'sixlab-tool')); ?>');
                }
            }).fail(function() {
                alert('<?php esc_js(__('Network error occurred while resetting the lab.', 'sixlab-tool')); ?>');
                button.prop('disabled', false).html('<i class="dashicons dashicons-update"></i> <?php esc_js(__('Reset Lab', 'sixlab-tool')); ?>');
            });
        }
    });
    
    // End lab
    $('#end-lab-btn').on('click', function() {
        if (confirm('<?php esc_js(__('Are you sure you want to end this lab session? All unsaved progress will be lost.', 'sixlab-tool')); ?>')) {
            $.post(sixlab_ajax.ajax_url, {
                action: 'sixlab_end_session',
                session_id: sessionId,
                nonce: sixlab_ajax.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error ending lab: ' + response.data.message);
                }
            });
        }
    });
    
    // Provider-specific access buttons
    $('.access-desktop-btn, .access-gns3-btn, .access-eveng-btn').on('click', function() {
        if (!sessionId) {
            alert('<?php esc_js(__('No active session found.', 'sixlab-tool')); ?>');
            return;
        }
        
        // This would typically open the lab environment in a new window/tab
        // Implementation depends on the specific provider
        window.open('#', '_blank');
    });
});
</script>

<?php get_footer(); ?>