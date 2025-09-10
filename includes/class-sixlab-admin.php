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
        add_action('wp_ajax_sixlab_test_ai_provider', array($this, 'ajax_test_ai_provider'));
        add_action('wp_ajax_sixlab_get_provider_status', array($this, 'ajax_get_provider_status'));
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
        // Get system status
        $providers_status = $this->get_providers_status();
        $ai_providers_status = $this->get_ai_providers_status();
        $recent_sessions = $this->get_recent_sessions();
        $system_info = $this->get_system_info();
        
        include plugin_dir_path(__FILE__) . '../admin/templates/dashboard.php';
    }
    
    /**
     * Lab providers admin page
     */
    public function admin_page_providers() {
        $active_tab = $_GET['tab'] ?? 'overview';
        $available_providers = $this->provider_factory->get_available_providers();
        $configured_providers = get_option('sixlab_providers_config', array());
        
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
            $provider = $this->provider_factory->get_provider($provider_type);
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
        
        foreach ($configured_providers as $provider_type => $config) {
            try {
                $provider = $this->provider_factory->get_provider($provider_type);
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
                $provider = $this->ai_factory->get_provider($provider_type);
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
        $providers_config = $_POST['sixlab_providers_config'] ?? array();
        
        // Sanitize and validate configuration
        $sanitized_config = array();
        foreach ($providers_config as $provider_type => $config) {
            $sanitized_config[$provider_type] = $this->sanitize_provider_config($config);
        }
        
        update_option('sixlab_providers_config', $sanitized_config);
        
        add_settings_error(
            'sixlab_providers',
            'settings_updated',
            __('Provider settings saved successfully!', 'sixlab-tool'),
            'updated'
        );
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
            'provider_type' => sanitize_text_field($_POST['provider_type'] ?? ''),
            'difficulty_level' => sanitize_text_field($_POST['difficulty_level'] ?? 'beginner'),
            'estimated_duration' => intval($_POST['estimated_duration'] ?? 0),
            'template_data' => sanitize_textarea_field($_POST['template_data'] ?? '{}'),
            'instructions' => sanitize_textarea_field($_POST['instructions'] ?? ''),
            'learning_objectives' => sanitize_textarea_field($_POST['learning_objectives'] ?? ''),
            'tags' => sanitize_text_field($_POST['tags'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'author_id' => get_current_user_id(),
        );
        
        // Validate required fields
        if (empty($template_data['name']) || empty($template_data['provider_type'])) {
            add_settings_error(
                'sixlab_templates',
                'missing_required',
                __('Please fill in all required fields.', 'sixlab-tool'),
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
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s'),
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
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
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
}
