<?php
/**
 * Settings Admin Page Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$active_tab = $_GET['tab'] ?? 'general';
$general_settings = get_option('sixlab_general_settings', array());
?>

<div class="wrap">
    <h1><?php _e('6Lab Tool Settings', 'sixlab-tool'); ?></h1>
    
    <?php settings_errors('sixlab_settings'); ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=sixlab-settings&tab=general" 
           class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-settings&tab=security" 
           class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Security', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-settings&tab=integration" 
           class="nav-tab <?php echo $active_tab === 'integration' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Integrations', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-settings&tab=advanced" 
           class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'sixlab-tool'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('sixlab_settings_nonce'); ?>
            
            <?php if ($active_tab === 'general'): ?>
                <div class="sixlab-settings-section">
                    <h2><?php _e('General Settings', 'sixlab-tool'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="default_session_duration"><?php _e('Default Session Duration', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="default_session_duration" 
                                       name="sixlab_settings[default_session_duration]" 
                                       value="<?php echo esc_attr($general_settings['default_session_duration'] ?? '120'); ?>" 
                                       min="30" max="1440" class="small-text">
                                <?php _e('minutes', 'sixlab-tool'); ?>
                                <p class="description">
                                    <?php _e('How long should lab sessions last by default?', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_concurrent_sessions"><?php _e('Max Concurrent Sessions', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_concurrent_sessions" 
                                       name="sixlab_settings[max_concurrent_sessions]" 
                                       value="<?php echo esc_attr($general_settings['max_concurrent_sessions'] ?? '5'); ?>" 
                                       min="1" max="50" class="small-text">
                                <p class="description">
                                    <?php _e('Maximum number of concurrent lab sessions per user.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="auto_save_interval"><?php _e('Auto-save Interval', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="auto_save_interval" name="sixlab_settings[auto_save_interval]">
                                    <option value="30" <?php selected($general_settings['auto_save_interval'] ?? '60', '30'); ?>>
                                        <?php _e('30 seconds', 'sixlab-tool'); ?>
                                    </option>
                                    <option value="60" <?php selected($general_settings['auto_save_interval'] ?? '60', '60'); ?>>
                                        <?php _e('1 minute', 'sixlab-tool'); ?>
                                    </option>
                                    <option value="120" <?php selected($general_settings['auto_save_interval'] ?? '60', '120'); ?>>
                                        <?php _e('2 minutes', 'sixlab-tool'); ?>
                                    </option>
                                    <option value="300" <?php selected($general_settings['auto_save_interval'] ?? '60', '300'); ?>>
                                        <?php _e('5 minutes', 'sixlab-tool'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('How often should lab progress be automatically saved?', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Default Features', 'sixlab-tool'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[enable_ai_assistance]" value="1" 
                                           <?php checked($general_settings['enable_ai_assistance'] ?? '1', '1'); ?>>
                                    <?php _e('Enable AI assistance by default', 'sixlab-tool'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[enable_progress_tracking]" value="1" 
                                           <?php checked($general_settings['enable_progress_tracking'] ?? '1', '1'); ?>>
                                    <?php _e('Enable progress tracking', 'sixlab-tool'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[enable_session_recordings]" value="1" 
                                           <?php checked($general_settings['enable_session_recordings'] ?? '0', '1'); ?>>
                                    <?php _e('Enable session recordings', 'sixlab-tool'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($active_tab === 'security'): ?>
                <div class="sixlab-settings-section">
                    <h2><?php _e('Security Settings', 'sixlab-tool'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="session_timeout"><?php _e('Session Timeout', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="session_timeout" 
                                       name="sixlab_settings[session_timeout]" 
                                       value="<?php echo esc_attr($general_settings['session_timeout'] ?? '30'); ?>" 
                                       min="5" max="120" class="small-text">
                                <?php _e('minutes of inactivity', 'sixlab-tool'); ?>
                                <p class="description">
                                    <?php _e('Automatically end sessions after this period of inactivity.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="allowed_file_types"><?php _e('Allowed File Types', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="allowed_file_types" 
                                       name="sixlab_settings[allowed_file_types]" 
                                       value="<?php echo esc_attr($general_settings['allowed_file_types'] ?? 'txt,json,yaml,cfg'); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Comma-separated list of allowed file extensions for uploads.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_file_size"><?php _e('Max File Size', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_file_size" 
                                       name="sixlab_settings[max_file_size]" 
                                       value="<?php echo esc_attr($general_settings['max_file_size'] ?? '5'); ?>" 
                                       min="1" max="50" class="small-text">
                                <?php _e('MB', 'sixlab-tool'); ?>
                                <p class="description">
                                    <?php _e('Maximum file size for lab-related uploads.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Security Options', 'sixlab-tool'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[require_https]" value="1" 
                                           <?php checked($general_settings['require_https'] ?? '0', '1'); ?>>
                                    <?php _e('Require HTTPS for lab sessions', 'sixlab-tool'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[log_user_actions]" value="1" 
                                           <?php checked($general_settings['log_user_actions'] ?? '1', '1'); ?>>
                                    <?php _e('Log user actions in lab sessions', 'sixlab-tool'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($active_tab === 'integration'): ?>
                <div class="sixlab-settings-section">
                    <h2><?php _e('Integration Settings', 'sixlab-tool'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="learndash_integration"><?php _e('LearnDash Integration', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[learndash_integration]" value="1" 
                                           <?php checked($general_settings['learndash_integration'] ?? '1', '1'); ?>
                                           <?php echo !class_exists('LearnDash_Settings_Section') ? 'disabled' : ''; ?>>
                                    <?php _e('Enable LearnDash integration', 'sixlab-tool'); ?>
                                    <?php if (!class_exists('LearnDash_Settings_Section')): ?>
                                        <em><?php _e('(LearnDash not detected)', 'sixlab-tool'); ?></em>
                                    <?php endif; ?>
                                </label>
                                <p class="description">
                                    <?php _e('Integrate lab completion with LearnDash courses.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="webhook_url"><?php _e('Webhook URL', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="webhook_url" 
                                       name="sixlab_settings[webhook_url]" 
                                       value="<?php echo esc_attr($general_settings['webhook_url'] ?? ''); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Send lab completion notifications to this URL.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="api_key"><?php _e('API Key', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="api_key" 
                                       name="sixlab_settings[api_key]" 
                                       value="<?php echo esc_attr($general_settings['api_key'] ?? ''); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('API key for external integrations (optional).', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($active_tab === 'advanced'): ?>
                <div class="sixlab-settings-section">
                    <h2><?php _e('Advanced Settings', 'sixlab-tool'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="debug_mode"><?php _e('Debug Mode', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sixlab_settings[debug_mode]" value="1" 
                                           <?php checked($general_settings['debug_mode'] ?? '0', '1'); ?>>
                                    <?php _e('Enable debug logging', 'sixlab-tool'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Log detailed information for troubleshooting (may affect performance).', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cleanup_interval"><?php _e('Cleanup Interval', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="cleanup_interval" name="sixlab_settings[cleanup_interval]">
                                    <option value="daily" <?php selected($general_settings['cleanup_interval'] ?? 'daily', 'daily'); ?>>
                                        <?php _e('Daily', 'sixlab-tool'); ?>
                                    </option>
                                    <option value="weekly" <?php selected($general_settings['cleanup_interval'] ?? 'daily', 'weekly'); ?>>
                                        <?php _e('Weekly', 'sixlab-tool'); ?>
                                    </option>
                                    <option value="monthly" <?php selected($general_settings['cleanup_interval'] ?? 'daily', 'monthly'); ?>>
                                        <?php _e('Monthly', 'sixlab-tool'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('How often to clean up expired sessions and temporary data.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="data_retention"><?php _e('Data Retention', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="data_retention" 
                                       name="sixlab_settings[data_retention]" 
                                       value="<?php echo esc_attr($general_settings['data_retention'] ?? '90'); ?>" 
                                       min="1" max="365" class="small-text">
                                <?php _e('days', 'sixlab-tool'); ?>
                                <p class="description">
                                    <?php _e('How long to keep completed session data before permanent deletion.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="custom_css"><?php _e('Custom CSS', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="custom_css" name="sixlab_settings[custom_css]" 
                                          rows="10" class="large-text code"><?php echo esc_textarea($general_settings['custom_css'] ?? ''); ?></textarea>
                                <p class="description">
                                    <?php _e('Add custom CSS for the lab interface.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php submit_button(); ?>
        </form>
    </div>
</div>

<style>
.sixlab-settings-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 5px;
}

.sixlab-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.tab-content {
    margin-top: 20px;
}
</style>
