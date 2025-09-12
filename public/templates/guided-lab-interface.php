<?php
/**
 * Guided Lab Interface Template
 * 
 * Provides step-by-step interface for guided labs with terminal command validation
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

if (!$template || $template->template_type !== 'guided') {
    wp_die(__('Guided lab template not found.', 'sixlab-tool'));
}

// Parse guided steps
$guided_steps = json_decode($template->guided_steps, true) ?? array();

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

$current_step = $current_session ? intval($current_session->current_step) : 1;
$completed_steps = $current_session ? json_decode($current_session->completed_steps, true) ?? array() : array();
$session_id = $current_session ? $current_session->id : null;

get_header();
?>

<div class="sixlab-guided-interface">
    <div class="guided-header">
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
        </div>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo count($guided_steps) ? (count($completed_steps) / count($guided_steps)) * 100 : 0; ?>%"></div>
            </div>
            <div class="progress-text">
                <?php printf(__('Progress: %d/%d steps completed', 'sixlab-tool'), count($completed_steps), count($guided_steps)); ?>
            </div>
        </div>
    </div>

    <div class="guided-content">
        <div class="guided-sidebar">
            <h3><?php _e('Lab Steps', 'sixlab-tool'); ?></h3>
            <ul class="steps-navigation">
                <?php foreach ($guided_steps as $index => $step): ?>
                    <li class="step-nav-item <?php echo $index + 1 === $current_step ? 'current' : ''; ?> <?php echo in_array($index + 1, $completed_steps) ? 'completed' : ''; ?>">
                        <span class="step-number"><?php echo $index + 1; ?></span>
                        <span class="step-title"><?php echo esc_html($step['title']); ?></span>
                        <?php if (in_array($index + 1, $completed_steps)): ?>
                            <i class="dashicons dashicons-yes"></i>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="lab-info">
                <h4><?php _e('Lab Information', 'sixlab-tool'); ?></h4>
                <p><?php echo esc_html($template->description); ?></p>
                
                <?php if ($template->learning_objectives): ?>
                    <h4><?php _e('Learning Objectives', 'sixlab-tool'); ?></h4>
                    <ul>
                        <?php 
                        $objectives = json_decode($template->learning_objectives, true);
                        if (is_array($objectives)) {
                            foreach ($objectives as $objective) {
                                echo '<li>' . esc_html($objective) . '</li>';
                            }
                        } else {
                            echo '<li>' . esc_html($template->learning_objectives) . '</li>';
                        }
                        ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="guided-main">
            <?php if (!$session_id): ?>
                <div class="session-start">
                    <h2><?php _e('Ready to Start?', 'sixlab-tool'); ?></h2>
                    <p><?php _e('Click the button below to start your guided lab session.', 'sixlab-tool'); ?></p>
                    <button type="button" class="button button-primary button-large" id="start-lab-btn" data-template-id="<?php echo esc_attr($template_id); ?>">
                        <?php _e('Start Lab', 'sixlab-tool'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="current-step-container">
                    <?php if (isset($guided_steps[$current_step - 1])): ?>
                        <?php $step = $guided_steps[$current_step - 1]; ?>
                        
                        <div class="step-header">
                            <h2><?php printf(__('Step %d: %s', 'sixlab-tool'), $current_step, esc_html($step['title'])); ?></h2>
                        </div>
                        
                        <div class="step-instructions">
                            <h3><?php _e('Instructions', 'sixlab-tool'); ?></h3>
                            <div class="instruction-content">
                                <?php echo wpautop(esc_html($step['instructions'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($step['commands'])): ?>
                            <div class="step-commands">
                                <h3><?php _e('Commands to Execute', 'sixlab-tool'); ?></h3>
                                <div class="commands-container">
                                    <?php 
                                    $commands = array_filter(array_map('trim', explode("\n", $step['commands'])));
                                    foreach ($commands as $command): 
                                    ?>
                                        <div class="command-block">
                                            <code class="command-text"><?php echo esc_html($command); ?></code>
                                            <button type="button" class="button copy-command" data-command="<?php echo esc_attr($command); ?>">
                                                <i class="dashicons dashicons-admin-page"></i> <?php _e('Copy', 'sixlab-tool'); ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="terminal-interface">
                            <h3><?php _e('Terminal', 'sixlab-tool'); ?></h3>
                            <div class="terminal-container">
                                <div class="terminal-header">
                                    <span class="terminal-title"><?php _e('Lab Terminal', 'sixlab-tool'); ?></span>
                                    <button type="button" class="button button-small clear-terminal">
                                        <?php _e('Clear', 'sixlab-tool'); ?>
                                    </button>
                                </div>
                                <div class="terminal-output" id="terminal-output"></div>
                                <div class="terminal-input">
                                    <span class="terminal-prompt">student@lab:~$ </span>
                                    <input type="text" id="terminal-command" placeholder="<?php esc_attr_e('Enter command...', 'sixlab-tool'); ?>" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($step['validation'])): ?>
                            <div class="step-validation">
                                <h3><?php _e('Expected Results', 'sixlab-tool'); ?></h3>
                                <div class="validation-content">
                                    <?php echo wpautop(esc_html($step['validation'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="step-actions">
                            <?php if ($current_step > 1): ?>
                                <button type="button" class="button previous-step" data-step="<?php echo $current_step - 1; ?>">
                                    <i class="dashicons dashicons-arrow-left-alt"></i> <?php _e('Previous Step', 'sixlab-tool'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="button button-primary validate-step" data-step="<?php echo $current_step; ?>">
                                <?php _e('Validate & Continue', 'sixlab-tool'); ?>
                            </button>
                            
                            <button type="button" class="button skip-step" data-step="<?php echo $current_step; ?>">
                                <?php _e('Skip Step', 'sixlab-tool'); ?>
                            </button>
                            
                            <button type="button" class="button button-secondary reset-lab" 
                                    data-session-id="<?php echo esc_attr($session_id); ?>" 
                                    data-template-id="<?php echo esc_attr($template_id); ?>"
                                    title="<?php esc_attr_e('Reset lab environment to start over', 'sixlab-tool'); ?>">
                                <i class="dashicons dashicons-update"></i> <?php _e('Reset Lab', 'sixlab-tool'); ?>
                            </button>
                        </div>
                        
                    <?php else: ?>
                        <div class="lab-completed">
                            <h2><?php _e('Congratulations!', 'sixlab-tool'); ?></h2>
                            <p><?php _e('You have completed all steps in this guided lab.', 'sixlab-tool'); ?></p>
                            <button type="button" class="button button-primary complete-lab">
                                <?php _e('Complete Lab', 'sixlab-tool'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.sixlab-guided-interface {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.guided-header {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.guided-header h1 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 2.5em;
}

.lab-meta {
    margin: 15px 0;
}

.difficulty-badge, .duration-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    margin-right: 10px;
}

.difficulty-beginner { background: #e8f5e8; color: #2d5a2d; }
.difficulty-intermediate { background: #fff3cd; color: #856404; }
.difficulty-advanced { background: #f8d7da; color: #721c24; }

.duration-badge {
    background: #e7f3ff;
    color: #0056b3;
}

.progress-container {
    margin-top: 20px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s ease;
}

.progress-text {
    margin-top: 8px;
    font-size: 0.9em;
    color: #6c757d;
}

.guided-content {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    align-items: start;
}

.guided-sidebar {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 20px;
}

.steps-navigation {
    list-style: none;
    padding: 0;
    margin: 0 0 30px 0;
}

.step-nav-item {
    display: flex;
    align-items: center;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.step-nav-item.current {
    background: #e7f3ff;
    border-color: #0056b3;
}

.step-nav-item.completed {
    background: #e8f5e8;
    border-color: #28a745;
}

.step-number {
    background: #6c757d;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8em;
    margin-right: 10px;
}

.step-nav-item.current .step-number {
    background: #0056b3;
}

.step-nav-item.completed .step-number {
    background: #28a745;
}

.step-title {
    flex: 1;
    font-size: 0.9em;
}

.guided-main {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.session-start {
    text-align: center;
    padding: 60px 20px;
}

.session-start h2 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.step-header h2 {
    color: #2c3e50;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

.step-instructions,
.step-commands,
.step-validation {
    margin: 25px 0;
}

.step-instructions h3,
.step-commands h3,
.step-validation h3 {
    color: #495057;
    margin-bottom: 15px;
}

.instruction-content,
.validation-content {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #0056b3;
}

.commands-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #28a745;
}

.command-block {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #2d3748;
    color: #e2e8f0;
    padding: 12px 16px;
    border-radius: 4px;
    margin: 10px 0;
    font-family: 'Courier New', monospace;
}

.command-text {
    flex: 1;
    background: none;
    color: inherit;
    font-family: inherit;
}

.copy-command {
    margin-left: 15px;
    background: #4a5568;
    color: #e2e8f0;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.8em;
}

.copy-command:hover {
    background: #2d3748;
}

.terminal-interface {
    margin: 30px 0;
}

.terminal-container {
    background: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
    font-family: 'Courier New', monospace;
}

.terminal-header {
    background: #2d3748;
    color: #e2e8f0;
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.terminal-output {
    background: #1a1a1a;
    color: #00ff00;
    padding: 15px;
    min-height: 200px;
    max-height: 400px;
    overflow-y: auto;
    font-size: 14px;
    line-height: 1.4;
}

.terminal-input {
    background: #1a1a1a;
    color: #00ff00;
    padding: 15px;
    display: flex;
    align-items: center;
    border-top: 1px solid #2d3748;
}

.terminal-prompt {
    color: #00ff00;
    margin-right: 8px;
}

#terminal-command {
    flex: 1;
    background: transparent;
    border: none;
    color: #00ff00;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    outline: none;
}

.step-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 15px;
}

.lab-completed {
    text-align: center;
    padding: 60px 20px;
}

.lab-completed h2 {
    color: #28a745;
    font-size: 2.5em;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .guided-content {
        grid-template-columns: 1fr;
    }
    
    .guided-sidebar {
        position: static;
    }
    
    .step-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let commandHistory = [];
    let sessionId = <?php echo $session_id ? $session_id : 'null'; ?>;
    let currentStep = <?php echo $current_step; ?>;
    
    // Start lab session
    $('#start-lab-btn').on('click', function() {
        const templateId = $(this).data('template-id');
        
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_start_guided_session',
            template_id: templateId,
            nonce: sixlab_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error starting lab: ' + response.data.message);
            }
        });
    });
    
    // Terminal command handling
    $('#terminal-command').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            const command = $(this).val().trim();
            if (command) {
                executeCommand(command);
                $(this).val('');
            }
        }
    });
    
    // Copy command functionality
    $('.copy-command').on('click', function() {
        const command = $(this).data('command');
        $('#terminal-command').val(command);
        $('#terminal-command').focus();
    });
    
    // Clear terminal
    $('.clear-terminal').on('click', function() {
        $('#terminal-output').empty();
    });
    
    // Step validation
    $('.validate-step').on('click', function() {
        const step = $(this).data('step');
        validateStep(step);
    });
    
    // Skip step
    $('.skip-step').on('click', function() {
        const step = $(this).data('step');
        skipStep(step);
    });
    
    // Previous step
    $('.previous-step').on('click', function() {
        const step = $(this).data('step');
        goToStep(step);
    });
    
    // Complete lab
    $('.complete-lab').on('click', function() {
        completeLab();
    });
    
    // Reset lab
    $('.reset-lab').on('click', function() {
        const sessionId = $(this).data('session-id');
        const templateId = $(this).data('template-id');
        
        if (confirm('<?php esc_js(__('Are you sure you want to reset the lab environment? This will clear all progress and start over.', 'sixlab-tool')); ?>')) {
            resetLab(sessionId, templateId);
        }
    });
    
    function executeCommand(command) {
        // Add command to history
        commandHistory.push(command);
        
        // Display command in terminal
        addToTerminal('student@lab:~$ ' + command);
        
        // Send command to backend for processing
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_execute_command',
            session_id: sessionId,
            command: command,
            step: currentStep,
            nonce: sixlab_ajax.nonce
        }, function(response) {
            if (response.success) {
                addToTerminal(response.data.output);
                
                // Check if step is completed
                if (response.data.step_completed) {
                    showStepCompletedMessage();
                }
            } else {
                addToTerminal('Error: ' + response.data.message);
            }
        });
    }
    
    function addToTerminal(text) {
        const output = $('#terminal-output');
        output.append('<div>' + text + '</div>');
        output.scrollTop(output[0].scrollHeight);
    }
    
    function validateStep(step) {
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_validate_step',
            session_id: sessionId,
            step: step,
            command_history: commandHistory,
            nonce: sixlab_ajax.nonce
        }, function(response) {
            if (response.success) {
                if (response.data.valid) {
                    // Step completed, move to next
                    currentStep++;
                    location.reload();
                } else {
                    alert('Step validation failed: ' + response.data.message);
                }
            } else {
                alert('Error validating step: ' + response.data.message);
            }
        });
    }
    
    function skipStep(step) {
        if (confirm('<?php esc_js(__('Are you sure you want to skip this step?', 'sixlab-tool')); ?>')) {
            $.post(sixlab_ajax.ajax_url, {
                action: 'sixlab_skip_step',
                session_id: sessionId,
                step: step,
                nonce: sixlab_ajax.nonce
            }, function(response) {
                if (response.success) {
                    currentStep++;
                    location.reload();
                } else {
                    alert('Error skipping step: ' + response.data.message);
                }
            });
        }
    }
    
    function goToStep(step) {
        window.location.href = '<?php echo get_permalink(); ?>?step=' + step;
    }
    
    function completeLab() {
        $.post(sixlab_ajax.ajax_url, {
            action: 'sixlab_complete_lab',
            session_id: sessionId,
            nonce: sixlab_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('<?php esc_js(__('Congratulations! Lab completed successfully.', 'sixlab-tool')); ?>');
                window.location.href = '<?php echo home_url(); ?>';
            } else {
                alert('Error completing lab: ' + response.data.message);
            }
        });
    }
    
    function resetLab(sessionId, templateId) {
        // Show loading state
        $('.reset-lab').prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-spin"></i> <?php esc_js(__('Resetting...', 'sixlab-tool')); ?>');
        
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
                // Restore button state
                $('.reset-lab').prop('disabled', false).html('<i class="dashicons dashicons-update"></i> <?php esc_js(__('Reset Lab', 'sixlab-tool')); ?>');
            }
        }).fail(function() {
            alert('<?php esc_js(__('Network error occurred while resetting the lab.', 'sixlab-tool')); ?>');
            // Restore button state
            $('.reset-lab').prop('disabled', false).html('<i class="dashicons dashicons-update"></i> <?php esc_js(__('Reset Lab', 'sixlab-tool')); ?>');
        });
    }
    
    function showStepCompletedMessage() {
        addToTerminal('<span style="color: #28a745; font-weight: bold;">✓ Step completed! You can proceed to validation.</span>');
    }
});
</script>

<?php get_footer(); ?>