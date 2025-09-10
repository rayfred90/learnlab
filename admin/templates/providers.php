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
                            $is_configured = isset($configured_providers[$provider_type]) && !empty($configured_providers[$provider_type]);
                        } catch (Throwable $e) {
                            continue;
                        }
                        ?>
                        <div class="sixlab-provider-card">
                            <div class="sixlab-provider-header">
                                <h3><?php echo esc_html($temp_provider->get_display_name()); ?></h3>
                                <span class="sixlab-provider-status <?php echo $is_configured ? 'configured' : 'not-configured'; ?>">
                                    <?php echo $is_configured ? __('Configured', 'sixlab-tool') : __('Not Configured', 'sixlab-tool'); ?>
                                </span>
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
                                    <?php echo $is_configured ? __('Configure', 'sixlab-tool') : __('Setup', 'sixlab-tool'); ?>
                                </a>
                                
                                <?php if ($is_configured): ?>
                                    <button type="button" class="button button-secondary sixlab-test-provider" 
                                            data-provider="<?php echo esc_attr($provider_type); ?>">
                                        <?php _e('Test Connection', 'sixlab-tool'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Provider-specific configuration form -->
            <?php if (isset($available_providers[$active_tab])): ?>
                <?php
                $provider_class = $available_providers[$active_tab];
                try {
                    $provider = new $provider_class();
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
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('sixlab_providers_nonce'); ?>
                        
                        <table class="form-table">
                            <?php
                            $config_fields = $provider->get_config_fields();
                            $current_config = $configured_providers[$active_tab] ?? array();
                            
                            foreach ($config_fields as $field_name => $field_config):
                                $field_value = $current_config[$field_name] ?? ($field_config['default'] ?? '');
                                $field_id = $active_tab . '_' . $field_name;
                                $field_name_attr = "sixlab_providers_config[{$active_tab}][{$field_name}]";
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
        <?php endif; ?>
    </div>
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
    $('.sixlab-test-provider').on('click', function() {
        var button = $(this);
        var provider = button.data('provider');
        var resultDiv = $('#sixlab-test-result');
        
        button.prop('disabled', true).text(sixlab_admin.strings.testing);
        resultDiv.hide();
        
        // Collect current configuration
        var config = {};
        $('input[name^="sixlab_providers_config[' + provider + ']"], select[name^="sixlab_providers_config[' + provider + ']"], textarea[name^="sixlab_providers_config[' + provider + ']"]').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/\[([^\]]+)\]$/);
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
            url: sixlab_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'sixlab_test_provider',
                provider_type: provider,
                config: config,
                nonce: sixlab_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    resultDiv.removeClass('error').addClass('success')
                           .html('<strong>' + sixlab_admin.strings.test_success + '</strong> ' + result.message)
                           .show();
                } else {
                    resultDiv.removeClass('success').addClass('error')
                           .html('<strong>' + sixlab_admin.strings.test_failed + '</strong> ' + response.data.message)
                           .show();
                }
            },
            error: function() {
                resultDiv.removeClass('success').addClass('error')
                       .html('<strong>' + sixlab_admin.strings.error + '</strong> ' + 'Connection test failed')
                       .show();
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e('Test Connection', 'sixlab-tool'); ?>');
            }
        });
    });
});
</script>
