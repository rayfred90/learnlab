<?php
/**
 * Enhanced Lab Interface - Real Terminal with Provider Connection
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current session and template data
$session_id = $_GET['session'] ?? '';
$current_user = wp_get_current_user();

if (!$session_id || !$current_user->ID) {
    wp_die(__('Invalid session or user not logged in.', 'sixlab-tool'));
}

// Get session data from database
global $wpdb;
$sessions_table = $wpdb->prefix . 'sixlab_sessions';
$templates_table = $wpdb->prefix . 'sixlab_lab_templates';

$session = $wpdb->get_row($wpdb->prepare("
    SELECT s.*, t.name as template_name, t.description, t.instructions, 
           t.template_data, t.provider_type, t.estimated_duration
    FROM {$sessions_table} s
    LEFT JOIN {$templates_table} t ON s.template_id = t.id
    WHERE s.id = %s AND s.user_id = %d
", $session_id, $current_user->ID));

if (!$session) {
    wp_die(__('Session not found or access denied.', 'sixlab-tool'));
}

// Parse template data
$template_data = json_decode($session->template_data, true) ?? array();
$lab_config = $template_data['lab_config'] ?? array();
$connection_info = $template_data['connection'] ?? array();

// Get provider configuration
$provider_config = get_option('sixlab_providers_config', array());
$provider_settings = $provider_config[$session->provider_type] ?? array();

// Enqueue necessary scripts
wp_enqueue_script('xterm', 'https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js', array(), '5.3.0', true);
wp_enqueue_script('xterm-addon-fit', 'https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js', array('xterm'), '0.8.0', true);
wp_enqueue_script('xterm-addon-web-links', 'https://cdn.jsdelivr.net/npm/xterm-addon-web-links@0.9.0/lib/xterm-addon-web-links.min.js', array('xterm'), '0.9.0', true);

wp_enqueue_style('xterm-css', 'https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css', array(), '5.3.0');

wp_enqueue_script('sixlab-enhanced-interface', SIXLAB_PLUGIN_URL . 'public/js/enhanced-interface.js', array('jquery', 'xterm'), SIXLAB_PLUGIN_VERSION, true);
wp_enqueue_style('sixlab-enhanced-interface', SIXLAB_PLUGIN_URL . 'public/css/enhanced-interface.css', array('xterm-css'), SIXLAB_PLUGIN_VERSION);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($session->template_name); ?> - 6Lab Tool</title>
    <?php wp_head(); ?>
</head>
<body class="sixlab-lab-interface-body">
    <div class="sixlab-enhanced-interface" id="sixlab-interface">
        
        <!-- Header -->
        <header class="sixlab-interface-header">
            <div class="sixlab-header-content">
                <div class="sixlab-lab-info">
                    <div class="sixlab-lab-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="sixlab-lab-details">
                        <h1 class="sixlab-lab-title"><?php echo esc_html($session->template_name); ?></h1>
                        <div class="sixlab-lab-meta">
                            <span class="sixlab-provider">
                                <i class="fas fa-server"></i>
                                <?php echo esc_html(ucfirst($session->provider_type)); ?>
                            </span>
                            <span class="sixlab-session-id">
                                <i class="fas fa-fingerprint"></i>
                                Session: <?php echo esc_html($session_id); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="sixlab-header-controls">
                    <div class="sixlab-connection-status" id="connection-status">
                        <div class="status-indicator disconnected" id="status-indicator"></div>
                        <span class="status-text" id="status-text">Disconnected</span>
                    </div>
                    
                    <div class="sixlab-timer">
                        <i class="fas fa-clock"></i>
                        <span id="session-timer">00:00:00</span>
                    </div>
                    
                    <div class="sixlab-header-actions">
                        <button class="sixlab-action-btn" id="take-screenshot" title="Take Screenshot">
                            <i class="fas fa-camera"></i>
                        </button>
                        <button class="sixlab-action-btn" id="save-progress" title="Save Progress">
                            <i class="fas fa-save"></i>
                        </button>
                        <button class="sixlab-action-btn" id="reset-lab" title="Reset Lab">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="sixlab-action-btn danger" id="end-session" title="End Session">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="sixlab-interface-main">
            
            <!-- Sidebar -->
            <aside class="sixlab-sidebar" id="sidebar">
                <div class="sixlab-sidebar-header">
                    <div class="sixlab-sidebar-tabs">
                        <button class="sixlab-tab-btn active" data-tab="instructions">
                            <i class="fas fa-list-check"></i>
                            Instructions
                        </button>
                        <button class="sixlab-tab-btn" data-tab="progress">
                            <i class="fas fa-chart-line"></i>
                            Progress
                        </button>
                        <button class="sixlab-tab-btn" data-tab="assistant">
                            <i class="fas fa-robot"></i>
                            AI Assistant
                        </button>
                    </div>
                    <button class="sixlab-sidebar-toggle" id="sidebar-toggle">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
                
                <div class="sixlab-sidebar-content">
                    <!-- Instructions Tab -->
                    <div class="sixlab-tab-content active" id="instructions-tab">
                        <div class="sixlab-instructions">
                            <?php if (!empty($session->instructions)): ?>
                                <?php echo wp_kses_post(wpautop($session->instructions)); ?>
                            <?php else: ?>
                                <p><?php _e('No specific instructions provided for this lab.', 'sixlab-tool'); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sixlab-objectives">
                            <h4><i class="fas fa-bullseye"></i> Lab Objectives</h4>
                            <div class="sixlab-objectives-list">
                                <?php
                                $objectives = $template_data['objectives'] ?? array();
                                if (empty($objectives)) {
                                    $objectives = array('Complete the lab exercises', 'Validate configurations', 'Test connectivity');
                                }
                                foreach ($objectives as $index => $objective): ?>
                                    <div class="sixlab-objective" data-step="<?php echo $index + 1; ?>">
                                        <div class="objective-marker">
                                            <span class="step-number"><?php echo $index + 1; ?></span>
                                            <div class="objective-status pending" id="objective-<?php echo $index + 1; ?>">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                        </div>
                                        <div class="objective-content">
                                            <?php echo esc_html($objective); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Tab -->
                    <div class="sixlab-tab-content" id="progress-tab">
                        <div class="sixlab-progress-overview">
                            <div class="progress-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="completed-tasks">0</div>
                                    <div class="stat-label">Tasks Completed</div>
                                </div>
                            </div>
                            
                            <div class="progress-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="completion-percentage">0%</div>
                                    <div class="stat-label">Completion</div>
                                </div>
                            </div>
                            
                            <div class="progress-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="time-spent">00:00</div>
                                    <div class="stat-label">Time Spent</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sixlab-validation-panel">
                            <h4><i class="fas fa-check-double"></i> Validation</h4>
                            <button class="sixlab-validate-btn" id="validate-configuration">
                                <i class="fas fa-play"></i>
                                Validate Current Step
                            </button>
                            <div class="validation-results" id="validation-results"></div>
                        </div>
                    </div>
                    
                    <!-- AI Assistant Tab -->
                    <div class="sixlab-tab-content" id="assistant-tab">
                        <div class="sixlab-ai-chat" id="ai-chat">
                            <div class="chat-messages" id="chat-messages">
                                <div class="chat-message assistant">
                                    <div class="message-avatar">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="message-content">
                                        <p>Hello! I'm your AI lab assistant. I can help you with questions about this lab, explain configurations, and provide hints when you're stuck.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="chat-input-container">
                                <div class="chat-suggestions">
                                    <button class="suggestion-btn" data-message="Explain this configuration">
                                        Explain this configuration
                                    </button>
                                    <button class="suggestion-btn" data-message="What should I do next?">
                                        What should I do next?
                                    </button>
                                    <button class="suggestion-btn" data-message="Help me troubleshoot">
                                        Help me troubleshoot
                                    </button>
                                </div>
                                
                                <div class="chat-input-wrapper">
                                    <textarea 
                                        id="chat-input" 
                                        placeholder="Ask me anything about this lab..."
                                        rows="2"></textarea>
                                    <button id="send-chat" class="chat-send-btn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
            
            <!-- Lab Content Area -->
            <section class="sixlab-lab-content">
                
                <!-- Connection Panel -->
                <div class="sixlab-connection-panel" id="connection-panel">
                    <div class="connection-header">
                        <h3>
                            <i class="fas fa-plug"></i>
                            Lab Environment Connection
                        </h3>
                        <div class="connection-info">
                            <?php if (!empty($connection_info)): ?>
                                <div class="connection-details">
                                    <span><strong>Host:</strong> <?php echo esc_html($connection_info['host'] ?? 'N/A'); ?></span>
                                    <span><strong>Protocol:</strong> <?php echo esc_html($connection_info['protocol'] ?? 'SSH'); ?></span>
                                    <?php if (!empty($connection_info['port'])): ?>
                                        <span><strong>Port:</strong> <?php echo esc_html($connection_info['port']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="connection-controls">
                        <button class="sixlab-connect-btn" id="connect-lab">
                            <i class="fas fa-play"></i>
                            Connect to Lab Environment
                        </button>
                        
                        <div class="connection-options">
                            <label>
                                <input type="checkbox" id="auto-reconnect" checked>
                                Auto-reconnect on disconnect
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Terminal Interface -->
                <div class="sixlab-terminal-container" id="terminal-container" style="display: none;">
                    <div class="terminal-header">
                        <div class="terminal-title">
                            <i class="fas fa-terminal"></i>
                            Lab Terminal
                        </div>
                        <div class="terminal-controls">
                            <button class="terminal-btn" id="clear-terminal" title="Clear Terminal">
                                <i class="fas fa-eraser"></i>
                            </button>
                            <button class="terminal-btn" id="copy-terminal" title="Copy Terminal Content">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button class="terminal-btn" id="paste-terminal" title="Paste to Terminal">
                                <i class="fas fa-paste"></i>
                            </button>
                            <button class="terminal-btn" id="fullscreen-terminal" title="Toggle Fullscreen">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="terminal-wrapper">
                        <div id="terminal" class="xterm-terminal"></div>
                    </div>
                </div>
                
                <!-- GUI Interface (for Guacamole/Web-based labs) -->
                <div class="sixlab-gui-container" id="gui-container" style="display: none;">
                    <div class="gui-header">
                        <div class="gui-title">
                            <i class="fas fa-desktop"></i>
                            Lab Desktop Environment
                        </div>
                        <div class="gui-controls">
                            <button class="gui-btn" id="refresh-gui" title="Refresh">
                                <i class="fas fa-sync"></i>
                            </button>
                            <button class="gui-btn" id="fullscreen-gui" title="Toggle Fullscreen">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="gui-wrapper">
                        <iframe id="gui-frame" src="" frameborder="0"></iframe>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div class="sixlab-loading-state" id="loading-state">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="loading-text">Initializing lab environment...</div>
                    <div class="loading-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="loading-progress"></div>
                        </div>
                        <div class="progress-text" id="loading-text">Connecting to provider...</div>
                    </div>
                </div>
                
                <!-- Error State -->
                <div class="sixlab-error-state" id="error-state" style="display: none;">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="error-title">Connection Failed</div>
                    <div class="error-message" id="error-message">Unable to connect to the lab environment.</div>
                    <div class="error-actions">
                        <button class="sixlab-retry-btn" id="retry-connection">
                            <i class="fas fa-redo"></i>
                            Retry Connection
                        </button>
                        <button class="sixlab-help-btn" id="get-help">
                            <i class="fas fa-question-circle"></i>
                            Get Help
                        </button>
                    </div>
                </div>
                
            </section>
        </main>
    </div>

    <script>
    // Pass data to JavaScript
    window.sixlabConfig = {
        sessionId: '<?php echo esc_js($session_id); ?>',
        templateId: '<?php echo esc_js($session->template_id); ?>',
        providerType: '<?php echo esc_js($session->provider_type); ?>',
        userId: <?php echo intval($current_user->ID); ?>,
        estimatedDuration: <?php echo intval($session->estimated_duration ?? 0); ?>,
        connectionInfo: <?php echo json_encode($connection_info); ?>,
        labConfig: <?php echo json_encode($lab_config); ?>,
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('sixlab_enhanced_nonce'); ?>',
        websocketUrl: '<?php echo get_option('sixlab_websocket_url', 'ws://localhost:8080'); ?>',
        strings: {
            connecting: '<?php _e('Connecting...', 'sixlab-tool'); ?>',
            connected: '<?php _e('Connected', 'sixlab-tool'); ?>',
            disconnected: '<?php _e('Disconnected', 'sixlab-tool'); ?>',
            reconnecting: '<?php _e('Reconnecting...', 'sixlab-tool'); ?>',
            error: '<?php _e('Error', 'sixlab-tool'); ?>',
            confirmEnd: '<?php _e('Are you sure you want to end this lab session?', 'sixlab-tool'); ?>',
            sessionEnded: '<?php _e('Lab session has ended', 'sixlab-tool'); ?>',
            progressSaved: '<?php _e('Progress saved successfully', 'sixlab-tool'); ?>',
            screenshotTaken: '<?php _e('Screenshot captured', 'sixlab-tool'); ?>',
            validationPassed: '<?php _e('Validation passed!', 'sixlab-tool'); ?>',
            validationFailed: '<?php _e('Validation failed', 'sixlab-tool'); ?>'
        }
    };
    </script>

    <?php wp_footer(); ?>
</body>
</html>
