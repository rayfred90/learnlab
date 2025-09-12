<?php
/**
 * Lab Providers Configuration Template
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
        <a href="?page=sixlab-providers&tab=overview" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Overview', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-providers&tab=gns3" class="nav-tab <?php echo $active_tab === 'gns3' ? 'nav-tab-active' : ''; ?>">
            <?php _e('GNS3', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-providers&tab=guacamole" class="nav-tab <?php echo $active_tab === 'guacamole' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Guacamole', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-providers&tab=eveng" class="nav-tab <?php echo $active_tab === 'eveng' ? 'nav-tab-active' : ''; ?>">
            <?php _e('EVE-NG', 'sixlab-tool'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($active_tab === 'overview'): ?>
            <div class="sixlab-providers-overview">
                <h2><?php _e('Available Lab Providers', 'sixlab-tool'); ?></h2>
                <p><?php _e('Choose and configure the lab providers that best fit your educational needs.', 'sixlab-tool'); ?></p>
                
                <div class="sixlab-provider-cards">
                    <?php foreach ($available_providers as $provider_type => $provider_class): ?>
                        <?php
                        try {
                            $temp_provider = new $provider_class();
                            $provider_configs = $configured_providers[$provider_type] ?? array();
                            $is_configured = !empty($provider_configs);
                            $active_configs = array_filter($provider_configs, function($config) { return $config['is_active']; });
                            $config_count = count($provider_configs);
                            $active_count = count($active_configs);
                        } catch (Throwable $e) {
                            continue;
                        }
                        ?>
                        <div class="sixlab-provider-card">
                            <div class="sixlab-provider-header">
                                <h3><?php echo esc_html($temp_provider->get_display_name()); ?></h3>
                                <div class="sixlab-provider-status-info">
                                    <span class="sixlab-provider-status <?php echo $is_configured ? 'configured' : 'not-configured'; ?>">
                                        <?php if ($is_configured): ?>
                                            <?php printf(__('%d Configuration(s)', 'sixlab-tool'), $config_count); ?>
                                        <?php else: ?>
                                            <?php _e('Not Configured', 'sixlab-tool'); ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($is_configured && $active_count > 0): ?>
                                        <span class="sixlab-active-count">
                                            <?php printf(__('%d Active', 'sixlab-tool'), $active_count); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p class="sixlab-provider-description">
                                <?php echo esc_html($temp_provider->get_description()); ?>
                            </p>
                            
                            <div class="sixlab-provider-features">
                                <h4><?php _e('Features:', 'sixlab-tool'); ?></h4>
                                <ul>
                                    <?php
                                    $features = $temp_provider->get_supported_features();
                                    $feature_labels = array(
                                        'network_topology' => __('Network Topology', 'sixlab-tool'),
                                        'console_access' => __('Console Access', 'sixlab-tool'),
                                        'remote_desktop' => __('Remote Desktop', 'sixlab-tool'),
                                        'ssh_access' => __('SSH Access', 'sixlab-tool'),
                                        'real_time_validation' => __('Real-time Validation', 'sixlab-tool'),
                                        'configuration_backup' => __('Configuration Backup', 'sixlab-tool'),
                                        'snapshot_support' => __('Snapshots', 'sixlab-tool'),
                                        'multi_vendor_devices' => __('Multi-vendor Support', 'sixlab-tool'),
                                        'file_sharing' => __('File Sharing', 'sixlab-tool'),
                                        'screen_recording' => __('Screen Recording', 'sixlab-tool'),
                                        'wireshark_integration' => __('Wireshark Integration', 'sixlab-tool'),
                                        'collaborative_labs' => __('Collaborative Labs', 'sixlab-tool')
                                    );
                                    
                                    foreach (array_slice($features, 0, 4) as $feature):
                                        $label = $feature_labels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
                                    ?>
                                        <li><?php echo esc_html($label); ?></li>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($features) > 4): ?>
                                        <li><em><?php printf(__('+ %d more features', 'sixlab-tool'), count($features) - 4); ?></em></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <div class="sixlab-provider-actions">
                                <a href="?page=sixlab-providers&tab=<?php echo esc_attr($provider_type); ?>" class="button button-primary">
                                    <?php echo $is_configured ? __('Manage', 'sixlab-tool') : __('Setup', 'sixlab-tool'); ?>
                                </a>
                                
                                <?php if ($is_configured): ?>
                                    <a href="?page=sixlab-providers&tab=<?php echo esc_attr($provider_type); ?>&action=add" class="button button-secondary">
                                        <?php _e('Add New', 'sixlab-tool'); ?>
                                    </a>
                                    
                                    <?php if ($active_count > 0): ?>
                                        <button type="button" class="button button-secondary sixlab-test-provider" 
                                                data-provider="<?php echo esc_attr($provider_type); ?>">
                                            <?php _e('Test Default', 'sixlab-tool'); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Provider-specific configuration management -->
            <?php if (isset($available_providers[$active_tab])): ?>
                <?php
                $provider_class = $available_providers[$active_tab];
                try {
                    $provider = new $provider_class();
                    $provider_configs = $configured_providers[$active_tab] ?? array();
                    $is_adding_new = isset($_GET['action']) && $_GET['action'] === 'add';
                    $editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
                } catch (Throwable $e) {
                    echo '<div class="notice notice-error"><p>' . 
                         sprintf(__('Error loading provider: %s', 'sixlab-tool'), $e->getMessage()) . 
                         '</p></div>';
                    return;
                }
                ?>
                
                <div class="sixlab-provider-config">
                    <div class="sixlab-provider-info">
                        <h2><?php echo esc_html($provider->get_display_name()); ?></h2>
                        <p><?php echo esc_html($provider->get_description()); ?></p>
                    </div>
                    
                    <?php if (!$is_adding_new && !$editing_id): ?>
                        <!-- List existing configurations -->
                        <div class="sixlab-configurations-list">
                            <div class="sixlab-configurations-header">
                                <h3><?php _e('Existing Configurations', 'sixlab-tool'); ?></h3>
                                <a href="?page=sixlab-providers&tab=<?php echo esc_attr($active_tab); ?>&action=add" class="button button-primary">
                                    <?php _e('Add New Configuration', 'sixlab-tool'); ?>
                                </a>
                            </div>
                            
                            <?php if (!empty($provider_configs)): ?>
                                <div class="sixlab-provider-instances">
                                    <?php foreach ($provider_configs as $config): ?>
                                        <div class="sixlab-provider-instance <?php echo $config['is_active'] ? 'active' : 'inactive'; ?>">
                                            <div class="sixlab-instance-header">
                                                <h4><?php echo esc_html($config['display_name']); ?></h4>
                                                <div class="sixlab-instance-badges">
                                                    <?php if ($config['is_default']): ?>
                                                        <span class="badge default"><?php _e('Default', 'sixlab-tool'); ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <span class="badge <?php echo $config['is_active'] ? 'active' : 'inactive'; ?>">
                                                        <?php echo $config['is_active'] ? __('Active', 'sixlab-tool') : __('Inactive', 'sixlab-tool'); ?>
                                                    </span>
                                                    
                                                    <?php if ($config['health_status']): ?>
                                                        <span class="badge health <?php echo esc_attr($config['health_status']); ?>">
                                                            <?php echo esc_html(ucfirst($config['health_status'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="sixlab-instance-info">
                                                <p><strong><?php _e('Name:', 'sixlab-tool'); ?></strong> <?php echo esc_html($config['name']); ?></p>
                                                <?php if (!empty($config['config']['server_url'])): ?>
                                                    <p><strong><?php _e('Server URL:', 'sixlab-tool'); ?></strong> <?php echo esc_html($config['config']['server_url']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if ($config['health_message']): ?>
                                                    <p class="health-message"><strong><?php _e('Status:', 'sixlab-tool'); ?></strong> <?php echo esc_html($config['health_message']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if ($config['last_health_check']): ?>
                                                    <p class="last-check"><strong><?php _e('Last Check:', 'sixlab-tool'); ?></strong> <?php echo esc_html(date('Y-m-d H:i:s', strtotime($config['last_health_check']))); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="sixlab-instance-actions">
                                                <a href="?page=sixlab-providers&tab=<?php echo esc_attr($active_tab); ?>&edit=<?php echo esc_attr($config['id']); ?>" class="button button-secondary">
                                                    <?php _e('Edit', 'sixlab-tool'); ?>
                                                </a>
                                                
                                                <button type="button" class="button button-secondary sixlab-test-single-provider" 
                                                        data-provider-id="<?php echo esc_attr($config['id']); ?>"
                                                        data-provider-type="<?php echo esc_attr($active_tab); ?>">
                                                    <?php _e('Test Connection', 'sixlab-tool'); ?>
                                                </button>
                                                
                                                <?php if (!$config['is_default']): ?>
                                                    <button type="button" class="button button-link sixlab-set-default-provider" 
                                                            data-provider-id="<?php echo esc_attr($config['id']); ?>">
                                                        <?php _e('Set as Default', 'sixlab-tool'); ?>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="button button-link-delete sixlab-delete-provider" 
                                                        data-provider-id="<?php echo esc_attr($config['id']); ?>"
                                                        data-provider-name="<?php echo esc_attr($config['display_name']); ?>">
                                                    <?php _e('Delete', 'sixlab-tool'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="sixlab-no-configurations">
                                    <p><?php _e('No configurations found for this provider type.', 'sixlab-tool'); ?></p>
                                    <a href="?page=sixlab-providers&tab=<?php echo esc_attr($active_tab); ?>&action=add" class="button button-primary">
                                        <?php _e('Create First Configuration', 'sixlab-tool'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <!-- Add/Edit configuration form -->
                        <?php
                        $current_config = array();
                        $is_editing = false;
                        
                        if ($editing_id) {
                            $is_editing = true;
                            foreach ($provider_configs as $config) {
                                if ($config['id'] == $editing_id) {
                                    $current_config = $config['config'];
                                    break;
                                }
                            }
                        }
                        ?>
                        
                        <div class="sixlab-configuration-form">
                            <div class="sixlab-form-header">
                                <h3><?php echo $is_editing ? __('Edit Configuration', 'sixlab-tool') : __('Add New Configuration', 'sixlab-tool'); ?></h3>
                                <a href="?page=sixlab-providers&tab=<?php echo esc_attr($active_tab); ?>" class="button button-secondary">
                                    <?php _e('← Back to List', 'sixlab-tool'); ?>
                                </a>
                            </div>
                            
                            <form method="post" action="" id="sixlab-provider-form">
                                <?php wp_nonce_field('sixlab_providers_nonce'); ?>
                                <input type="hidden" name="provider_type" value="<?php echo esc_attr($active_tab); ?>" />
                                <?php if ($is_editing): ?>
                                    <input type="hidden" name="provider_id" value="<?php echo esc_attr($editing_id); ?>" />
                                <?php endif; ?>
                                
                                <table class="form-table">
                                    <!-- Configuration Name -->
                                    <tr>
                                        <th scope="row">
                                            <label for="provider_name"><?php _e('Configuration Name', 'sixlab-tool'); ?> <span class="required">*</span></label>
                                        </th>
                                        <td>
                                            <input type="text" id="provider_name" name="provider_name" 
                                                   value="<?php echo $is_editing ? esc_attr($config['name'] ?? '') : ''; ?>" 
                                                   class="regular-text" required />
                                            <p class="description"><?php _e('A unique name to identify this configuration', 'sixlab-tool'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <!-- Display Name -->
                                    <tr>
                                        <th scope="row">
                                            <label for="provider_display_name"><?php _e('Display Name', 'sixlab-tool'); ?> <span class="required">*</span></label>
                                        </th>
                                        <td>
                                            <input type="text" id="provider_display_name" name="provider_display_name" 
                                                   value="<?php echo $is_editing ? esc_attr($config['display_name'] ?? '') : ''; ?>" 
                                                   class="regular-text" required />
                                            <p class="description"><?php _e('Name shown to users', 'sixlab-tool'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <?php
                                    $config_fields = $provider->get_config_fields();
                                    
                                    foreach ($config_fields as $field_name => $field_config):
                                        $field_value = $current_config[$field_name] ?? ($field_config['default'] ?? '');
                                        $field_id = $active_tab . '_' . $field_name;
                                        $field_name_attr = "provider_config[{$field_name}]";
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
                                            case 'url':
                                            case 'email':
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
                                                
                                            case 'password':
                                                ?>
                                                <input type="password" 
                                                       id="<?php echo esc_attr($field_id); ?>"
                                                       name="<?php echo esc_attr($field_name_attr); ?>"
                                                       value="<?php echo esc_attr($field_value); ?>"
                                                       class="regular-text"
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
                        
                        <div class="sixlab-form-actions">
                            <?php submit_button(__('Save Configuration', 'sixlab-tool'), 'primary', 'submit', false); ?>
                            
                            <button type="button" class="button button-secondary sixlab-test-provider" 
                                    data-provider="<?php echo esc_attr($active_tab); ?>">
                                <?php _e('Test Connection', 'sixlab-tool'); ?>
                            </button>
                        </div>
                        
                        <div id="sixlab-test-result" class="sixlab-test-result" style="display: none;"></div>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="notice notice-error">
                <p><?php _e('Provider type not found.', 'sixlab-tool'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.sixlab-provider-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.sixlab-provider-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-provider-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.sixlab-provider-header h3 {
    margin: 0;
    color: #1d2327;
}

.sixlab-provider-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.sixlab-provider-status.configured {
    background: #d7eddb;
    color: #00a32a;
}

.sixlab-provider-status.not-configured {
    background: #fcf0f1;
    color: #d63638;
}

.sixlab-provider-description {
    color: #646970;
    margin-bottom: 15px;
}

.sixlab-provider-features h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #1d2327;
}

.sixlab-provider-features ul {
    margin: 0 0 15px 0;
    padding-left: 20px;
}

.sixlab-provider-features li {
    color: #646970;
    font-size: 13px;
    margin-bottom: 3px;
}

.sixlab-provider-actions {
    display: flex;
    gap: 10px;
}

.sixlab-provider-config {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sixlab-provider-info {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #c3c4c7;
}

.sixlab-provider-info h2 {
    margin-top: 0;
    color: #1d2327;
}

.sixlab-form-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #c3c4c7;
    display: flex;
    gap: 10px;
    align-items: center;
}

.sixlab-test-result {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
}

.sixlab-test-result.success {
    background: #d7eddb;
    border: 1px solid #00a32a;
    color: #00a32a;
}

.sixlab-test-result.error {
    background: #fcf0f1;
    border: 1px solid #d63638;
    color: #d63638;
}

.required {
    color: #d63638;
}

@media (max-width: 768px) {
    .sixlab-provider-cards {
        grid-template-columns: 1fr;
    }
    
    .sixlab-provider-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .sixlab-provider-actions {
        flex-direction: column;
    }
    
    .sixlab-form-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Handle test connection for individual provider instances (Image 1 scenario)
    $('.sixlab-test-single-provider').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var providerId = button.data('provider-id');
        var providerType = button.data('provider-type');
        var originalText = button.text();
        
        // Find or create result div for this specific provider instance
        var $instance = button.closest('.sixlab-provider-instance');
        var $resultDiv = $instance.find('.sixlab-test-result');
        
        if ($resultDiv.length === 0) {
            $resultDiv = $('<div class="sixlab-test-result"></div>');
            button.closest('.sixlab-instance-actions').after($resultDiv);
        }
        
        button.prop('disabled', true).text('Testing...');
        $resultDiv.hide();
        
        $.ajax({
            url: typeof sixlab_admin !== 'undefined' ? sixlab_admin.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'sixlab_test_provider_by_id',
                provider_id: providerId,
                nonce: typeof sixlab_admin !== 'undefined' ? sixlab_admin.nonce : ''
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    $resultDiv.removeClass('error').addClass('success')
                           .html('<strong>✓ Test Success:</strong> ' + result.message)
                           .show();
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Connection test failed';
                    $resultDiv.removeClass('success').addClass('error')
                           .html('<strong>✗ Test Failed:</strong> ' + errorMessage)
                           .show();
                }
            },
            error: function() {
                $resultDiv.removeClass('success').addClass('error')
                       .html('<strong>✗ Error:</strong> Connection test failed')
                       .show();
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    $resultDiv.fadeOut();
                }, 5000);
            }
        });
    });
    
    // Handle test connection for overview cards (Image 2 scenario)
    $('.sixlab-test-provider').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var provider = button.data('provider');
        var originalText = button.text();
        
        // Find or create result div for this provider card
        var $card = button.closest('.sixlab-provider-card');
        var $resultDiv = $card.find('.sixlab-test-result');
        
        if ($resultDiv.length === 0) {
            $resultDiv = $('<div class="sixlab-test-result"></div>');
            button.closest('.sixlab-provider-actions').after($resultDiv);
        }
        
        button.prop('disabled', true).text('Testing...');
        $resultDiv.hide();
        
        // For overview cards, test the default provider configuration
        $.ajax({
            url: typeof sixlab_admin !== 'undefined' ? sixlab_admin.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'sixlab_test_default_provider',
                provider_type: provider,
                nonce: typeof sixlab_admin !== 'undefined' ? sixlab_admin.nonce : ''
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    $resultDiv.removeClass('error').addClass('success')
                           .html('<strong>✓ Test Success:</strong> ' + result.message)
                           .show();
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Connection test failed';
                    $resultDiv.removeClass('success').addClass('error')
                           .html('<strong>✗ Test Failed:</strong> ' + errorMessage)
                           .show();
                }
            },
            error: function() {
                $resultDiv.removeClass('success').addClass('error')
                       .html('<strong>✗ Error:</strong> Connection test failed')
                       .show();
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    $resultDiv.fadeOut();
                }, 5000);
            }
        });
    });
    
    // Handle test connection in configuration forms
    $('.sixlab-test-provider').on('click', function(e) {
        // Only handle if this is in a form context
        if (!$(this).closest('form').length) {
            return; // Let the overview card handler take care of it
        }
        
        e.preventDefault();
        
        var button = $(this);
        var provider = button.data('provider');
        var resultDiv = $('#sixlab-test-result');
        
        button.prop('disabled', true).text('Testing...');
        resultDiv.hide();
        
        // Collect current configuration from form
        var config = {};
        $('input[name^="provider_config["], select[name^="provider_config["], textarea[name^="provider_config["]').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/provider_config\[([^\]]+)\]/);
            if (matches) {
                var fieldName = matches[1];
                if ($(this).attr('type') === 'checkbox') {
                    config[fieldName] = $(this).is(':checked') ? '1' : '';
                } else {
                    config[fieldName] = $(this).val();
                }
            }
        });
        
        $.ajax({
            url: typeof sixlab_admin !== 'undefined' ? sixlab_admin.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'sixlab_test_provider',
                provider_type: provider,
                config: config,
                nonce: typeof sixlab_admin !== 'undefined' ? sixlab_admin.nonce : ''
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    resultDiv.removeClass('error').addClass('success')
                           .html('<strong>✓ Test Success:</strong> ' + result.message)
                           .show();
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Connection test failed';
                    resultDiv.removeClass('success').addClass('error')
                           .html('<strong>✗ Test Failed:</strong> ' + errorMessage)
                           .show();
                }
            },
            error: function() {
                resultDiv.removeClass('success').addClass('error')
                       .html('<strong>✗ Error:</strong> Connection test failed')
                       .show();
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });
});
</script>
