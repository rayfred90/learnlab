<?php
/**
 * 6Lab Tool - Admin Interface Manager
 * Handles WordPress admin interface setup and menu management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SixLab_Admin_Interface {
    
    /**
     * Admin interface configuration
     * @var array
     */
    private $admin_config;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_admin_config();
        $this->init_hooks();
    }
    
    /**
     * Load admin interface configuration
     */
    private function load_admin_config() {
        $this->admin_config = array(
            'menu_structure' => array(
                'main_menu' => array(
                    'title' => '6Lab Tool',
                    'capability' => 'manage_options',
                    'icon' => 'dashicons-networking',
                    'position' => 30,
                    'submenus' => array(
                        array(
                            'title' => 'Dashboard',
                            'slug' => 'sixlab-dashboard',
                            'callback' => 'render_dashboard_page'
                        ),
                        array(
                            'title' => 'Lab Providers',
                            'slug' => 'sixlab-providers',
                            'callback' => 'render_providers_page'
                        ),
                        array(
                            'title' => 'AI Assistant',
                            'slug' => 'sixlab-ai-config',
                            'callback' => 'render_ai_config_page'
                        ),
                        array(
                            'title' => 'Lab Templates',
                            'slug' => 'sixlab-templates',
                            'callback' => 'render_templates_page'
                        ),
                        array(
                            'title' => 'Analytics',
                            'slug' => 'sixlab-analytics',
                            'callback' => 'render_analytics_page'
                        ),
                        array(
                            'title' => 'Settings',
                            'slug' => 'sixlab-settings',
                            'callback' => 'render_settings_page'
                        ),
                        array(
                            'title' => 'Automation',
                            'slug' => 'sixlab-automation',
                            'callback' => 'render_automation_page'
                        )
                    )
                )
            ),
            'dashboard_widgets' => array(
                'active_sessions' => array(
                    'title' => 'Active Lab Sessions',
                    'type' => 'realtime_counter',
                    'refresh_interval' => 30000,
                    'chart_type' => 'line'
                ),
                'provider_health' => array(
                    'title' => 'Provider Health Status',
                    'type' => 'status_grid',
                    'refresh_interval' => 60000
                ),
                'ai_usage' => array(
                    'title' => 'AI Assistant Usage',
                    'type' => 'usage_chart',
                    'timeframe' => 'last_7_days'
                ),
                'recent_completions' => array(
                    'title' => 'Recent Lab Completions',
                    'type' => 'data_table',
                    'limit' => 10
                )
            ),
            'settings_sections' => array(
                'general' => array(
                    'title' => 'General Settings',
                    'fields' => array(
                        'plugin_enabled' => array(
                            'type' => 'checkbox',
                            'label' => 'Enable 6Lab Tool',
                            'default' => true
                        ),
                        'default_session_duration' => array(
                            'type' => 'number',
                            'label' => 'Default Session Duration (minutes)',
                            'default' => 120,
                            'min' => 30,
                            'max' => 480
                        ),
                        'max_concurrent_sessions_per_user' => array(
                            'type' => 'number',
                            'label' => 'Max Sessions per User',
                            'default' => 3,
                            'min' => 1,
                            'max' => 10
                        )
                    )
                ),
                'notifications' => array(
                    'title' => 'Notifications',
                    'fields' => array(
                        'email_on_completion' => array(
                            'type' => 'checkbox',
                            'label' => 'Email instructors on lab completion'
                        ),
                        'slack_webhook' => array(
                            'type' => 'text',
                            'label' => 'Slack Webhook URL (optional)'
                        )
                    )
                ),
                'security' => array(
                    'title' => 'Security Settings',
                    'fields' => array(
                        'require_https' => array(
                            'type' => 'checkbox',
                            'label' => 'Require HTTPS for lab access'
                        ),
                        'session_encryption' => array(
                            'type' => 'checkbox',
                            'label' => 'Encrypt session data',
                            'default' => true
                        ),
                        'audit_logging' => array(
                            'type' => 'checkbox',
                            'label' => 'Enable audit logging',
                            'default' => true
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'setup_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_sixlab_get_widget_data', array($this, 'handle_widget_data_request'));
        add_action('wp_ajax_sixlab_dashboard_refresh', array($this, 'handle_dashboard_refresh'));
    }
    
    /**
     * Setup WordPress admin menu
     */
    public function setup_admin_menu() {
        $main_menu = $this->admin_config['menu_structure']['main_menu'];
        
        // Add main menu page
        add_menu_page(
            $main_menu['title'],
            $main_menu['title'],
            $main_menu['capability'],
            'sixlab-dashboard',
            array($this, 'render_dashboard_page'),
            $main_menu['icon'],
            $main_menu['position']
        );
        
        // Add submenu pages
        foreach ($main_menu['submenus'] as $submenu) {
            add_submenu_page(
                'sixlab-dashboard',
                $submenu['title'] . ' - 6Lab Tool',
                $submenu['title'],
                $main_menu['capability'],
                $submenu['slug'],
                array($this, $submenu['callback'])
            );
        }
        
        // Remove duplicate dashboard link
        remove_submenu_page('sixlab-dashboard', 'sixlab-dashboard');
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        foreach ($this->admin_config['settings_sections'] as $section_key => $section) {
            $group_name = 'sixlab_' . $section_key . '_settings';
            
            // Register setting group
            register_setting($group_name, $group_name, array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            ));
            
            // Add settings section
            add_settings_section(
                $section_key,
                $section['title'],
                null,
                $group_name
            );
            
            // Add fields
            foreach ($section['fields'] as $field_key => $field) {
                add_settings_field(
                    $field_key,
                    $field['label'],
                    array($this, 'render_setting_field'),
                    $group_name,
                    $section_key,
                    array(
                        'field_key' => $field_key,
                        'field_config' => $field,
                        'group_name' => $group_name
                    )
                );
            }
        }
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'sixlab') === false) {
            return;
        }
        
        wp_enqueue_style(
            'sixlab-admin-styles',
            plugin_dir_url(__FILE__) . '../admin/assets/css/admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'sixlab-admin-scripts',
            plugin_dir_url(__FILE__) . '../admin/assets/js/admin.js',
            array('jquery', 'wp-api'),
            '1.0.0',
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('sixlab-admin-scripts', 'sixlabAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sixlab_admin'),
            'refreshIntervals' => array(
                'active_sessions' => 30000,
                'provider_health' => 60000,
                'ai_usage' => 300000
            )
        ));
        
        // Load Chart.js for dashboard widgets
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $dashboard_data = $this->get_dashboard_data();
        include plugin_dir_path(__FILE__) . '../admin/views/dashboard.php';
    }
    
    /**
     * Render providers page
     */
    public function render_providers_page() {
        include plugin_dir_path(__FILE__) . '../admin/views/providers.php';
    }
    
    /**
     * Render AI config page
     */
    public function render_ai_config_page() {
        include plugin_dir_path(__FILE__) . '../admin/views/ai-config.php';
    }
    
    /**
     * Render templates page
     */
    public function render_templates_page() {
        include plugin_dir_path(__FILE__) . '../admin/views/templates.php';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        include plugin_dir_path(__FILE__) . '../admin/views/analytics.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings_sections = $this->admin_config['settings_sections'];
        include plugin_dir_path(__FILE__) . '../admin/views/settings.php';
    }
    
    /**
     * Render automation page
     */
    public function render_automation_page() {
        include plugin_dir_path(__FILE__) . '../admin/views/automation.php';
    }
    
    /**
     * Render setting field
     * 
     * @param array $args
     */
    public function render_setting_field($args) {
        $field_key = $args['field_key'];
        $field_config = $args['field_config'];
        $group_name = $args['group_name'];
        
        $options = get_option($group_name, array());
        $value = $options[$field_key] ?? $field_config['default'] ?? '';
        
        $field_name = $group_name . '[' . $field_key . ']';
        $field_id = $group_name . '_' . $field_key;
        
        switch ($field_config['type']) {
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s />',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    checked($value, 1, false)
                );
                break;
                
            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" class="regular-text" />',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($value),
                    esc_attr($field_config['min'] ?? ''),
                    esc_attr($field_config['max'] ?? '')
                );
                break;
                
            case 'text':
            case 'url':
            case 'email':
            default:
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />',
                    esc_attr($field_config['type']),
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($value)
                );
                break;
        }
        
        if (!empty($field_config['description'])) {
            printf('<p class="description">%s</p>', esc_html($field_config['description']));
        }
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        foreach ($input as $key => $value) {
            // Basic sanitization - can be enhanced based on field type
            if (is_numeric($value)) {
                $sanitized[$key] = intval($value);
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                $sanitized[$key] = esc_url_raw($value);
            } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $sanitized[$key] = sanitize_email($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get dashboard data
     * 
     * @return array
     */
    private function get_dashboard_data() {
        global $wpdb;
        
        $data = array(
            'active_sessions' => $this->get_active_sessions_data(),
            'provider_health' => $this->get_provider_health_data(),
            'ai_usage' => $this->get_ai_usage_data(),
            'recent_completions' => $this->get_recent_completions_data()
        );
        
        return $data;
    }
    
    /**
     * Get active sessions data
     * 
     * @return array
     */
    private function get_active_sessions_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_sessions';
        
        $active_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE status = 'active' 
            AND expires_at > NOW()
        ");
        
        // Get hourly data for chart
        $hourly_data = $wpdb->get_results("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as count
            FROM {$table_name} 
            WHERE DATE(created_at) = CURDATE()
            AND status = 'active'
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ");
        
        return array(
            'total' => intval($active_count),
            'chart_data' => $hourly_data
        );
    }
    
    /**
     * Get provider health data
     * 
     * @return array
     */
    private function get_provider_health_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $providers = $wpdb->get_results("
            SELECT name, type, health_status, health_message, last_health_check
            FROM {$table_name}
            WHERE is_active = 1
        ");
        
        return $providers;
    }
    
    /**
     * Get AI usage data
     * 
     * @return array
     */
    private function get_ai_usage_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_ai_interactions';
        
        $usage_data = $wpdb->get_results("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as interactions,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost
            FROM {$table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        
        return $usage_data;
    }
    
    /**
     * Get recent completions data
     * 
     * @return array
     */
    private function get_recent_completions_data() {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'sixlab_sessions';
        $users_table = $wpdb->prefix . 'users';
        
        $completions = $wpdb->get_results("
            SELECT 
                s.id,
                s.lab_id,
                s.provider,
                s.score,
                s.max_score,
                s.completed_at,
                u.display_name
            FROM {$sessions_table} s
            JOIN {$users_table} u ON s.user_id = u.ID
            WHERE s.status = 'completed'
            ORDER BY s.completed_at DESC
            LIMIT 10
        ");
        
        return $completions;
    }
    
    /**
     * Handle widget data AJAX request
     */
    public function handle_widget_data_request() {
        check_ajax_referer('sixlab_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $widget_type = sanitize_key($_POST['widget'] ?? '');
        
        switch ($widget_type) {
            case 'active_sessions':
                $data = $this->get_active_sessions_data();
                break;
            case 'provider_health':
                $data = $this->get_provider_health_data();
                break;
            case 'ai_usage':
                $data = $this->get_ai_usage_data();
                break;
            case 'recent_completions':
                $data = $this->get_recent_completions_data();
                break;
            default:
                wp_send_json_error(array('message' => 'Invalid widget type'));
                return;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Handle dashboard refresh AJAX request
     */
    public function handle_dashboard_refresh() {
        check_ajax_referer('sixlab_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $dashboard_data = $this->get_dashboard_data();
        wp_send_json_success($dashboard_data);
    }
    
    /**
     * Get admin configuration
     * 
     * @return array
     */
    public function get_admin_config() {
        return $this->admin_config;
    }
}

// Initialize admin interface
new SixLab_Admin_Interface();
