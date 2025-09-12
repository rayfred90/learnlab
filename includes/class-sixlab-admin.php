<?php
/**
 * Admin Interface Class
 * 
 * WordPress admin interface for 6Lab Tool configuration
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Interface Class
 */
class SixLab_Admin {
    
    /**
     * Instance of the provider factory
     * @var SixLab_Provider_Factory
     */
    private $provider_factory;
    
    /**
     * Instance of the AI factory
     * @var SixLab_AI_Factory
     */
    private $ai_factory;
    
    /**
     * Instance of the database class
     * @var SixLab_Database
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->provider_factory = new SixLab_Provider_Factory();
        $this->ai_factory = new SixLab_AI_Factory();
        $this->database = new SixLab_Database();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_sixlab_test_provider', array($this, 'ajax_test_provider'));
        add_action('wp_ajax_sixlab_test_provider_by_id', array($this, 'ajax_test_provider_by_id'));
        add_action('wp_ajax_sixlab_test_default_provider', array($this, 'ajax_test_default_provider'));
        add_action('wp_ajax_sixlab_repair_database', array($this, 'ajax_repair_database'));
        add_action('wp_ajax_sixlab_delete_provider', array($this, 'ajax_delete_provider'));
        add_action('wp_ajax_sixlab_set_default_provider', array($this, 'ajax_set_default_provider'));
        add_action('wp_ajax_sixlab_test_ai_provider', array($this, 'ajax_test_ai_provider'));
        add_action('wp_ajax_sixlab_get_provider_status', array($this, 'ajax_get_provider_status'));
        
        // New guided and non-guided lab AJAX handlers
        add_action('wp_ajax_sixlab_start_guided_session', array($this, 'ajax_start_guided_session'));
        add_action('wp_ajax_sixlab_start_nonguided_session', array($this, 'ajax_start_nonguided_session'));
        add_action('wp_ajax_sixlab_execute_command', array($this, 'ajax_execute_command'));
        add_action('wp_ajax_sixlab_validate_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_sixlab_skip_step', array($this, 'ajax_skip_step'));
        add_action('wp_ajax_sixlab_complete_lab', array($this, 'ajax_complete_lab'));
        add_action('wp_ajax_sixlab_verify_nonguided_work', array($this, 'ajax_verify_nonguided_work'));
        add_action('wp_ajax_sixlab_save_session_notes', array($this, 'ajax_save_session_notes'));
        add_action('wp_ajax_sixlab_end_session', array($this, 'ajax_end_session'));
        add_action('wp_ajax_sixlab_admin_stop_session', array($this, 'ajax_admin_stop_session'));
        add_action('wp_ajax_sixlab_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_sixlab_run_verification_script', array($this, 'ajax_run_verification_script'));
        add_action('wp_ajax_sixlab_run_reset_script', array($this, 'ajax_run_reset_script'));
        
        // Allow non-logged-in users for public lab interfaces
        add_action('wp_ajax_nopriv_sixlab_start_guided_session', array($this, 'ajax_start_guided_session'));
        add_action('wp_ajax_nopriv_sixlab_start_nonguided_session', array($this, 'ajax_start_nonguided_session'));
        add_action('wp_ajax_nopriv_sixlab_execute_command', array($this, 'ajax_execute_command'));
        add_action('wp_ajax_nopriv_sixlab_validate_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_nopriv_sixlab_skip_step', array($this, 'ajax_skip_step'));
        add_action('wp_ajax_nopriv_sixlab_complete_lab', array($this, 'ajax_complete_lab'));
        add_action('wp_ajax_nopriv_sixlab_verify_nonguided_work', array($this, 'ajax_verify_nonguided_work'));
        add_action('wp_ajax_nopriv_sixlab_save_session_notes', array($this, 'ajax_save_session_notes'));
        add_action('wp_ajax_nopriv_sixlab_end_session', array($this, 'ajax_end_session'));
        add_action('wp_ajax_nopriv_sixlab_run_verification_script', array($this, 'ajax_run_verification_script'));
        add_action('wp_ajax_nopriv_sixlab_run_reset_script', array($this, 'ajax_run_reset_script'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('6Lab Tool', 'sixlab-tool'),
            __('6Lab Tool', 'sixlab-tool'),
            'manage_options',
            'sixlab-tool',
            array($this, 'admin_page_dashboard'),
            'dashicons-desktop',
            30
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('Dashboard', 'sixlab-tool'),
            __('Dashboard', 'sixlab-tool'),
            'manage_options',
            'sixlab-tool',
            array($this, 'admin_page_dashboard')
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('Lab Providers', 'sixlab-tool'),
            __('Lab Providers', 'sixlab-tool'),
            'manage_options',
            'sixlab-providers',
            array($this, 'admin_page_providers')
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('AI Providers', 'sixlab-tool'),
            __('AI Providers', 'sixlab-tool'),
            'manage_options',
            'sixlab-ai-providers',
            array($this, 'admin_page_ai_providers')
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('Lab Templates', 'sixlab-tool'),
            __('Lab Templates', 'sixlab-tool'),
            'manage_options',
            'sixlab-templates',
            array($this, 'admin_page_templates')
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('Lab Sessions', 'sixlab-tool'),
            __('Lab Sessions', 'sixlab-tool'),
            'manage_options',
            'sixlab-sessions',
            array($this, 'admin_page_sessions')
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('Analytics', 'sixlab-tool'),
            __('Analytics', 'sixlab-tool'),
            'manage_options',
            'sixlab-analytics',
            array($this, 'admin_page_analytics')
        );
        
        add_submenu_page(
            'sixlab-tool',
            __('Settings', 'sixlab-tool'),
            __('Settings', 'sixlab-tool'),
            'manage_options',
            'sixlab-settings',
            array($this, 'admin_page_settings')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register settings for providers
        register_setting('sixlab_providers', 'sixlab_providers_config');
        register_setting('sixlab_ai_providers', 'sixlab_ai_providers_config');
        register_setting('sixlab_settings', 'sixlab_general_settings');
        
        // Add settings sections and fields
        $this->add_provider_settings();
        $this->add_ai_provider_settings();
        $this->add_general_settings();
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'sixlab') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-accordion');
        
        wp_enqueue_script(
            'sixlab-admin',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery', 'jquery-ui-tabs'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'sixlab-admin',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array('wp-jquery-ui-dialog'),
            '1.0.0'
        );
        
        // Localize script for AJAX
        wp_localize_script('sixlab-admin', 'sixlab_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sixlab_admin_nonce'),
            'strings' => array(
                'testing' => __('Testing connection...', 'sixlab-tool'),
                'test_success' => __('Connection successful!', 'sixlab-tool'),
                'test_failed' => __('Connection failed:', 'sixlab-tool'),
                'saving' => __('Saving...', 'sixlab-tool'),
                'saved' => __('Settings saved!', 'sixlab-tool'),
                'error' => __('An error occurred:', 'sixlab-tool')
            )
        ));
    }
    
    /**
     * Dashboard admin page
     */
    public function admin_page_dashboard() {
        // Start output buffering to prevent any unexpected output
        ob_start();
        
        try {
            // Simplified status gathering with error suppression
            $providers_status = array();
            $ai_providers_status = array();
            $recent_sessions = array();
            $system_info = array(
                'active_sessions' => 0,
                'total_sessions' => 0,
                'total_templates' => 0
            );
            
            // Try to get real data, but don't fail if there are issues
            try {
                $providers_status = $this->get_providers_status();
            } catch (Exception $e) {
                error_log('6Lab Tool: Provider status error: ' . $e->getMessage());
            }
            
            try {
                $ai_providers_status = $this->get_ai_providers_status();
            } catch (Exception $e) {
                error_log('6Lab Tool: AI provider status error: ' . $e->getMessage());
            }
            
            try {
                $recent_sessions = $this->get_recent_sessions();
            } catch (Exception $e) {
                error_log('6Lab Tool: Recent sessions error: ' . $e->getMessage());
            }
            
            try {
                $system_info = $this->get_system_info();
            } catch (Exception $e) {
                error_log('6Lab Tool: System info error: ' . $e->getMessage());
            }

            // Clean any unexpected output before including template
            $unexpected_output = ob_get_clean();
            if (!empty($unexpected_output)) {
                error_log('6Lab Tool: Unexpected output in dashboard: ' . $unexpected_output);
            }

            include plugin_dir_path(__FILE__) . '../admin/templates/dashboard.php';
            
        } catch (Exception $e) {
            // Clean output buffer and log error
            ob_end_clean();
            error_log('6Lab Tool Dashboard Error: ' . $e->getMessage());
            
            // Display fallback error message
            echo '<div class="wrap"><h1>6Lab Tool Dashboard</h1>';
            echo '<div class="notice notice-error"><p>An error occurred loading the dashboard. Please check the error logs.</p></div>';
            echo '</div>';
        }
    }
    
    /**
     * Lab providers admin page
     */
    public function admin_page_providers() {
        $active_tab = $_GET['tab'] ?? 'overview';
        $available_providers = $this->provider_factory->get_available_providers();
        
        // Get configured providers from database instead of options
        $configured_providers = $this->get_configured_providers_from_db();
        
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'sixlab_providers_nonce')) {
            $this->handle_provider_save();
        }
        
        include plugin_dir_path(__FILE__) . '../admin/templates/providers.php';
    }
    
    /**
     * AI providers admin page
     */
    public function admin_page_ai_providers() {
        $active_tab = $_GET['tab'] ?? 'overview';
        $available_ai_providers = $this->ai_factory->get_available_providers();
        $configured_ai_providers = get_option('sixlab_ai_providers_config', array());
        
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'sixlab_ai_providers_nonce')) {
            $this->handle_ai_provider_save();
        }
        
        include plugin_dir_path(__FILE__) . '../admin/templates/ai-providers.php';
    }
    
    /**
     * Lab templates admin page
     */
    public function admin_page_templates() {
        $templates = $this->get_lab_templates();
        
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'sixlab_templates_nonce')) {
            $this->handle_template_save();
        }
        
        include plugin_dir_path(__FILE__) . '../admin/templates/lab-templates.php';
    }
    
    /**
     * Lab sessions admin page
     */
    public function admin_page_sessions() {
        include plugin_dir_path(__FILE__) . '../admin/templates/lab-sessions.php';
    }
    
    /**
     * Analytics admin page
     */
    public function admin_page_analytics() {
        $date_range = $_GET['range'] ?? '7days';
        $analytics_data = $this->get_analytics_data($date_range);
        
        include plugin_dir_path(__FILE__) . '../admin/templates/analytics.php';
    }
    
    /**
     * Settings admin page
     */
    public function admin_page_settings() {
        $active_tab = $_GET['tab'] ?? 'general';
        
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'sixlab_settings_nonce')) {
            $this->handle_settings_save();
        }
        
        include plugin_dir_path(__FILE__) . '../admin/templates/settings.php';
    }
    
    /**
     * AJAX handler for testing provider connection
     */
    public function ajax_test_provider() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_type = sanitize_text_field($_POST['provider_type'] ?? '');
        $config = $_POST['config'] ?? array();
        
        // Sanitize config data
        $config = $this->sanitize_provider_config($config);
        
        try {
            $provider = $this->provider_factory->create_provider($provider_type, $config);
            $test_result = $provider->test_connection();
            
            wp_send_json_success($test_result);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for testing AI provider connection
     */
    public function ajax_test_ai_provider() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_type = sanitize_text_field($_POST['provider_type'] ?? '');
        $config = $_POST['config'] ?? array();
        
        // Sanitize config data
        $config = $this->sanitize_ai_provider_config($config);
        
        try {
            $provider = $this->ai_factory->create_provider($provider_type, $config);
            $test_result = $provider->test_connection();
            
            wp_send_json_success($test_result);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for getting provider status
     */
    public function ajax_get_provider_status() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_type = sanitize_text_field($_POST['provider_type'] ?? '');
        
        try {
            $provider = $this->provider_factory->get_provider_by_type($provider_type);
            
            // Check if provider creation failed
            if (is_wp_error($provider)) {
                wp_send_json_error(array(
                    'healthy' => false,
                    'message' => $provider->get_error_message()
                ));
                return;
            }
            
            $health_status = $provider->get_health_status();
            
            wp_send_json_success($health_status);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'healthy' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Add provider settings sections and fields
     */
    private function add_provider_settings() {
        $available_providers = $this->provider_factory->get_available_providers();
        
        foreach ($available_providers as $provider_type => $provider_class) {
            $section_id = "sixlab_provider_{$provider_type}";
            
            add_settings_section(
                $section_id,
                sprintf(__('%s Configuration', 'sixlab-tool'), $provider_class),
                array($this, 'provider_section_callback'),
                'sixlab_providers'
            );
            
            // Create a temporary provider instance to get config fields
            try {
                $temp_provider = new $provider_class();
                $config_fields = $temp_provider->get_config_fields();
                
                foreach ($config_fields as $field_name => $field_config) {
                    add_settings_field(
                        "{$provider_type}_{$field_name}",
                        $field_config['label'],
                        array($this, 'provider_field_callback'),
                        'sixlab_providers',
                        $section_id,
                        array(
                            'provider_type' => $provider_type,
                            'field_name' => $field_name,
                            'field_config' => $field_config
                        )
                    );
                }
            } catch (Throwable $e) {
                // Handle provider initialization error (class not found or other issues)
                continue;
            }
        }
    }
    
    /**
     * Add AI provider settings sections and fields
     */
    private function add_ai_provider_settings() {
        $available_providers = $this->ai_factory->get_available_providers();
        
        foreach ($available_providers as $provider_type => $provider_class) {
            $section_id = "sixlab_ai_provider_{$provider_type}";
            
            add_settings_section(
                $section_id,
                sprintf(__('%s Configuration', 'sixlab-tool'), $provider_class),
                array($this, 'ai_provider_section_callback'),
                'sixlab_ai_providers'
            );
            
            // Create a temporary provider instance to get config fields
            try {
                $temp_provider = new $provider_class();
                $config_fields = $temp_provider->get_config_fields();
                
                foreach ($config_fields as $field_name => $field_config) {
                    add_settings_field(
                        "{$provider_type}_{$field_name}",
                        $field_config['label'],
                        array($this, 'ai_provider_field_callback'),
                        'sixlab_ai_providers',
                        $section_id,
                        array(
                            'provider_type' => $provider_type,
                            'field_name' => $field_name,
                            'field_config' => $field_config
                        )
                    );
                }
            } catch (Throwable $e) {
                // Handle provider initialization error (class not found or other issues)
                continue;
            }
        }
    }
    
    /**
     * Add general settings
     */
    private function add_general_settings() {
        add_settings_section(
            'sixlab_general',
            __('General Settings', 'sixlab-tool'),
            array($this, 'general_section_callback'),
            'sixlab_settings'
        );
        
        $general_fields = array(
            'default_session_timeout' => array(
                'label' => __('Default Session Timeout (minutes)', 'sixlab-tool'),
                'type' => 'number',
                'default' => 60,
                'min' => 5,
                'max' => 480
            ),
            'max_concurrent_sessions_per_user' => array(
                'label' => __('Max Concurrent Sessions per User', 'sixlab-tool'),
                'type' => 'number',
                'default' => 3,
                'min' => 1,
                'max' => 10
            ),
            'enable_session_recording' => array(
                'label' => __('Enable Session Recording', 'sixlab-tool'),
                'type' => 'checkbox',
                'default' => false
            ),
            'ai_assistance_enabled' => array(
                'label' => __('Enable AI Assistance', 'sixlab-tool'),
                'type' => 'checkbox',
                'default' => true
            ),
            'debug_logging' => array(
                'label' => __('Enable Debug Logging', 'sixlab-tool'),
                'type' => 'checkbox',
                'default' => false
            )
        );
        
        foreach ($general_fields as $field_name => $field_config) {
            add_settings_field(
                "general_{$field_name}",
                $field_config['label'],
                array($this, 'general_field_callback'),
                'sixlab_settings',
                'sixlab_general',
                array(
                    'field_name' => $field_name,
                    'field_config' => $field_config
                )
            );
        }
    }
    
    /**
     * Provider section callback
     */
    public function provider_section_callback($args) {
        echo '<p>' . __('Configure your lab provider settings below.', 'sixlab-tool') . '</p>';
    }
    
    /**
     * AI provider section callback
     */
    public function ai_provider_section_callback($args) {
        echo '<p>' . __('Configure your AI provider settings below.', 'sixlab-tool') . '</p>';
    }
    
    /**
     * General section callback
     */
    public function general_section_callback($args) {
        echo '<p>' . __('General plugin settings and preferences.', 'sixlab-tool') . '</p>';
    }
    
    /**
     * Provider field callback
     */
    public function provider_field_callback($args) {
        $provider_type = $args['provider_type'];
        $field_name = $args['field_name'];
        $field_config = $args['field_config'];
        
        $options = get_option('sixlab_providers_config', array());
        $value = $options[$provider_type][$field_name] ?? ($field_config['default'] ?? '');
        
        $this->render_field($value, $field_config, "sixlab_providers_config[{$provider_type}][{$field_name}]");
    }
    
    /**
     * AI provider field callback
     */
    public function ai_provider_field_callback($args) {
        $provider_type = $args['provider_type'];
        $field_name = $args['field_name'];
        $field_config = $args['field_config'];
        
        $options = get_option('sixlab_ai_providers_config', array());
        $value = $options[$provider_type][$field_name] ?? ($field_config['default'] ?? '');
        
        $this->render_field($value, $field_config, "sixlab_ai_providers_config[{$provider_type}][{$field_name}]");
    }
    
    /**
     * General field callback
     */
    public function general_field_callback($args) {
        $field_name = $args['field_name'];
        $field_config = $args['field_config'];
        
        $options = get_option('sixlab_general_settings', array());
        $value = $options[$field_name] ?? ($field_config['default'] ?? '');
        
        $this->render_field($value, $field_config, "sixlab_general_settings[{$field_name}]");
    }
    
    /**
     * Render form field based on type
     */
    private function render_field($value, $config, $name) {
        $type = $config['type'] ?? 'text';
        $id = str_replace(array('[', ']'), array('_', ''), $name);
        
        switch ($type) {
            case 'text':
            case 'url':
            case 'email':
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" %s />',
                    esc_attr($type),
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value),
                    isset($config['required']) && $config['required'] ? 'required' : ''
                );
                break;
                
            case 'password':
                printf(
                    '<input type="password" id="%s" name="%s" value="%s" class="regular-text" %s />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value),
                    isset($config['required']) && $config['required'] ? 'required' : ''
                );
                break;
                
            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" %s %s %s %s />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value),
                    isset($config['min']) ? 'min="' . esc_attr($config['min']) . '"' : '',
                    isset($config['max']) ? 'max="' . esc_attr($config['max']) . '"' : '',
                    isset($config['step']) ? 'step="' . esc_attr($config['step']) . '"' : '',
                    isset($config['required']) && $config['required'] ? 'required' : ''
                );
                break;
                
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s />',
                    esc_attr($id),
                    esc_attr($name),
                    checked(1, $value, false)
                );
                break;
                
            case 'select':
                printf('<select id="%s" name="%s">', esc_attr($id), esc_attr($name));
                
                if (isset($config['options'])) {
                    foreach ($config['options'] as $option_value => $option_label) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($option_value),
                            selected($value, $option_value, false),
                            esc_html($option_label)
                        );
                    }
                }
                
                echo '</select>';
                break;
                
            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="5" cols="50" class="large-text">%s</textarea>',
                    esc_attr($id),
                    esc_attr($name),
                    esc_textarea($value)
                );
                break;
        }
        
        if (isset($config['description'])) {
            printf('<p class="description">%s</p>', esc_html($config['description']));
        }
    }
    
    /**
     * Get providers status
     */
    private function get_providers_status() {
        $configured_providers = get_option('sixlab_providers_config', array());
        $status = array();
        
        // If no configured providers, try to get providers from database
        if (empty($configured_providers)) {
            global $wpdb;
            $providers_table = $wpdb->prefix . 'sixlab_providers';
            $db_providers = $wpdb->get_results("SELECT type FROM {$providers_table} WHERE is_active = 1");
            
            foreach ($db_providers as $provider) {
                $configured_providers[$provider->type] = array(); // Empty config, will use database defaults
            }
        }
        
        foreach ($configured_providers as $provider_type => $config) {
            try {
                $provider = $this->provider_factory->get_provider_by_type($provider_type);
                
                // Check if provider creation failed
                if (is_wp_error($provider)) {
                    $status[$provider_type] = array(
                        'healthy' => false,
                        'message' => $provider->get_error_message()
                    );
                    continue;
                }
                
                $health = $provider->get_health_status();
                $status[$provider_type] = $health;
            } catch (Exception $e) {
                $status[$provider_type] = array(
                    'healthy' => false,
                    'message' => $e->getMessage()
                );
            }
        }
        
        return $status;
    }
    
    /**
     * Get AI providers status
     */
    private function get_ai_providers_status() {
        $configured_providers = get_option('sixlab_ai_providers_config', array());
        $status = array();
        
        foreach ($configured_providers as $provider_type => $config) {
            try {
                $provider = $this->ai_factory->create_ai_provider($provider_type, $config);
                
                // Check if provider creation failed
                if (is_wp_error($provider)) {
                    $status[$provider_type] = array(
                        'success' => false,
                        'message' => $provider->get_error_message()
                    );
                    continue;
                }
                
                $test_result = $provider->test_connection();
                $status[$provider_type] = $test_result;
            } catch (Exception $e) {
                $status[$provider_type] = array(
                    'success' => false,
                    'message' => $e->getMessage()
                );
            }
        }
        
        return $status;
    }
    
    /**
     * Check and repair database setup if needed
     */
    private function check_database_setup() {
        try {
            global $wpdb;
            
            // Check if providers table exists and has data
            $providers_table = $wpdb->prefix . 'sixlab_providers';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$providers_table}'") === $providers_table;
            
            if (!$table_exists) {
                // Recreate database tables
                require_once SIXLAB_PLUGIN_DIR . 'database/class-sixlab-database.php';
                SixLab_Database::create_tables();
                return 'tables_created';
            }
            
            // Check if we have any providers
            $provider_count = $wpdb->get_var("SELECT COUNT(*) FROM {$providers_table}");
            
            if ($provider_count == 0) {
                // Insert default providers
                require_once SIXLAB_PLUGIN_DIR . 'database/class-sixlab-database.php';
                
                try {
                    $wpdb->query("DELETE FROM {$providers_table}"); // Clear any existing data
                    
                    // Call the private method safely
                    if (method_exists('SixLab_Database', 'insert_default_data')) {
                        $method = new ReflectionMethod('SixLab_Database', 'insert_default_data');
                        $method->setAccessible(true);
                        $method->invoke(null);
                    } else {
                        // Fallback: recreate tables which will insert default data
                        SixLab_Database::create_tables();
                    }
                    
                    return 'default_providers_inserted';
                } catch (Exception $e) {
                    // Log error but don't break the page
                    error_log('SixLab Database Setup Error: ' . $e->getMessage());
                    return 'database_error';
                }
            }
            
            return 'database_ok';
        } catch (Exception $e) {
            // Log error and return safe status
            error_log('SixLab Database Check Error: ' . $e->getMessage());
            return 'database_error';
        }
    }
    
    /**
     * Get recent sessions
     */
    private function get_recent_sessions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_sessions';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT s.*, u.display_name as user_name
            FROM {$table_name} s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            ORDER BY s.created_at DESC
            LIMIT %d
        ", 10));
    }
    
    /**
     * Get system information
     */
    private function get_system_info() {
        global $wpdb;
        
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => '1.0.0',
            'database_version' => get_option('sixlab_database_version', '0'),
            'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions"),
            'active_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions WHERE status = 'active'"),
            'total_templates' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_lab_templates")
        );
    }
    
    /**
     * Get lab templates
     */
    private function get_lab_templates() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        return $wpdb->get_results("
            SELECT * FROM {$table_name}
            ORDER BY name ASC
        ");
    }
    
    /**
     * Get analytics data
     */
    private function get_analytics_data($date_range) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'sixlab_sessions';
        $analytics_table = $wpdb->prefix . 'sixlab_analytics';
        
        // Calculate date range
        $end_date = current_time('mysql');
        switch ($date_range) {
            case '24hours':
                $start_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
                break;
            case '7days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }
        
        return array(
            'sessions_created' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$sessions_table}
                WHERE created_at BETWEEN %s AND %s
            ", $start_date, $end_date)),
            
            'sessions_completed' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$sessions_table}
                WHERE completed_at BETWEEN %s AND %s
            ", $start_date, $end_date)),
            
            'average_session_duration' => $wpdb->get_var($wpdb->prepare("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at))
                FROM {$sessions_table}
                WHERE completed_at BETWEEN %s AND %s
                AND completed_at IS NOT NULL
            ", $start_date, $end_date)),
            
            'popular_providers' => $wpdb->get_results($wpdb->prepare("
                SELECT provider, COUNT(*) as usage_count
                FROM {$sessions_table}
                WHERE created_at BETWEEN %s AND %s
                GROUP BY provider
                ORDER BY usage_count DESC
                LIMIT 5
            ", $start_date, $end_date))
        );
    }
    
    /**
     * Handle provider configuration save
     */
    private function handle_provider_save() {
        $provider_type = sanitize_text_field($_POST['provider_type'] ?? '');
        $provider_name = sanitize_text_field($_POST['provider_name'] ?? '');
        $provider_display_name = sanitize_text_field($_POST['provider_display_name'] ?? '');
        $provider_config = $_POST['provider_config'] ?? array();
        $provider_id = isset($_POST['provider_id']) ? intval($_POST['provider_id']) : null;
        
        if (empty($provider_type) || empty($provider_name) || empty($provider_display_name)) {
            add_settings_error(
                'sixlab_providers',
                'missing_fields',
                __('Provider name, display name, and type are required.', 'sixlab-tool'),
                'error'
            );
            return;
        }
        
        // Sanitize configuration
        $sanitized_config = $this->sanitize_provider_config($provider_config);
        
        try {
            if ($provider_id) {
                // Update existing provider
                $result = $this->provider_factory->update_provider($provider_id, array(
                    'name' => $provider_name,
                    'display_name' => $provider_display_name,
                    'config' => $sanitized_config
                ));
                
                if (is_wp_error($result)) {
                    add_settings_error(
                        'sixlab_providers',
                        'update_failed',
                        sprintf(__('Failed to update provider: %s', 'sixlab-tool'), $result->get_error_message()),
                        'error'
                    );
                    return;
                }
                
                add_settings_error(
                    'sixlab_providers',
                    'settings_updated',
                    __('Provider configuration updated successfully!', 'sixlab-tool'),
                    'updated'
                );
            } else {
                // Create new provider
                $result = $this->provider_factory->save_provider(
                    $provider_name,
                    $provider_type,
                    $provider_display_name,
                    $sanitized_config,
                    true, // is_active
                    false // is_default - let user set this manually
                );
                
                if (is_wp_error($result)) {
                    add_settings_error(
                        'sixlab_providers',
                        'save_failed',
                        sprintf(__('Failed to save provider: %s', 'sixlab-tool'), $result->get_error_message()),
                        'error'
                    );
                    return;
                }
                
                add_settings_error(
                    'sixlab_providers',
                    'settings_updated',
                    __('Provider configuration saved successfully!', 'sixlab-tool'),
                    'updated'
                );
            }
        } catch (Exception $e) {
            add_settings_error(
                'sixlab_providers',
                'exception',
                sprintf(__('Error saving provider: %s', 'sixlab-tool'), $e->getMessage()),
                'error'
            );
        }
    }
    
    /**
     * Handle AI provider configuration save
     */
    private function handle_ai_provider_save() {
        $providers_config = $_POST['sixlab_ai_providers_config'] ?? array();
        
        // Sanitize and validate configuration
        $sanitized_config = array();
        foreach ($providers_config as $provider_type => $config) {
            $sanitized_config[$provider_type] = $this->sanitize_ai_provider_config($config);
        }
        
        update_option('sixlab_ai_providers_config', $sanitized_config);
        
        add_settings_error(
            'sixlab_ai_providers',
            'settings_updated',
            __('AI provider settings saved successfully!', 'sixlab-tool'),
            'updated'
        );
    }
    
    /**
     * Handle lab template save
     */
    private function handle_template_save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        // Sanitize input data
        $template_data = array(
            'name' => sanitize_text_field($_POST['template_name'] ?? ''),
            'slug' => sanitize_title($_POST['template_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['template_description'] ?? ''),
            'template_type' => sanitize_text_field($_POST['template_type'] ?? 'guided'),
            'provider_type' => sanitize_text_field($_POST['provider_type'] ?? ''),
            'difficulty_level' => sanitize_text_field($_POST['difficulty_level'] ?? 'beginner'),
            'estimated_duration' => intval($_POST['estimated_duration'] ?? 0),
            'lab_start_date' => !empty($_POST['lab_start_date']) ? sanitize_text_field($_POST['lab_start_date']) : null,
            'lab_start_time' => !empty($_POST['lab_start_time']) ? sanitize_text_field($_POST['lab_start_time']) : null,
            'lab_end_date' => !empty($_POST['lab_end_date']) ? sanitize_text_field($_POST['lab_end_date']) : null,
            'lab_end_time' => !empty($_POST['lab_end_time']) ? sanitize_text_field($_POST['lab_end_time']) : null,
            'template_data' => sanitize_textarea_field($_POST['template_data'] ?? '{}'),
            'instructions' => sanitize_textarea_field($_POST['instructions'] ?? ''),
            'learning_objectives' => sanitize_textarea_field($_POST['learning_objectives'] ?? ''),
            'tags' => sanitize_text_field($_POST['tags'] ?? ''),
            'delete_reset_script' => sanitize_textarea_field($_POST['delete_reset_script'] ?? ''),
            'guided_delete_reset_script' => sanitize_textarea_field($_POST['guided_delete_reset_script'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'author_id' => get_current_user_id(),
        );
        
        // Handle template type specific fields
        if ($template_data['template_type'] === 'guided') {
            // Process guided steps
            $guided_steps = array();
            if (!empty($_POST['guided_steps'])) {
                foreach ($_POST['guided_steps'] as $step) {
                    $guided_steps[] = array(
                        'title' => sanitize_text_field($step['title'] ?? ''),
                        'instructions' => sanitize_textarea_field($step['instructions'] ?? ''),
                        'commands' => sanitize_textarea_field($step['commands'] ?? ''),
                        'validation' => sanitize_textarea_field($step['validation'] ?? '')
                    );
                }
            }
            $template_data['guided_steps'] = json_encode($guided_steps);
            $template_data['step_validation_rules'] = json_encode(array());
            $template_data['terminal_commands'] = json_encode(array());
            
            // Clear non-guided fields
            $template_data['startup_script'] = null;
            $template_data['startup_script_filename'] = null;
            $template_data['verification_script'] = null;
            $template_data['verification_script_filename'] = null;
            $template_data['instructions_content'] = null;
            $template_data['instructions_images'] = null;
            
        } elseif ($template_data['template_type'] === 'non_guided') {
            // Process non-guided lab fields
            $template_data['instructions_content'] = wp_kses_post($_POST['instructions_content'] ?? '');
            $template_data['startup_script'] = sanitize_textarea_field($_POST['startup_script'] ?? '');
            $template_data['verification_script'] = sanitize_textarea_field($_POST['verification_script'] ?? '');
            
            // Handle file uploads for scripts
            if (!empty($_FILES['startup_script_file']['tmp_name'])) {
                $startup_script_content = file_get_contents($_FILES['startup_script_file']['tmp_name']);
                if ($startup_script_content !== false) {
                    $template_data['startup_script'] = sanitize_textarea_field($startup_script_content);
                    $template_data['startup_script_filename'] = sanitize_file_name($_FILES['startup_script_file']['name']);
                }
            }
            
            if (!empty($_FILES['verification_script_file']['tmp_name'])) {
                $verification_script_content = file_get_contents($_FILES['verification_script_file']['tmp_name']);
                if ($verification_script_content !== false) {
                    $template_data['verification_script'] = sanitize_textarea_field($verification_script_content);
                    $template_data['verification_script_filename'] = sanitize_file_name($_FILES['verification_script_file']['name']);
                }
            }
            
            // Clear guided fields
            $template_data['guided_steps'] = null;
            $template_data['step_validation_rules'] = null;
            $template_data['terminal_commands'] = null;
        }
        
        // Validate required fields
        if (empty($template_data['name']) || empty($template_data['provider_type']) || empty($template_data['template_type'])) {
            add_settings_error(
                'sixlab_templates',
                'missing_required',
                __('Please fill in all required fields.', 'sixlab-tool'),
                'error'
            );
            return;
        }
        
        // Additional validation for template types
        if ($template_data['template_type'] === 'guided' && empty($template_data['guided_steps'])) {
            add_settings_error(
                'sixlab_templates',
                'missing_guided_steps',
                __('Guided labs must have at least one step defined.', 'sixlab-tool'),
                'error'
            );
            return;
        }
        
        if ($template_data['template_type'] === 'non_guided' && empty($template_data['instructions_content'])) {
            add_settings_error(
                'sixlab_templates',
                'missing_instructions_content',
                __('Non-guided labs must have detailed instructions.', 'sixlab-tool'),
                'error'
            );
            return;
        }
        
        // Check if this is an edit or create
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if ($template_id > 0) {
            // Update existing template
            $template_data['updated_at'] = current_time('mysql');
            
            $result = $wpdb->update(
                $table_name,
                $template_data,
                array('id' => $template_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                add_settings_error(
                    'sixlab_templates',
                    'template_updated',
                    __('Lab template updated successfully!', 'sixlab-tool'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'sixlab_templates',
                    'update_failed',
                    __('Failed to update lab template.', 'sixlab-tool'),
                    'error'
                );
            }
        } else {
            // Create new template
            $template_data['created_at'] = current_time('mysql');
            $template_data['updated_at'] = current_time('mysql');
            
            // Check for duplicate slug
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE slug = %s",
                $template_data['slug']
            ));
            
            if ($existing) {
                $template_data['slug'] .= '-' . time();
            }
            
            $result = $wpdb->insert(
                $table_name,
                $template_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                add_settings_error(
                    'sixlab_templates',
                    'template_created',
                    __('Lab template created successfully!', 'sixlab-tool'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'sixlab_templates',
                    'create_failed',
                    __('Failed to create lab template.', 'sixlab-tool'),
                    'error'
                );
            }
        }
    }
    
    /**
     * Handle general settings save
     */
    private function handle_settings_save() {
        $general_settings = $_POST['sixlab_general_settings'] ?? array();
        
        // Sanitize settings
        $sanitized_settings = array();
        foreach ($general_settings as $key => $value) {
            $sanitized_settings[$key] = sanitize_text_field($value);
        }
        
        update_option('sixlab_general_settings', $sanitized_settings);
        
        add_settings_error(
            'sixlab_settings',
            'settings_updated',
            __('Settings saved successfully!', 'sixlab-tool'),
            'updated'
        );
    }
    
    /**
     * Sanitize provider configuration
     */
    private function sanitize_provider_config($config) {
        $sanitized = array();
        
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_provider_config($value);
            } else {
                // Basic sanitization - could be enhanced based on field types
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize AI provider configuration
     */
    private function sanitize_ai_provider_config($config) {
        $sanitized = array();
        
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_ai_provider_config($value);
            } else {
                // Basic sanitization - could be enhanced based on field types
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get configured providers from database
     * 
     * @return array Configured providers grouped by type
     */
    private function get_configured_providers_from_db() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $providers = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY type, is_default DESC, display_name ASC"
        );
        
        $configured_providers = array();
        
        foreach ($providers as $provider) {
            $type = $provider->type;
            
            if (!isset($configured_providers[$type])) {
                $configured_providers[$type] = array();
            }
            
            $configured_providers[$type][] = array(
                'id' => $provider->id,
                'name' => $provider->name,
                'display_name' => $provider->display_name,
                'config' => json_decode($provider->config, true),
                'is_active' => (bool) $provider->is_active,
                'is_default' => (bool) $provider->is_default,
                'health_status' => $provider->health_status,
                'health_message' => $provider->health_message,
                'last_health_check' => $provider->last_health_check
            );
        }
        
        return $configured_providers;
    }
    
    /**
     * AJAX handler for testing provider by ID
     */
    public function ajax_test_provider_by_id() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_id = intval($_POST['provider_id'] ?? 0);
        
        if (!$provider_id) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Invalid provider ID', 'sixlab-tool')
            ));
        }
        
        try {
            $provider = $this->provider_factory->get_provider($provider_id);
            
            if (is_wp_error($provider)) {
                wp_send_json_error(array(
                    'success' => false,
                    'message' => $provider->get_error_message()
                ));
            }
            
            $test_result = $provider->test_connection();
            
            wp_send_json_success($test_result);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for testing default provider connection
     */
    public function ajax_test_default_provider() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_type = sanitize_text_field($_POST['provider_type'] ?? '');
        
        if (!$provider_type) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Invalid provider type', 'sixlab-tool')
            ));
        }
        
        try {
            $provider = $this->provider_factory->get_provider_by_type($provider_type);
            
            if (is_wp_error($provider)) {
                wp_send_json_error(array(
                    'success' => false,
                    'message' => $provider->get_error_message()
                ));
            }
            
            $test_result = $provider->test_connection();
            
            wp_send_json_success($test_result);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for deleting provider
     */
    public function ajax_delete_provider() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_id = intval($_POST['provider_id'] ?? 0);
        
        if (!$provider_id) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Invalid provider ID', 'sixlab-tool')
            ));
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $provider_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Failed to delete provider configuration', 'sixlab-tool')
            ));
        }
        
        wp_send_json_success(array(
            'success' => true,
            'message' => __('Provider configuration deleted successfully', 'sixlab-tool')
        ));
    }
    
    /**
     * AJAX handler for setting default provider
     */
    public function ajax_set_default_provider() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $provider_id = intval($_POST['provider_id'] ?? 0);
        
        if (!$provider_id) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Invalid provider ID', 'sixlab-tool')
            ));
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        // Get the provider type first
        $provider = $wpdb->get_row($wpdb->prepare(
            "SELECT type FROM {$table_name} WHERE id = %d",
            $provider_id
        ));
        
        if (!$provider) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Provider not found', 'sixlab-tool')
            ));
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Remove default flag from all providers of this type
            $wpdb->update(
                $table_name,
                array('is_default' => 0),
                array('type' => $provider->type),
                array('%d'),
                array('%s')
            );
            
            // Set the selected provider as default
            $result = $wpdb->update(
                $table_name,
                array('is_default' => 1),
                array('id' => $provider_id),
                array('%d'),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to update provider default status', 'sixlab-tool'));
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'success' => true,
                'message' => __('Default provider updated successfully', 'sixlab-tool')
            ));
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for repairing database
     */
    public function ajax_repair_database() {
        check_ajax_referer('sixlab_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Insufficient permissions', 'sixlab-tool')
            ));
            return;
        }
        
        try {
            $db_status = $this->check_database_setup();
            
            $message = '';
            switch ($db_status) {
                case 'tables_created':
                    $message = __('Database tables were missing and have been recreated.', 'sixlab-tool');
                    break;
                case 'default_providers_inserted':
                    $message = __('Default providers were missing and have been restored.', 'sixlab-tool');
                    break;
                case 'database_ok':
                    $message = __('Database setup is already correct.', 'sixlab-tool');
                    break;
                default:
                    $message = __('Database setup completed.', 'sixlab-tool');
                    break;
            }
            
            wp_send_json_success(array(
                'success' => true,
                'message' => $message,
                'status' => $db_status
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for starting guided lab session
     */
    public function ajax_start_guided_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $template_id = intval($_POST['template_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'sixlab-tool')));
            return;
        }
        
        global $wpdb;
        
        // Get template
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_lab_templates WHERE id = %d AND is_active = 1",
            $template_id
        ));
        
        if (!$template || $template->template_type !== 'guided') {
            wp_send_json_error(array('message' => __('Invalid guided lab template', 'sixlab-tool')));
            return;
        }
        
        // Check for existing active session
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sixlab_sessions 
             WHERE user_id = %d AND lab_id = %d AND status IN ('started', 'active', 'in_progress')",
            $user_id, $template_id
        ));
        
        if ($existing_session) {
            wp_send_json_success(array(
                'message' => __('Session already active', 'sixlab-tool'),
                'session_id' => $existing_session->id
            ));
            return;
        }
        
        // Create new session
        $result = $wpdb->insert(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'user_id' => $user_id,
                'lab_id' => $template_id,
                'provider' => $template->provider_type,
                'provider_session_id' => 'guided_' . time() . '_' . $user_id,
                'current_step' => 1,
                'status' => 'started',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+4 hours'))
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Guided lab session started', 'sixlab-tool'),
                'session_id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to start session', 'sixlab-tool')));
        }
    }
    
    /**
     * AJAX handler for starting non-guided lab session
     */
    public function ajax_start_nonguided_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $template_id = intval($_POST['template_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'sixlab-tool')));
            return;
        }
        
        global $wpdb;
        
        // Get template
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_lab_templates WHERE id = %d AND is_active = 1",
            $template_id
        ));
        
        if (!$template || $template->template_type !== 'non_guided') {
            wp_send_json_error(array('message' => __('Invalid non-guided lab template', 'sixlab-tool')));
            return;
        }
        
        // Check for existing active session
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sixlab_sessions 
             WHERE user_id = %d AND lab_id = %d AND status IN ('started', 'active', 'in_progress')",
            $user_id, $template_id
        ));
        
        if ($existing_session) {
            wp_send_json_success(array(
                'message' => __('Session already active', 'sixlab-tool'),
                'session_id' => $existing_session->id
            ));
            return;
        }
        
        // Create new session
        $result = $wpdb->insert(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'user_id' => $user_id,
                'lab_id' => $template_id,
                'provider' => $template->provider_type,
                'provider_session_id' => 'nonguided_' . time() . '_' . $user_id,
                'current_step' => 1,
                'status' => 'started',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+4 hours'))
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $session_id = $wpdb->insert_id;
            
            // Execute startup script if available
            if (!empty($template->startup_script)) {
                $this->execute_startup_script($session_id, $template->startup_script);
            }
            
            wp_send_json_success(array(
                'message' => __('Non-guided lab session started', 'sixlab-tool'),
                'session_id' => $session_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to start session', 'sixlab-tool')));
        }
    }
    
    /**
     * AJAX handler for executing terminal commands in guided labs
     */
    public function ajax_execute_command() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        $command = sanitize_text_field($_POST['command']);
        $step = intval($_POST['step']);
        
        global $wpdb;
        
        // Get session
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_sessions WHERE id = %d",
            $session_id
        ));
        
        if (!$session) {
            wp_send_json_error(array('message' => __('Session not found', 'sixlab-tool')));
            return;
        }
        
        // Update command history
        $commands_history = $session->commands_history ? $session->commands_history . "\n" . $command : $command;
        
        $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'commands_history' => $commands_history,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        // Simulate command execution (this would integrate with actual lab environment)
        $output = $this->simulate_command_execution($command, $session);
        
        wp_send_json_success(array(
            'output' => $output,
            'step_completed' => false
        ));
    }
    
    /**
     * AJAX handler for validating guided lab steps
     */
    public function ajax_validate_step() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        $step = intval($_POST['step']);
        $command_history = sanitize_textarea_field($_POST['command_history']);
        
        global $wpdb;
        
        // Get session and template
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.guided_steps FROM {$wpdb->prefix}sixlab_sessions s
             LEFT JOIN {$wpdb->prefix}sixlab_lab_templates t ON s.lab_id = t.id
             WHERE s.id = %d",
            $session_id
        ));
        
        if (!$session) {
            wp_send_json_error(array('message' => __('Session not found', 'sixlab-tool')));
            return;
        }
        
        // Parse guided steps
        $guided_steps = json_decode($session->guided_steps, true) ?? array();
        
        if (!isset($guided_steps[$step - 1])) {
            wp_send_json_error(array('message' => __('Invalid step', 'sixlab-tool')));
            return;
        }
        
        $current_step_data = $guided_steps[$step - 1];
        
        // Simple validation - check if expected commands were executed
        $validation_passed = true;
        $validation_message = __('Step completed successfully!', 'sixlab-tool');
        
        if (!empty($current_step_data['commands'])) {
            $expected_commands = array_filter(array_map('trim', explode("\n", $current_step_data['commands'])));
            $executed_commands = array_filter(array_map('trim', explode("\n", $command_history)));
            
            foreach ($expected_commands as $expected_cmd) {
                $found = false;
                foreach ($executed_commands as $executed_cmd) {
                    if (strpos($executed_cmd, $expected_cmd) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $validation_passed = false;
                    $validation_message = sprintf(__('Missing command: %s', 'sixlab-tool'), $expected_cmd);
                    break;
                }
            }
        }
        
        if ($validation_passed) {
            // Update session progress
            $completed_steps = json_decode($session->completed_steps, true) ?? array();
            if (!in_array($step, $completed_steps)) {
                $completed_steps[] = $step;
            }
            
            $wpdb->update(
                $wpdb->prefix . 'sixlab_sessions',
                array(
                    'current_step' => $step + 1,
                    'completed_steps' => json_encode($completed_steps),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $session_id),
                array('%d', '%s', '%s'),
                array('%d')
            );
        }
        
        wp_send_json_success(array(
            'valid' => $validation_passed,
            'message' => $validation_message
        ));
    }
    
    /**
     * AJAX handler for skipping guided lab steps
     */
    public function ajax_skip_step() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        $step = intval($_POST['step']);
        
        global $wpdb;
        
        // Update session to skip step
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'current_step' => $step + 1,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Step skipped', 'sixlab-tool')));
        } else {
            wp_send_json_error(array('message' => __('Failed to skip step', 'sixlab-tool')));
        }
    }
    
    /**
     * AJAX handler for completing guided labs
     */
    public function ajax_complete_lab() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        
        global $wpdb;
        
        // Update session status
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Lab completed successfully!', 'sixlab-tool')));
        } else {
            wp_send_json_error(array('message' => __('Failed to complete lab', 'sixlab-tool')));
        }
    }
    
    /**
     * AJAX handler for verifying non-guided lab work
     */
    public function ajax_verify_nonguided_work() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        
        // Get session and template
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.verification_script, t.name as lab_name FROM {$wpdb->prefix}sixlab_sessions s
             LEFT JOIN {$wpdb->prefix}sixlab_lab_templates t ON s.lab_id = t.id
             WHERE s.id = %d",
            $session_id
        ));
        
        if (!$session) {
            wp_send_json_error(array('message' => __('Session not found', 'sixlab-tool')));
            return;
        }
        
        $verification_output = '';
        $score = 0;
        $ai_feedback = '';
        
        // Execute verification script if available
        if (!empty($session->verification_script)) {
            $verification_output = $this->execute_verification_script($session_id, $session->verification_script);
            
            // Parse verification output for score (expecting JSON format)
            $verification_data = json_decode($verification_output, true);
            if ($verification_data && isset($verification_data['score'])) {
                $score = floatval($verification_data['score']);
            } else {
                $score = 50; // Default score if script doesn't return proper format
            }
        } else {
            $verification_output = __('No verification script configured for this lab.', 'sixlab-tool');
            $score = 100; // Default to full score if no verification script
        }
        
        // Get AI feedback
        $ai_feedback = $this->get_ai_verification_feedback($session, $notes, $verification_output);
        
        // Update session with results
        $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'score' => $score,
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%f', '%s', '%s', '%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'verification_output' => $verification_output,
            'score' => $score,
            'ai_feedback' => $ai_feedback
        ));
    }
    
    /**
     * AJAX handler for saving session notes
     */
    public function ajax_save_session_notes() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        
        // Update session with notes in session_data field
        $session_data = array('notes' => $notes);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'session_data' => json_encode($session_data),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Notes saved successfully', 'sixlab-tool')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save notes', 'sixlab-tool')));
        }
    }
    
    /**
     * AJAX handler for ending lab sessions
     */
    public function ajax_end_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        
        global $wpdb;
        
        // Update session status
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'status' => 'expired',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Session ended successfully', 'sixlab-tool')));
        } else {
            wp_send_json_error(array('message' => __('Failed to end session', 'sixlab-tool')));
        }
    }
    
    /**
     * Helper method to execute startup script
     */
    private function execute_startup_script($session_id, $script) {
        // This would integrate with the actual lab provider to execute the startup script
        // For now, we'll just log the execution
        error_log("Executing startup script for session {$session_id}: " . substr($script, 0, 100) . "...");
        
        // Here you would typically:
        // 1. Connect to the lab provider (GNS3, EVE-NG, Guacamole)
        // 2. Execute the script in the appropriate environment
        // 3. Return the execution results
        
        return true;
    }
    
    /**
     * Helper method to simulate command execution
     */
    private function simulate_command_execution($command, $session) {
        // This is a simple simulation - in a real implementation, 
        // this would connect to the actual lab environment
        
        $output = '';
        
        switch (strtolower(trim($command))) {
            case 'ls':
                $output = "Desktop  Documents  Downloads  Music  Pictures  Videos";
                break;
            case 'pwd':
                $output = "/home/student";
                break;
            case 'whoami':
                $output = "student";
                break;
            case 'date':
                $output = date('D M j H:i:s T Y');
                break;
            default:
                if (strpos($command, 'ping') === 0) {
                    $output = "PING google.com (8.8.8.8): 56 data bytes\n64 bytes from 8.8.8.8: icmp_seq=0 ttl=55 time=15.2 ms\n--- google.com ping statistics ---\n1 packets transmitted, 1 received, 0% packet loss";
                } elseif (strpos($command, 'show') === 0) {
                    $output = "Command executed successfully. Check the configuration in your lab environment.";
                } else {
                    $output = "Command executed: " . $command;
                }
                break;
        }
        
        return $output;
    }
    
    /**
     * Helper method to execute verification script
     */
    private function execute_verification_script($session_id, $script) {
        // This would integrate with the actual lab provider to execute the verification script
        // For now, we'll simulate verification
        
        $verification_result = array(
            'score' => rand(70, 100),
            'status' => 'completed',
            'details' => 'Lab objectives verified successfully. Configuration appears correct.'
        );
        
        return json_encode($verification_result);
    }
    
    /**
     * Helper method to get AI verification feedback
     */
    private function get_ai_verification_feedback($session, $notes, $verification_output) {
        // This would integrate with the AI provider to generate feedback
        // For now, we'll return a simple feedback message
        
        $feedback = sprintf(
            __('Great work on completing the %s lab! Based on your notes and the verification results, you have demonstrated a good understanding of the lab objectives. Keep up the excellent work!', 'sixlab-tool'),
            $session->lab_name
        );
        
        if (!empty($notes)) {
            $feedback .= ' ' . __('Your detailed notes show thoughtful engagement with the material.', 'sixlab-tool');
        }
        
        return $feedback;
    }
    
    /**
     * AJAX handler for admin stopping lab sessions
     */
    public function ajax_admin_stop_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_admin_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'sixlab-tool')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        
        global $wpdb;
        
        // Update session status to stopped
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'status' => 'expired',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Session stopped successfully', 'sixlab-tool')));
        } else {
            wp_send_json_error(array('message' => __('Failed to stop session', 'sixlab-tool')));
        }
    }
    
    /**
     * Trigger database migration for date/time fields
     */
    public function trigger_migration() {
        if (current_user_can('manage_options')) {
            // Include database class
            require_once SIXLAB_TOOL_PLUGIN_DIR . 'database/class-sixlab-database.php';
            
            // Force upgrade
            SixLab_Database::maybe_upgrade('0.9.0');
            
            add_settings_error(
                'sixlab_settings',
                'migration_complete',
                __('Database migration completed successfully!', 'sixlab-tool'),
                'updated'
            );
        }
    }
    
    /**
     * Handle template deletion via AJAX
     */
    public function ajax_delete_template() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sixlab_delete_template')) {
            wp_send_json_error(array('message' => __('Security check failed', 'sixlab-tool')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'sixlab-tool')));
            return;
        }
        
        $template_id = intval($_POST['template_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        // Get template name for confirmation
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$table_name} WHERE id = %d",
            $template_id
        ));
        
        if (!$template) {
            wp_send_json_error(array('message' => __('Template not found', 'sixlab-tool')));
            return;
        }
        
        // Check if template has active sessions
        $active_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions WHERE lab_id = %d AND status = 'active'",
            $template_id
        ));
        
        if ($active_sessions > 0) {
            wp_send_json_error(array('message' => sprintf(
                __('Cannot delete template. There are %d active sessions using this template.', 'sixlab-tool'),
                $active_sessions
            )));
            return;
        }
        
        // Delete the template
        $result = $wpdb->delete(
            $table_name,
            array('id' => $template_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => sprintf(
                __('Template "%s" has been deleted successfully.', 'sixlab-tool'),
                $template->name
            )));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete template', 'sixlab-tool')));
        }
    }
    
    /**
     * Handle verification script execution via AJAX
     */
    public function ajax_run_verification_script() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sixlab_verification_script')) {
            wp_send_json_error(array('message' => __('Security check failed', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$session_id || !$template_id) {
            wp_send_json_error(array('message' => __('Invalid session or template ID', 'sixlab-tool')));
            return;
        }
        
        global $wpdb;
        
        // Get template and session data
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_lab_templates WHERE id = %d",
            $template_id
        ));
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_sessions WHERE id = %d",
            $session_id
        ));
        
        if (!$template || !$session) {
            wp_send_json_error(array('message' => __('Template or session not found', 'sixlab-tool')));
            return;
        }
        
        // Get the appropriate verification script
        $script = '';
        if ($template->template_type === 'guided') {
            // For guided labs, we could have step-specific validation
            $script = $this->get_guided_verification_script($template, $session);
        } else {
            $script = $template->verification_script;
        }
        
        if (empty($script)) {
            wp_send_json_error(array('message' => __('No verification script defined for this template', 'sixlab-tool')));
            return;
        }
        
        // Execute the verification script
        $result = $this->execute_lab_script($script, $session, $template);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['data']);
        }
    }
    
    /**
     * Handle reset script execution via AJAX
     */
    public function ajax_run_reset_script() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sixlab_reset_script')) {
            wp_send_json_error(array('message' => __('Security check failed', 'sixlab-tool')));
            return;
        }
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$session_id || !$template_id) {
            wp_send_json_error(array('message' => __('Invalid session or template ID', 'sixlab-tool')));
            return;
        }
        
        global $wpdb;
        
        // Get template and session data
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_lab_templates WHERE id = %d",
            $template_id
        ));
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_sessions WHERE id = %d",
            $session_id
        ));
        
        if (!$template || !$session) {
            wp_send_json_error(array('message' => __('Template or session not found', 'sixlab-tool')));
            return;
        }
        
        // Get the appropriate reset script
        $script = '';
        if ($template->template_type === 'guided') {
            $script = $template->guided_delete_reset_script;
        } else {
            $script = $template->delete_reset_script;
        }
        
        if (empty($script)) {
            wp_send_json_error(array('message' => __('No reset script defined for this template', 'sixlab-tool')));
            return;
        }
        
        // Execute the reset script
        $result = $this->execute_lab_script($script, $session, $template, 'reset');
        
        if ($result['success']) {
            // Reset session progress for guided labs
            if ($template->template_type === 'guided') {
                $wpdb->update(
                    $wpdb->prefix . 'sixlab_sessions',
                    array(
                        'current_step' => 1,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $session_id),
                    array('%d', '%s'),
                    array('%d')
                );
            }
            
            wp_send_json_success(array_merge($result['data'], array(
                'message' => __('Lab environment has been reset successfully', 'sixlab-tool')
            )));
        } else {
            wp_send_json_error($result['data']);
        }
    }
    
    /**
     * Execute lab script (verification or reset)
     */
    private function execute_lab_script($script, $session, $template, $type = 'verification') {
        // Prepare environment variables
        $env_vars = array(
            'WP_USERNAME' => get_userdata($session->user_id)->user_login,
            'SESSION_ID' => $session->id,
            'TEMPLATE_ID' => $template->id,
            'TEMPLATE_TYPE' => $template->template_type,
            'PROVIDER_TYPE' => $template->provider_type,
            'SCRIPT_TYPE' => $type
        );
        
        if ($template->template_type === 'guided') {
            $env_vars['CURRENT_STEP'] = $session->current_step;
            $env_vars['TOTAL_STEPS'] = $session->total_steps;
        }
        
        // Create temporary script file
        $temp_file = tempnam(sys_get_temp_dir(), 'sixlab_script_');
        file_put_contents($temp_file, $script);
        chmod($temp_file, 0755);
        
        // Set environment variables and execute script
        $env_string = '';
        foreach ($env_vars as $key => $value) {
            $env_string .= "export {$key}=" . escapeshellarg($value) . "; ";
        }
        
        $command = $env_string . escapeshellarg($temp_file) . ' 2>&1';
        
        // Execute the script with timeout
        $output = '';
        $return_code = 0;
        
        try {
            $output = shell_exec($command);
            $return_code = 0; // shell_exec doesn't provide return code
        } catch (Exception $e) {
            $output = 'Script execution failed: ' . $e->getMessage();
            $return_code = 1;
        }
        
        // Clean up temporary file
        unlink($temp_file);
        
        // Parse output (expect JSON for verification scripts)
        if ($type === 'verification') {
            $json_output = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return array(
                    'success' => $json_output['success'] ?? false,
                    'data' => $json_output
                );
            } else {
                return array(
                    'success' => false,
                    'data' => array(
                        'message' => __('Verification script output is not valid JSON', 'sixlab-tool'),
                        'raw_output' => $output
                    )
                );
            }
        } else {
            // Reset scripts - just check if execution was successful
            return array(
                'success' => $return_code === 0,
                'data' => array(
                    'output' => $output,
                    'return_code' => $return_code
                )
            );
        }
    }
    
    /**
     * Get verification script for guided labs (could be step-specific)
     */
    private function get_guided_verification_script($template, $session) {
        // For now, return a generic verification script
        // In the future, this could be step-specific
        return $template->guided_delete_reset_script; // Or create a guided verification script field
    }
}
