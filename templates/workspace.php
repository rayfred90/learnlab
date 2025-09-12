<?php
/**
 * Frontend Lab Workspace Interface
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the current user and lab session
$current_user = wp_get_current_user();
$lab_session = get_query_var('lab_session');
$lab_provider = get_query_var('lab_provider', 'gns3');

// Verify user has access to this lab
if (!current_user_can('access_sixlab') || !$lab_session) {
    wp_die(__('Access denied. You do not have permission to access this lab.', 'sixlab-tool'));
}

// Get lab session data
$session_data = get_transient('sixlab_session_' . $lab_session);
if (!$session_data) {
    wp_die(__('Lab session not found or expired.', 'sixlab-tool'));
}

// Get lab provider instance
$provider_factory = new SixLab_Provider_Factory();
$provider = $provider_factory->get_provider($lab_provider);

if (is_wp_error($provider) || !$provider) {
    $error_message = is_wp_error($provider) 
        ? $provider->get_error_message() 
        : __('Lab provider not available.', 'sixlab-tool');
    wp_die($error_message);
}

// Get AI provider if enabled
$ai_enabled = get_option('sixlab_ai_assistant_enabled', 1);
$default_ai_provider = get_option('sixlab_default_ai_provider', '');
$ai_provider = null;

if ($ai_enabled && $default_ai_provider) {
    $ai_factory = new SixLab_AI_Provider_Factory();
    $ai_provider = $ai_factory->get_provider($default_ai_provider);
    
    // Check if AI provider creation failed - don't die, just disable AI
    if (is_wp_error($ai_provider)) {
        $ai_provider = null;
    }
}

// Enqueue required scripts and styles
wp_enqueue_script('sixlab-workspace', SIXLAB_PLUGIN_URL . 'assets/js/workspace.js', array('jquery'), SIXLAB_VERSION, true);
wp_enqueue_style('sixlab-workspace', SIXLAB_PLUGIN_URL . 'assets/css/workspace.css', array(), SIXLAB_VERSION);

// Localize script with data
wp_localize_script('sixlab-workspace', 'sixlab_workspace', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('sixlab_workspace_nonce'),
    'session_id' => $lab_session,
    'provider_type' => $lab_provider,
    'ai_enabled' => $ai_enabled,
    'ai_provider' => $default_ai_provider,
    'user_id' => $current_user->ID,
    'strings' => array(
        'loading' => __('Loading...', 'sixlab-tool'),
        'error' => __('Error', 'sixlab-tool'),
        'connecting' => __('Connecting to lab...', 'sixlab-tool'),
        'connected' => __('Connected', 'sixlab-tool'),
        'disconnected' => __('Disconnected', 'sixlab-tool'),
        'ask_ai' => __('Ask AI Assistant', 'sixlab-tool'),
        'send_message' => __('Send', 'sixlab-tool'),
        'clear_chat' => __('Clear Chat', 'sixlab-tool'),
        'minimize_chat' => __('Minimize', 'sixlab-tool'),
        'maximize_chat' => __('Maximize', 'sixlab-tool'),
        'end_session' => __('End Session', 'sixlab-tool'),
        'save_progress' => __('Save Progress', 'sixlab-tool'),
        'take_screenshot' => __('Screenshot', 'sixlab-tool'),
        'download_config' => __('Download Config', 'sixlab-tool'),
        'session_ended' => __('Lab session has ended', 'sixlab-tool'),
        'confirm_end' => __('Are you sure you want to end this lab session?', 'sixlab-tool')
    )
));

get_header();
?>

<div id="sixlab-workspace" class="sixlab-workspace">
    <!-- Workspace Header -->
    <div class="sixlab-header">
        <div class="sixlab-header-left">
            <h1 class="sixlab-lab-title"><?php echo esc_html($session_data['lab_name'] ?? __('Lab Session', 'sixlab-tool')); ?></h1>
            <div class="sixlab-session-info">
                <span class="sixlab-provider-badge"><?php echo esc_html($provider->get_display_name()); ?></span>
                <span class="sixlab-session-id">Session: <?php echo esc_html($lab_session); ?></span>
                <span id="sixlab-connection-status" class="sixlab-status disconnected">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php _e('Connecting...', 'sixlab-tool'); ?></span>
                </span>
            </div>
        </div>
        
        <div class="sixlab-header-right">
            <div class="sixlab-timer">
                <span id="sixlab-session-timer">00:00:00</span>
            </div>
            
            <div class="sixlab-controls">
                <?php if ($provider->supports_feature('snapshot_support')): ?>
                    <button id="sixlab-take-screenshot" class="sixlab-btn sixlab-btn-secondary" title="<?php _e('Take Screenshot', 'sixlab-tool'); ?>">
                        <span class="dashicons dashicons-camera"></span>
                    </button>
                <?php endif; ?>
                
                <?php if ($provider->supports_feature('configuration_backup')): ?>
                    <button id="sixlab-download-config" class="sixlab-btn sixlab-btn-secondary" title="<?php _e('Download Configuration', 'sixlab-tool'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                <?php endif; ?>
                
                <button id="sixlab-save-progress" class="sixlab-btn sixlab-btn-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Progress', 'sixlab-tool'); ?>
                </button>
                
                <button id="sixlab-end-session" class="sixlab-btn sixlab-btn-danger">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('End Session', 'sixlab-tool'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Workspace Content -->
    <div class="sixlab-content">
        <!-- Lab Environment Panel -->
        <div class="sixlab-panel sixlab-lab-panel" id="sixlab-lab-panel">
            <div class="sixlab-panel-header">
                <h3><?php _e('Lab Environment', 'sixlab-tool'); ?></h3>
                <div class="sixlab-panel-controls">
                    <button class="sixlab-panel-toggle" data-panel="lab">
                        <span class="dashicons dashicons-minus"></span>
                    </button>
                </div>
            </div>
            
            <div class="sixlab-panel-content">
                <div id="sixlab-lab-loading" class="sixlab-loading">
                    <div class="sixlab-spinner"></div>
                    <p><?php _e('Loading lab environment...', 'sixlab-tool'); ?></p>
                </div>
                
                <div id="sixlab-lab-iframe-container" class="sixlab-iframe-container" style="display: none;">
                    <iframe id="sixlab-lab-iframe" 
                            src="" 
                            frameborder="0" 
                            allowfullscreen>
                    </iframe>
                </div>
                
                <div id="sixlab-lab-error" class="sixlab-error-message" style="display: none;">
                    <div class="sixlab-error-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="sixlab-error-content">
                        <h4><?php _e('Connection Error', 'sixlab-tool'); ?></h4>
                        <p id="sixlab-error-text"></p>
                        <button id="sixlab-retry-connection" class="sixlab-btn sixlab-btn-primary">
                            <?php _e('Retry Connection', 'sixlab-tool'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lab Instructions Panel -->
        <div class="sixlab-panel sixlab-instructions-panel" id="sixlab-instructions-panel">
            <div class="sixlab-panel-header">
                <h3><?php _e('Lab Instructions', 'sixlab-tool'); ?></h3>
                <div class="sixlab-panel-controls">
                    <button class="sixlab-panel-toggle" data-panel="instructions">
                        <span class="dashicons dashicons-minus"></span>
                    </button>
                </div>
            </div>
            
            <div class="sixlab-panel-content">
                <div class="sixlab-instructions-content">
                    <?php if (!empty($session_data['instructions'])): ?>
                        <?php echo wp_kses_post($session_data['instructions']); ?>
                    <?php else: ?>
                        <p><?php _e('No instructions available for this lab.', 'sixlab-tool'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Progress Tracking -->
                <div class="sixlab-progress-section">
                    <h4><?php _e('Progress Tracking', 'sixlab-tool'); ?></h4>
                    <div class="sixlab-checklist" id="sixlab-progress-checklist">
                        <!-- Progress items will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- AI Assistant Chat Widget -->
    <?php if ($ai_enabled && $ai_provider): ?>
        <div id="sixlab-ai-chat" class="sixlab-ai-chat minimized">
            <div class="sixlab-chat-header">
                <div class="sixlab-chat-title">
                    <span class="dashicons dashicons-format-chat"></span>
                    <span><?php _e('AI Assistant', 'sixlab-tool'); ?></span>
                    <span class="sixlab-ai-provider-badge"><?php echo esc_html($ai_provider->get_display_name()); ?></span>
                </div>
                <div class="sixlab-chat-controls">
                    <button id="sixlab-clear-chat" class="sixlab-chat-btn" title="<?php _e('Clear Chat', 'sixlab-tool'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                    <button id="sixlab-minimize-chat" class="sixlab-chat-btn" title="<?php _e('Minimize', 'sixlab-tool'); ?>">
                        <span class="dashicons dashicons-minus"></span>
                    </button>
                </div>
            </div>
            
            <div class="sixlab-chat-body">
                <div id="sixlab-chat-messages" class="sixlab-chat-messages">
                    <div class="sixlab-chat-message sixlab-ai-message">
                        <div class="sixlab-message-avatar">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </div>
                        <div class="sixlab-message-content">
                            <p><?php _e('Hello! I\'m your AI assistant. I can help you with lab tasks, explain concepts, troubleshoot issues, and provide guidance. How can I assist you today?', 'sixlab-tool'); ?></p>
                        </div>
                        <div class="sixlab-message-time">
                            <?php echo current_time('H:i'); ?>
                        </div>
                    </div>
                </div>
                
                <div class="sixlab-chat-input-container">
                    <div class="sixlab-chat-input-wrapper">
                        <textarea id="sixlab-chat-input" 
                                  placeholder="<?php _e('Ask me anything about this lab...', 'sixlab-tool'); ?>"
                                  rows="1"></textarea>
                        <button id="sixlab-send-message" class="sixlab-send-btn">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </button>
                    </div>
                    
                    <div class="sixlab-chat-suggestions">
                        <button class="sixlab-suggestion-btn" data-message="<?php _e('Explain this configuration', 'sixlab-tool'); ?>">
                            <?php _e('Explain this configuration', 'sixlab-tool'); ?>
                        </button>
                        <button class="sixlab-suggestion-btn" data-message="<?php _e('What should I do next?', 'sixlab-tool'); ?>">
                            <?php _e('What should I do next?', 'sixlab-tool'); ?>
                        </button>
                        <button class="sixlab-suggestion-btn" data-message="<?php _e('Help me troubleshoot', 'sixlab-tool'); ?>">
                            <?php _e('Help me troubleshoot', 'sixlab-tool'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Chat Toggle Button (when minimized) -->
            <div class="sixlab-chat-toggle" style="display: none;">
                <button id="sixlab-maximize-chat" class="sixlab-chat-toggle-btn">
                    <span class="dashicons dashicons-format-chat"></span>
                    <span class="sixlab-toggle-text"><?php _e('AI Assistant', 'sixlab-tool'); ?></span>
                    <span id="sixlab-unread-indicator" class="sixlab-unread-indicator" style="display: none;"></span>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Progress Modal -->
    <div id="sixlab-progress-modal" class="sixlab-modal" style="display: none;">
        <div class="sixlab-modal-content">
            <div class="sixlab-modal-header">
                <h3><?php _e('Save Progress', 'sixlab-tool'); ?></h3>
                <button class="sixlab-modal-close">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="sixlab-modal-body">
                <form id="sixlab-progress-form">
                    <div class="sixlab-form-group">
                        <label for="progress-notes"><?php _e('Progress Notes:', 'sixlab-tool'); ?></label>
                        <textarea id="progress-notes" name="progress_notes" rows="4" 
                                  placeholder="<?php _e('Describe what you\'ve accomplished in this session...', 'sixlab-tool'); ?>"></textarea>
                    </div>
                    
                    <div class="sixlab-form-group">
                        <label>
                            <input type="checkbox" id="include-screenshot" name="include_screenshot" checked>
                            <?php _e('Include screenshot with progress', 'sixlab-tool'); ?>
                        </label>
                    </div>
                    
                    <div class="sixlab-form-actions">
                        <button type="button" class="sixlab-btn sixlab-btn-secondary sixlab-modal-close">
                            <?php _e('Cancel', 'sixlab-tool'); ?>
                        </button>
                        <button type="submit" class="sixlab-btn sixlab-btn-primary">
                            <?php _e('Save Progress', 'sixlab-tool'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="sixlab-loading-overlay" class="sixlab-loading-overlay" style="display: none;">
        <div class="sixlab-loading-content">
            <div class="sixlab-spinner"></div>
            <p id="sixlab-loading-text"><?php _e('Loading...', 'sixlab-tool'); ?></p>
        </div>
    </div>
</div>

<script>
// Initialize workspace when document is ready
jQuery(document).ready(function($) {
    SixLabWorkspace.init();
});
</script>

<?php get_footer(); ?>
