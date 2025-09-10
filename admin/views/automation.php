<?php
/**
 * 6Lab Tool - Automation Admin Interface
 * Admin panel for development automation and build pipeline
 */

if (!defined('ABSPATH')) {
    exit;
}

$automation_manager = new SixLab_Automation_Manager();
$automation_scripts = $automation_manager->get_automation_scripts();
$build_pipeline = $automation_manager->get_build_pipeline();
$recent_logs = get_option('sixlab_build_logs', array());
$recent_logs = array_slice($recent_logs, 0, 5);
?>

<div class="wrap">
    <h1><?php esc_html_e('Development Automation', 'sixlab-tool'); ?></h1>
    
    <div class="automation-dashboard">
        <!-- Quick Actions -->
        <div class="postbox" style="margin-bottom: 20px;">
            <h3 class="hndle"><?php esc_html_e('Quick Actions', 'sixlab-tool'); ?></h3>
            <div class="inside">
                <div class="automation-quick-actions">
                    <button type="button" class="button button-primary" onclick="runBuildPipeline('development')">
                        <?php esc_html_e('Development Build', 'sixlab-tool'); ?>
                    </button>
                    <button type="button" class="button button-secondary" onclick="runBuildPipeline('production')">
                        <?php esc_html_e('Production Build', 'sixlab-tool'); ?>
                    </button>
                    <button type="button" class="button" onclick="runAutomationScript('database_setup')">
                        <?php esc_html_e('Database Setup', 'sixlab-tool'); ?>
                    </button>
                    <button type="button" class="button" onclick="showProviderScaffold()">
                        <?php esc_html_e('Create Provider', 'sixlab-tool'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Automation Scripts -->
        <div class="postbox">
            <h3 class="hndle"><?php esc_html_e('Automation Scripts', 'sixlab-tool'); ?></h3>
            <div class="inside">
                <div class="automation-scripts-grid">
                    <?php foreach ($automation_scripts as $script_key => $script_config): ?>
                        <div class="automation-script-card">
                            <h4><?php echo esc_html($script_config['name']); ?></h4>
                            <p><?php echo esc_html($script_config['description']); ?></p>
                            
                            <?php if (!empty($script_config['parameters'])): ?>
                                <div class="script-parameters" id="params-<?php echo esc_attr($script_key); ?>" style="display: none;">
                                    <?php foreach ($script_config['parameters'] as $param_key => $param_config): ?>
                                        <div class="parameter-field">
                                            <label><?php echo esc_html($param_config['label']); ?></label>
                                            <?php if ($param_config['type'] === 'text'): ?>
                                                <input type="text" 
                                                       name="<?php echo esc_attr($param_key); ?>" 
                                                       placeholder="<?php echo esc_attr($param_config['placeholder'] ?? ''); ?>"
                                                       <?php echo !empty($param_config['required']) ? 'required' : ''; ?>>
                                            <?php elseif ($param_config['type'] === 'select'): ?>
                                                <select name="<?php echo esc_attr($param_key); ?>">
                                                    <?php foreach ($param_config['options'] as $value => $label): ?>
                                                        <option value="<?php echo esc_attr($value); ?>">
                                                            <?php echo esc_html($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="script-actions">
                                <button type="button" 
                                        class="button button-primary" 
                                        onclick="runAutomationScript('<?php echo esc_js($script_key); ?>')">
                                    <?php esc_html_e('Run Script', 'sixlab-tool'); ?>
                                </button>
                                <?php if (!empty($script_config['parameters'])): ?>
                                    <button type="button" 
                                            class="button" 
                                            onclick="toggleParameters('<?php echo esc_js($script_key); ?>')">
                                        <?php esc_html_e('Configure', 'sixlab-tool'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Build Pipeline Status -->
        <div class="automation-row">
            <div class="automation-column">
                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Build Pipeline', 'sixlab-tool'); ?></h3>
                    <div class="inside">
                        <div class="build-environments">
                            <?php foreach ($build_pipeline as $env => $config): ?>
                                <div class="build-environment">
                                    <h4><?php echo esc_html(ucfirst($env)); ?> Environment</h4>
                                    <div class="build-tasks">
                                        <?php foreach ($config['tasks'] as $task): ?>
                                            <span class="build-task"><?php echo esc_html($task); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" 
                                            class="button" 
                                            onclick="runBuildPipeline('<?php echo esc_js($env); ?>')">
                                        <?php echo sprintf(esc_html__('Build %s', 'sixlab-tool'), ucfirst($env)); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="automation-column">
                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Recent Activity', 'sixlab-tool'); ?></h3>
                    <div class="inside">
                        <div class="build-logs">
                            <?php if (!empty($recent_logs)): ?>
                                <?php foreach ($recent_logs as $log): ?>
                                    <div class="log-entry <?php echo $log['success'] ? 'success' : 'error'; ?>">
                                        <div class="log-header">
                                            <span class="log-time"><?php echo esc_html(date('M j, H:i', strtotime($log['timestamp']))); ?></span>
                                            <span class="log-environment"><?php echo esc_html($log['environment']); ?></span>
                                        </div>
                                        <div class="log-message"><?php echo esc_html($log['message']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php esc_html_e('No recent build activity.', 'sixlab-tool'); ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="#" onclick="showFullLogs()" class="button">
                            <?php esc_html_e('View All Logs', 'sixlab-tool'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Provider Scaffold Modal -->
    <div id="provider-scaffold-modal" class="automation-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php esc_html_e('Create New Provider', 'sixlab-tool'); ?></h3>
                <span class="modal-close" onclick="hideProviderScaffold()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="provider-scaffold-form">
                    <div class="form-group">
                        <label><?php esc_html_e('Provider Name', 'sixlab-tool'); ?></label>
                        <input type="text" name="provider_name" required placeholder="e.g., custom_lab_provider">
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e('Provider Type', 'sixlab-tool'); ?></label>
                        <select name="provider_type" required>
                            <option value="">Select Type</option>
                            <option value="gns3">GNS3</option>
                            <option value="eve_ng">EVE-NG</option>
                            <option value="guacamole">Apache Guacamole</option>
                            <option value="custom">Custom Provider</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e('Display Name', 'sixlab-tool'); ?></label>
                        <input type="text" name="display_name" placeholder="e.g., Custom Lab Provider">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Create Provider', 'sixlab-tool'); ?>
                        </button>
                        <button type="button" class="button" onclick="hideProviderScaffold()">
                            <?php esc_html_e('Cancel', 'sixlab-tool'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="automation-progress-modal" class="automation-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="progress-title"><?php esc_html_e('Running Automation...', 'sixlab-tool'); ?></h3>
            </div>
            <div class="modal-body">
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="progress-text" id="progress-text">Starting...</div>
                </div>
                <div class="progress-output" id="progress-output"></div>
            </div>
        </div>
    </div>
</div>

<style>
.automation-dashboard {
    max-width: 1200px;
}

.automation-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.automation-scripts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.automation-script-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: #fafafa;
}

.automation-script-card h4 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.automation-script-card p {
    margin: 0 0 15px 0;
    color: #666;
}

.script-parameters {
    margin: 15px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.parameter-field {
    margin-bottom: 15px;
}

.parameter-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.parameter-field input,
.parameter-field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.script-actions {
    display: flex;
    gap: 10px;
}

.automation-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.automation-column {
    min-width: 0;
}

.build-environments {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.build-environment {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fafafa;
}

.build-environment h4 {
    margin: 0 0 10px 0;
}

.build-tasks {
    margin: 10px 0;
}

.build-task {
    display: inline-block;
    background: #0073aa;
    color: white;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 5px;
    margin-bottom: 5px;
}

.build-logs {
    max-height: 300px;
    overflow-y: auto;
}

.log-entry {
    padding: 10px;
    margin-bottom: 10px;
    border-left: 4px solid #ddd;
    background: #f9f9f9;
}

.log-entry.success {
    border-left-color: #46b450;
}

.log-entry.error {
    border-left-color: #dc3232;
}

.log-header {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.log-environment {
    background: #666;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

.automation-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 4px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    min-width: 500px;
    max-width: 90%;
    max-height: 90%;
    overflow: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.progress-container {
    margin-bottom: 20px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: #0073aa;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-weight: 600;
    color: #666;
}

.progress-output {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    max-height: 300px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
}

@media (max-width: 768px) {
    .automation-row {
        grid-template-columns: 1fr;
    }
    
    .automation-scripts-grid {
        grid-template-columns: 1fr;
    }
    
    .automation-quick-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize automation interface
    window.sixlabAutomation = {
        currentProcess: null,
        
        // Run automation script
        runScript: function(scriptName, parameters) {
            parameters = parameters || {};
            
            this.showProgress('Running ' + scriptName + '...');
            
            $.post(ajaxurl, {
                action: 'sixlab_run_automation',
                script: scriptName,
                parameters: parameters,
                nonce: '<?php echo wp_create_nonce('sixlab_automation'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    sixlabAutomation.updateProgress(100, 'Completed successfully!');
                    setTimeout(function() {
                        sixlabAutomation.hideProgress();
                        location.reload();
                    }, 2000);
                } else {
                    sixlabAutomation.updateProgress(0, 'Error: ' + response.data.message);
                }
            })
            .fail(function() {
                sixlabAutomation.updateProgress(0, 'Request failed');
            });
        },
        
        // Run build pipeline
        runBuild: function(environment) {
            this.showProgress('Running ' + environment + ' build...');
            
            $.post(ajaxurl, {
                action: 'sixlab_build_pipeline',
                environment: environment,
                nonce: '<?php echo wp_create_nonce('sixlab_automation'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    sixlabAutomation.updateProgress(100, 'Build completed successfully!');
                    setTimeout(function() {
                        sixlabAutomation.hideProgress();
                        location.reload();
                    }, 2000);
                } else {
                    sixlabAutomation.updateProgress(0, 'Build failed: ' + response.data.message);
                }
            })
            .fail(function() {
                sixlabAutomation.updateProgress(0, 'Build request failed');
            });
        },
        
        // Show progress modal
        showProgress: function(title) {
            $('#progress-title').text(title);
            $('#progress-fill').css('width', '0%');
            $('#progress-text').text('Starting...');
            $('#progress-output').text('');
            $('#automation-progress-modal').show();
        },
        
        // Update progress
        updateProgress: function(percent, text, output) {
            $('#progress-fill').css('width', percent + '%');
            $('#progress-text').text(text);
            if (output) {
                $('#progress-output').append(output + '\n');
                $('#progress-output').scrollTop($('#progress-output')[0].scrollHeight);
            }
        },
        
        // Hide progress modal
        hideProgress: function() {
            $('#automation-progress-modal').hide();
        },
        
        // Show provider scaffold modal
        showProviderScaffold: function() {
            $('#provider-scaffold-modal').show();
        },
        
        // Hide provider scaffold modal
        hideProviderScaffold: function() {
            $('#provider-scaffold-modal').hide();
        }
    };
    
    // Provider scaffold form submission
    $('#provider-scaffold-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        var parameters = {};
        
        $.each(formData, function(i, field) {
            parameters[field.name] = field.value;
        });
        
        sixlabAutomation.hideProviderScaffold();
        sixlabAutomation.runScript('provider_scaffold', parameters);
    });
});

// Global functions for onclick handlers
function runAutomationScript(scriptName) {
    var parameters = {};
    
    // Collect parameters if they exist
    var paramContainer = document.getElementById('params-' + scriptName);
    if (paramContainer && paramContainer.style.display !== 'none') {
        var inputs = paramContainer.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            parameters[input.name] = input.value;
        });
    }
    
    sixlabAutomation.runScript(scriptName, parameters);
}

function runBuildPipeline(environment) {
    sixlabAutomation.runBuild(environment);
}

function showProviderScaffold() {
    sixlabAutomation.showProviderScaffold();
}

function hideProviderScaffold() {
    sixlabAutomation.hideProviderScaffold();
}

function toggleParameters(scriptName) {
    var paramContainer = document.getElementById('params-' + scriptName);
    if (paramContainer) {
        paramContainer.style.display = paramContainer.style.display === 'none' ? 'block' : 'none';
    }
}

function showFullLogs() {
    // Implementation for showing full logs modal
    alert('Full logs feature coming soon!');
}
</script>";
    }
}
