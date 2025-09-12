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
                            'title' => 'Lab Sessions',
                            'slug' => 'sixlab-sessions',
                            'callback' => 'render_sessions_page'
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
        add_action('wp_ajax_sixlab_delete_template', array($this, 'handle_delete_template'));
        add_action('wp_ajax_sixlab_toggle_template_status', array($this, 'handle_toggle_template_status'));
        add_action('wp_ajax_sixlab_duplicate_template', array($this, 'handle_duplicate_template'));
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
        // Process form submissions first
        $this->process_template_form();
        
        // Load templates data
        global $wpdb;
        $templates = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}sixlab_lab_templates
            ORDER BY created_at DESC
        ");
        
        include plugin_dir_path(__FILE__) . '../admin/templates/lab-templates.php';
    }
    
    /**
     * Process template form submissions
     */
    private function process_template_form() {
        if (!isset($_POST['submit']) || !wp_verify_nonce($_POST['_wpnonce'], 'sixlab_templates_nonce')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        // Basic template data
        $template_data = array(
            'name' => sanitize_text_field($_POST['template_name']),
            'description' => sanitize_textarea_field($_POST['template_description']),
            'template_type' => sanitize_text_field($_POST['template_type']),
            'provider_type' => sanitize_text_field($_POST['provider_type']),
            'difficulty_level' => sanitize_text_field($_POST['difficulty_level']),
            'estimated_duration' => intval($_POST['estimated_duration'] ?? 0),
            'instructions' => wp_kses_post($_POST['instructions'] ?? ''),
            'tags' => sanitize_text_field($_POST['tags'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        );
        
        // Handle optional date/time fields
        if (!empty($_POST['lab_start_date'])) {
            $template_data['lab_start_date'] = sanitize_text_field($_POST['lab_start_date']);
        }
        if (!empty($_POST['lab_start_time'])) {
            $template_data['lab_start_time'] = sanitize_text_field($_POST['lab_start_time']);
        }
        if (!empty($_POST['lab_end_date'])) {
            $template_data['lab_end_date'] = sanitize_text_field($_POST['lab_end_date']);
        }
        if (!empty($_POST['lab_end_time'])) {
            $template_data['lab_end_time'] = sanitize_text_field($_POST['lab_end_time']);
        }
        
        // Handle learning objectives
        if (!empty($_POST['learning_objectives'])) {
            $objectives = array_map('trim', explode("\n", $_POST['learning_objectives']));
            $objectives = array_filter($objectives);
            $template_data['learning_objectives'] = json_encode($objectives);
        }
        
        // Handle template type specific fields
        if ($template_data['template_type'] === 'guided') {
            // Handle guided steps
            $guided_steps = array();
            if (isset($_POST['guided_steps']) && is_array($_POST['guided_steps'])) {
                foreach ($_POST['guided_steps'] as $step) {
                    if (!empty($step['title'])) {
                        $guided_steps[] = array(
                            'title' => sanitize_text_field($step['title']),
                            'instructions' => wp_kses_post($step['instructions'] ?? ''),
                            'commands' => sanitize_textarea_field($step['commands'] ?? ''),
                            'validation' => sanitize_textarea_field($step['validation'] ?? '')
                        );
                    }
                }
            }
            $template_data['guided_steps'] = json_encode($guided_steps);
            
            if (!empty($_POST['guided_delete_reset_script'])) {
                $template_data['guided_delete_reset_script'] = wp_kses_post($_POST['guided_delete_reset_script']);
            }
            
        } elseif ($template_data['template_type'] === 'non_guided') {
            if (!empty($_POST['instructions_content'])) {
                $template_data['instructions_content'] = wp_kses_post($_POST['instructions_content']);
            }
            if (!empty($_POST['startup_script'])) {
                $template_data['startup_script'] = wp_kses_post($_POST['startup_script']);
            }
            if (!empty($_POST['verification_script'])) {
                $template_data['verification_script'] = wp_kses_post($_POST['verification_script']);
            }
            if (!empty($_POST['delete_reset_script'])) {
                $template_data['delete_reset_script'] = wp_kses_post($_POST['delete_reset_script']);
            }
        }
        
        $is_edit = isset($_POST['template_id']);
        
        if ($is_edit) {
            // Update existing template
            $template_id = intval($_POST['template_id']);
            $template_data['updated_at'] = current_time('mysql');
            
            $result = $wpdb->update(
                $table_name,
                $template_data,
                array('id' => $template_id)
            );
            
            if ($result !== false) {
                add_settings_error('sixlab_templates', 'template_updated',
                    __('Lab template updated successfully.', 'sixlab-tool'), 'success');
            } else {
                add_settings_error('sixlab_templates', 'template_update_error',
                    __('Error updating lab template.', 'sixlab-tool'), 'error');
            }
        } else {
            // Create new template
            $template_data['created_at'] = current_time('mysql');
            $template_data['updated_at'] = current_time('mysql');
            
            $result = $wpdb->insert($table_name, $template_data);
            
            if ($result !== false) {
                add_settings_error('sixlab_templates', 'template_created',
                    __('Lab template created successfully.', 'sixlab-tool'), 'success');
                    
                // Redirect to overview page instead to avoid issues
                wp_redirect(admin_url('admin.php?page=sixlab-templates&tab=overview'));
                exit;
            } else {
                add_settings_error('sixlab_templates', 'template_create_error',
                    __('Error creating lab template: ' . $wpdb->last_error, 'sixlab-tool'), 'error');
            }
        }
    }
    
    /**
     * Render lab sessions page
     */
    public function render_sessions_page() {
        include plugin_dir_path(__FILE__) . '../admin/templates/lab-sessions.php';
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
    
    /**
     * Handle template deletion AJAX request
     */
    public function handle_delete_template() {
        check_ajax_referer('sixlab_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$template_id) {
            wp_send_json_error(array('message' => __('Invalid template ID', 'sixlab-tool')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $template_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Template deleted successfully', 'sixlab-tool')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete template', 'sixlab-tool')));
        }
    }
    
    /**
     * Handle template status toggle AJAX request
     */
    public function handle_toggle_template_status() {
        check_ajax_referer('sixlab_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!$template_id || !in_array($status, array('activate', 'deactivate'))) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'sixlab-tool')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        $is_active = $status === 'activate' ? 1 : 0;
        
        $result = $wpdb->update(
            $table_name,
            array('is_active' => $is_active),
            array('id' => $template_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            $message = $status === 'activate' ?
                __('Template activated successfully', 'sixlab-tool') :
                __('Template deactivated successfully', 'sixlab-tool');
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to update template status', 'sixlab-tool')));
        }
    }
    
    /**
     * Handle template duplication AJAX request
     */
    public function handle_duplicate_template() {
        check_ajax_referer('sixlab_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$template_id) {
            wp_send_json_error(array('message' => __('Invalid template ID', 'sixlab-tool')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        // Get the original template
        $original_template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $template_id
        ), ARRAY_A);
        
        if (!$original_template) {
            wp_send_json_error(array('message' => __('Template not found', 'sixlab-tool')));
        }
        
        // Prepare data for duplication
        unset($original_template['id']);
        $original_template['name'] .= ' (Copy)';
        $original_template['is_active'] = 0; // New copy should be inactive by default
        $original_template['created_at'] = current_time('mysql');
        $original_template['updated_at'] = current_time('mysql');
        
        $result = $wpdb->insert($table_name, $original_template);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Template duplicated successfully', 'sixlab-tool'),
                'new_template_id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to duplicate template', 'sixlab-tool')));
        }
    }
}

// Initialize admin interface
new SixLab_Admin_Interface();
