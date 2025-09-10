<?php
/**
 * AI Providers Configuration Template
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
    
    <?php settings_errors(); ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=sixlab-ai-providers&tab=overview" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Overview', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-ai-providers&tab=openrouter" class="nav-tab <?php echo $active_tab === 'openrouter' ? 'nav-tab-active' : ''; ?>">
            <?php _e('OpenRouter (Multi-Model AI)', 'sixlab-tool'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($active_tab === 'overview'): ?>
            <div class="sixlab-ai-overview">
                <h2><?php _e('AI Assistant Configuration', 'sixlab-tool'); ?></h2>
                <p><?php _e('Configure AI providers to enhance the learning experience with intelligent assistance, code analysis, and automated feedback.', 'sixlab-tool'); ?></p>
                
                <div class="sixlab-ai-cards">
                    <?php foreach ($available_ai_providers as $provider_type => $provider_class): ?>
                        <?php
                        try {
                            $temp_provider = new $provider_class();
                            $is_configured = isset($configured_ai_providers[$provider_type]) && !empty($configured_ai_providers[$provider_type]);
                            $is_active = $is_configured && !empty($configured_ai_providers[$provider_type]['enabled']);
                        } catch (Throwable $e) {
                            continue;
                        }
                        ?>
                        <div class="sixlab-ai-card">
                            <div class="sixlab-ai-header">
                                <div class="sixlab-ai-title">
                                    <h3><?php echo esc_html($temp_provider->get_display_name()); ?></h3>
                                    <span class="sixlab-ai-model"><?php echo esc_html($temp_provider->get_model_name()); ?></span>
                                </div>
                                <div class="sixlab-ai-status">
                                    <span class="sixlab-provider-status <?php echo $is_configured ? 'configured' : 'not-configured'; ?>">
                                        <?php echo $is_configured ? __('Configured', 'sixlab-tool') : __('Not Configured', 'sixlab-tool'); ?>
                                    </span>
                                    <?php if ($is_configured): ?>
                                        <span class="sixlab-active-status <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $is_active ? __('Active', 'sixlab-tool') : __('Inactive', 'sixlab-tool'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p class="sixlab-ai-description">
                                <?php echo esc_html($temp_provider->get_description()); ?>
                            </p>
                            
                            <div class="sixlab-ai-capabilities">
                                <h4><?php _e('Capabilities:', 'sixlab-tool'); ?></h4>
                                <ul>
                                    <?php
                                    $capabilities = $temp_provider->get_capabilities();
                                    $capability_labels = array(
                                        'code_analysis' => __('Code Analysis', 'sixlab-tool'),
                                        'configuration_help' => __('Configuration Help', 'sixlab-tool'),
                                        'troubleshooting' => __('Troubleshooting', 'sixlab-tool'),
                                        'learning_guidance' => __('Learning Guidance', 'sixlab-tool'),
                                        'concept_explanation' => __('Concept Explanation', 'sixlab-tool'),
                                        'best_practices' => __('Best Practices', 'sixlab-tool'),
                                        'automated_feedback' => __('Automated Feedback', 'sixlab-tool'),
                                        'progress_tracking' => __('Progress Tracking', 'sixlab-tool'),
                                        'conversation_memory' => __('Conversation Memory', 'sixlab-tool'),
                                        'multimodal_support' => __('Image/Diagram Analysis', 'sixlab-tool'),
                                        'streaming_responses' => __('Real-time Responses', 'sixlab-tool'),
                                        'cost_optimization' => __('Cost Optimization', 'sixlab-tool')
                                    );
                                    
                                    foreach (array_slice($capabilities, 0, 4) as $capability):
                                        $label = $capability_labels[$capability] ?? ucfirst(str_replace('_', ' ', $capability));
                                    ?>
                                        <li><?php echo esc_html($label); ?></li>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($capabilities) > 4): ?>
                                        <li><em><?php printf(__('+ %d more capabilities', 'sixlab-tool'), count($capabilities) - 4); ?></em></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <?php if ($is_configured): ?>
                                <div class="sixlab-ai-stats">
                                    <div class="sixlab-ai-stat">
                                        <span class="sixlab-stat-label"><?php _e('Usage Today:', 'sixlab-tool'); ?></span>
                                        <span class="sixlab-stat-value"><?php echo esc_html($temp_provider->get_daily_usage()); ?></span>
                                    </div>
                                    <div class="sixlab-ai-stat">
                                        <span class="sixlab-stat-label"><?php _e('Cost Today:', 'sixlab-tool'); ?></span>
                                        <span class="sixlab-stat-value">$<?php echo number_format($temp_provider->get_daily_cost(), 4); ?></span>
                                    </div>
                                    <div class="sixlab-ai-stat">
                                        <span class="sixlab-stat-label"><?php _e('Avg Response:', 'sixlab-tool'); ?></span>
                                        <span class="sixlab-stat-value"><?php echo esc_html($temp_provider->get_avg_response_time()); ?>ms</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="sixlab-ai-actions">
                                <a href="?page=sixlab-ai-providers&tab=<?php echo esc_attr($provider_type); ?>" class="button button-primary">
                                    <?php echo $is_configured ? __('Configure', 'sixlab-tool') : __('Setup', 'sixlab-tool'); ?>
                                </a>
                                
                                <?php if ($is_configured): ?>
                                    <button type="button" class="button button-secondary sixlab-test-ai-provider" 
                                            data-provider="<?php echo esc_attr($provider_type); ?>">
                                        <?php _e('Test AI', 'sixlab-tool'); ?>
                                    </button>
                                    
                                    <button type="button" class="button <?php echo $is_active ? 'button-secondary' : 'button-primary'; ?> sixlab-toggle-ai-provider" 
                                            data-provider="<?php echo esc_attr($provider_type); ?>"
                                            data-action="<?php echo $is_active ? 'disable' : 'enable'; ?>">
                                        <?php echo $is_active ? __('Disable', 'sixlab-tool') : __('Enable', 'sixlab-tool'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="sixlab-ai-global-settings">
                    <h3><?php _e('Global AI Settings', 'sixlab-tool'); ?></h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('sixlab_ai_global_settings_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ai_assistant_enabled"><?php _e('Enable AI Assistant', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="ai_assistant_enabled" name="ai_assistant_enabled" value="1"
                                           <?php checked(1, get_option('sixlab_ai_assistant_enabled', 1)); ?> />
                                    <p class="description"><?php _e('Allow students to use AI assistance during lab sessions.', 'sixlab-tool'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default_ai_provider"><?php _e('Default AI Provider', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <select id="default_ai_provider" name="default_ai_provider">
                                        <option value=""><?php _e('Select a provider...', 'sixlab-tool'); ?></option>
                                        <?php
                                        $default_provider = get_option('sixlab_default_ai_provider', '');
                                        foreach ($available_ai_providers as $provider_type => $provider_class):
                                            if (isset($configured_ai_providers[$provider_type]) && !empty($configured_ai_providers[$provider_type]['enabled'])):
                                                $temp_provider = new $provider_class();
                                        ?>
                                                <option value="<?php echo esc_attr($provider_type); ?>"
                                                        <?php selected($default_provider, $provider_type); ?>>
                                                    <?php echo esc_html($temp_provider->get_display_name()); ?>
                                                </option>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </select>
                                    <p class="description"><?php _e('The primary AI provider for new conversations.', 'sixlab-tool'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="max_daily_requests"><?php _e('Daily Request Limit', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="max_daily_requests" name="max_daily_requests" 
                                           value="<?php echo esc_attr(get_option('sixlab_max_daily_requests', 100)); ?>"
                                           min="1" max="10000" />
                                    <p class="description"><?php _e('Maximum AI requests per student per day.', 'sixlab-tool'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="conversation_timeout"><?php _e('Conversation Timeout', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <select id="conversation_timeout" name="conversation_timeout">
                                        <?php
                                        $timeout = get_option('sixlab_conversation_timeout', 3600);
                                        $timeout_options = array(
                                            1800 => __('30 minutes', 'sixlab-tool'),
                                            3600 => __('1 hour', 'sixlab-tool'),
                                            7200 => __('2 hours', 'sixlab-tool'),
                                            14400 => __('4 hours', 'sixlab-tool'),
                                            28800 => __('8 hours', 'sixlab-tool'),
                                            86400 => __('24 hours', 'sixlab-tool')
                                        );
                                        foreach ($timeout_options as $value => $label):
                                        ?>
                                            <option value="<?php echo esc_attr($value); ?>"
                                                    <?php selected($timeout, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php _e('How long to keep conversation context active.', 'sixlab-tool'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Global Settings', 'sixlab-tool')); ?>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- AI Provider-specific configuration form -->
            <?php if (isset($available_ai_providers[$active_tab])): ?>
                <?php
                $provider_class = $available_ai_providers[$active_tab];
                try {
                    $provider = new $provider_class();
                } catch (Throwable $e) {
                    echo '<div class="notice notice-error"><p>' . 
                         sprintf(__('Error loading AI provider: %s', 'sixlab-tool'), $e->getMessage()) . 
                         '</p></div>';
                    return;
                }
                ?>
                
                <div class="sixlab-ai-config">
                    <div class="sixlab-ai-info">
                        <h2><?php echo esc_html($provider->get_display_name()); ?></h2>
                        <p><?php echo esc_html($provider->get_description()); ?></p>
                        
                        <div class="sixlab-ai-model-info">
                            <h4><?php _e('Model Information:', 'sixlab-tool'); ?></h4>
                            <ul>
                                <li><strong><?php _e('Primary Model:', 'sixlab-tool'); ?></strong> <?php echo esc_html($provider->get_model_name()); ?></li>
                                <li><strong><?php _e('Context Window:', 'sixlab-tool'); ?></strong> <?php echo number_format($provider->get_context_limit()); ?> tokens</li>
                                <li><strong><?php _e('Cost per 1K Tokens:', 'sixlab-tool'); ?></strong> $<?php echo number_format($provider->get_cost_per_1k_tokens(), 6); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('sixlab_ai_providers_nonce'); ?>
                        
                        <h3><?php _e('API Configuration', 'sixlab-tool'); ?></h3>
                        <table class="form-table">
                            <?php
                            $config_fields = $provider->get_config_fields();
                            $current_config = $configured_ai_providers[$active_tab] ?? array();
                            
                            foreach ($config_fields as $field_name => $field_config):
                                $field_value = $current_config[$field_name] ?? ($field_config['default'] ?? '');
                                $field_id = $active_tab . '_' . $field_name;
                                $field_name_attr = "sixlab_ai_providers_config[{$active_tab}][{$field_name}]";
                            ?>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo esc_attr($field_id); ?>">
                                            <?php echo esc_html($field_config['label']); ?>
                                            <?php if (!empty($field_config['required'])): ?>
                                                <span class="required">*</span>
                                            <?php endif; ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $field_type = $field_config['type'] ?? 'text';
                                        
                                        switch ($field_type):
                                            case 'text':
                                            case 'password':
                                                ?>
                                                <input type="<?php echo esc_attr($field_type); ?>" 
                                                       id="<?php echo esc_attr($field_id); ?>"
                                                       name="<?php echo esc_attr($field_name_attr); ?>"
                                                       value="<?php echo esc_attr($field_value); ?>"
                                                       class="regular-text"
                                                       <?php if (!empty($field_config['placeholder'])): ?>
                                                           placeholder="<?php echo esc_attr($field_config['placeholder']); ?>"
                                                       <?php endif; ?>
                                                       <?php echo !empty($field_config['required']) ? 'required' : ''; ?> />
                                                <?php
                                                break;
                                                
                                            case 'number':
                                                ?>
                                                <input type="number" 
                                                       id="<?php echo esc_attr($field_id); ?>"
                                                       name="<?php echo esc_attr($field_name_attr); ?>"
                                                       value="<?php echo esc_attr($field_value); ?>"
                                                       <?php if (isset($field_config['min'])): ?>min="<?php echo esc_attr($field_config['min']); ?>"<?php endif; ?>
                                                       <?php if (isset($field_config['max'])): ?>max="<?php echo esc_attr($field_config['max']); ?>"<?php endif; ?>
                                                       <?php if (isset($field_config['step'])): ?>step="<?php echo esc_attr($field_config['step']); ?>"<?php endif; ?>
                                                       <?php echo !empty($field_config['required']) ? 'required' : ''; ?> />
                                                <?php
                                                break;
                                                
                                            case 'checkbox':
                                                ?>
                                                <input type="checkbox" 
                                                       id="<?php echo esc_attr($field_id); ?>"
                                                       name="<?php echo esc_attr($field_name_attr); ?>"
                                                       value="1"
                                                       <?php checked(1, $field_value); ?> />
                                                <?php
                                                break;
                                                
                                            case 'select':
                                                ?>
                                                <select id="<?php echo esc_attr($field_id); ?>"
                                                        name="<?php echo esc_attr($field_name_attr); ?>"
                                                        <?php echo !empty($field_config['required']) ? 'required' : ''; ?>>
                                                    <?php if (isset($field_config['options'])): ?>
                                                        <?php foreach ($field_config['options'] as $option_value => $option_label): ?>
                                                            <option value="<?php echo esc_attr($option_value); ?>"
                                                                    <?php selected($field_value, $option_value); ?>>
                                                                <?php echo esc_html($option_label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <?php
                                                break;
                                                
                                            case 'textarea':
                                                ?>
                                                <textarea id="<?php echo esc_attr($field_id); ?>"
                                                          name="<?php echo esc_attr($field_name_attr); ?>"
                                                          rows="5" cols="50" class="large-text"
                                                          <?php echo !empty($field_config['required']) ? 'required' : ''; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                                <?php
                                                break;
                                        endswitch;
                                        ?>
                                        
                                        <?php if (!empty($field_config['description'])): ?>
                                            <p class="description"><?php echo esc_html($field_config['description']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3><?php _e('Prompt Templates', 'sixlab-tool'); ?></h3>
                        <table class="form-table">
                            <?php
                            $prompt_templates = $provider->get_prompt_templates();
                            foreach ($prompt_templates as $template_name => $template_config):
                                $template_value = $current_config['prompts'][$template_name] ?? $template_config['default'];
                                $template_id = $active_tab . '_prompt_' . $template_name;
                                $template_name_attr = "sixlab_ai_providers_config[{$active_tab}][prompts][{$template_name}]";
                            ?>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo esc_attr($template_id); ?>">
                                            <?php echo esc_html($template_config['label']); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <textarea id="<?php echo esc_attr($template_id); ?>"
                                                  name="<?php echo esc_attr($template_name_attr); ?>"
                                                  rows="8" cols="60" class="large-text code"><?php echo esc_textarea($template_value); ?></textarea>
                                        
                                        <?php if (!empty($template_config['description'])): ?>
                                            <p class="description"><?php echo esc_html($template_config['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($template_config['variables'])): ?>
                                            <p class="description">
                                                <strong><?php _e('Available variables:', 'sixlab-tool'); ?></strong>
                                                <?php echo implode(', ', array_map(function($var) { return '<code>' . $var . '</code>'; }, $template_config['variables'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="button button-secondary sixlab-restore-template" 
                                                data-template="<?php echo esc_attr($template_id); ?>"
                                                data-default="<?php echo esc_attr($template_config['default']); ?>">
                                            <?php _e('Restore Default', 'sixlab-tool'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <div class="sixlab-form-actions">
                            <?php submit_button(__('Save Configuration', 'sixlab-tool'), 'primary', 'submit', false); ?>
                            
                            <button type="button" class="button button-secondary sixlab-test-ai-provider" 
                                    data-provider="<?php echo esc_attr($active_tab); ?>">
                                <?php _e('Test AI Provider', 'sixlab-tool'); ?>
                            </button>
                        </div>
                        
                        <div id="sixlab-ai-test-result" class="sixlab-test-result" style="display: none;"></div>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.sixlab-ai-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.sixlab-ai-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-ai-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.sixlab-ai-title h3 {
    margin: 0 0 5px 0;
    color: #1d2327;
}

.sixlab-ai-model {
    font-size: 12px;
    color: #646970;
    font-style: italic;
}

.sixlab-ai-status {
    display: flex;
    flex-direction: column;
    gap: 5px;
    text-align: right;
}

.sixlab-active-status {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.sixlab-active-status.active {
    background: #c6f6d5;
    color: #22543d;
}

.sixlab-active-status.inactive {
    background: #fed7d7;
    color: #742a2a;
}

.sixlab-ai-capabilities {
    margin-bottom: 15px;
}

.sixlab-ai-capabilities h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #1d2327;
}

.sixlab-ai-capabilities ul {
    margin: 0;
    padding-left: 20px;
}

.sixlab-ai-capabilities li {
    color: #646970;
    font-size: 13px;
    margin-bottom: 3px;
}

.sixlab-ai-stats {
    background: #f6f7f7;
    border-radius: 4px;
    padding: 12px;
    margin-bottom: 15px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 10px;
}

.sixlab-ai-stat {
    text-align: center;
}

.sixlab-stat-label {
    display: block;
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 2px;
}

.sixlab-stat-value {
    display: block;
    font-size: 14px;
    color: #1d2327;
    font-weight: 600;
}

.sixlab-ai-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.sixlab-ai-global-settings {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-ai-global-settings h3 {
    margin-top: 0;
    color: #1d2327;
    border-bottom: 1px solid #c3c4c7;
    padding-bottom: 10px;
}

.sixlab-ai-config {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-ai-info {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #c3c4c7;
}

.sixlab-ai-info h2 {
    margin-top: 0;
    color: #1d2327;
}

.sixlab-ai-model-info {
    background: #f6f7f7;
    border-radius: 4px;
    padding: 15px;
    margin-top: 15px;
}

.sixlab-ai-model-info h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.sixlab-ai-model-info ul {
    margin: 0;
    padding-left: 20px;
}

.sixlab-ai-model-info li {
    margin-bottom: 5px;
    color: #646970;
}

@media (max-width: 768px) {
    .sixlab-ai-cards {
        grid-template-columns: 1fr;
    }
    
    .sixlab-ai-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .sixlab-ai-status {
        flex-direction: row;
        text-align: left;
    }
    
    .sixlab-ai-actions {
        flex-direction: column;
    }
    
    .sixlab-ai-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.sixlab-test-ai-provider').on('click', function() {
        var button = $(this);
        var provider = button.data('provider');
        var resultDiv = $('#sixlab-ai-test-result');
        
        button.prop('disabled', true).text(sixlab_admin.strings.testing);
        resultDiv.hide();
        
        // Collect current configuration
        var config = {};
        $('input[name^="sixlab_ai_providers_config[' + provider + ']"], select[name^="sixlab_ai_providers_config[' + provider + ']"], textarea[name^="sixlab_ai_providers_config[' + provider + ']"]').each(function() {
            var name = $(this).attr('name');
            if ($(this).attr('type') === 'checkbox') {
                var matches = name.match(/\[([^\]]+)\]$/);
                if (matches) {
                    config[matches[1]] = $(this).is(':checked') ? '1' : '';
                }
            } else {
                // Handle nested arrays like prompts
                if (name.includes('[prompts][')) {
                    var promptMatches = name.match(/\[prompts\]\[([^\]]+)\]$/);
                    if (promptMatches) {
                        if (!config.prompts) config.prompts = {};
                        config.prompts[promptMatches[1]] = $(this).val();
                    }
                } else {
                    var matches = name.match(/\[([^\]]+)\]$/);
                    if (matches) {
                        config[matches[1]] = $(this).val();
                    }
                }
            }
        });
        
        $.ajax({
            url: sixlab_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'sixlab_test_ai_provider',
                provider_type: provider,
                config: config,
                nonce: sixlab_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    resultDiv.removeClass('error').addClass('success')
                           .html('<strong>' + sixlab_admin.strings.test_success + '</strong><br>' + 
                                 '<strong>Response:</strong> ' + result.response + '<br>' +
                                 '<strong>Tokens:</strong> ' + result.tokens + ' | ' +
                                 '<strong>Cost:</strong> $' + result.cost)
                           .show();
                } else {
                    resultDiv.removeClass('success').addClass('error')
                           .html('<strong>' + sixlab_admin.strings.test_failed + '</strong> ' + response.data.message)
                           .show();
                }
            },
            error: function() {
                resultDiv.removeClass('success').addClass('error')
                       .html('<strong>' + sixlab_admin.strings.error + '</strong> AI test failed')
                       .show();
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e('Test AI Provider', 'sixlab-tool'); ?>');
            }
        });
    });
    
    $('.sixlab-toggle-ai-provider').on('click', function() {
        var button = $(this);
        var provider = button.data('provider');
        var action = button.data('action');
        
        button.prop('disabled', true);
        
        $.ajax({
            url: sixlab_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'sixlab_toggle_ai_provider',
                provider_type: provider,
                toggle_action: action,
                nonce: sixlab_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
    
    $('.sixlab-restore-template').on('click', function() {
        var button = $(this);
        var templateId = button.data('template');
        var defaultValue = button.data('default');
        
        $('#' + templateId).val(defaultValue);
    });
});
</script>
