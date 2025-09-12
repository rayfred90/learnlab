<?php
/**
 * Core plugin functionality
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main SixLab Core Class
 */
class SixLab_Core {
    
    /**
     * Single instance of the class
     * @var SixLab_Core
     */
    private static $instance = null;
    
    /**
     * Provider factory instance
     * @var SixLab_Provider_Factory
     */
    public $provider_factory;
    
    /**
     * AI factory instance  
     * @var SixLab_AI_Factory
     */
    public $ai_factory;
    
    /**
     * Session manager instance
     * @var SixLab_Session_Manager
     */
    public $session_manager;
    
    /**
     * Admin instance
     * @var SixLab_Admin
     */
    public $admin;
    
    /**
     * Public instance
     * @var SixLab_Public
     */
    public $public;
    
    /**
     * Get single instance of the class
     * 
     * @return SixLab_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once SIXLAB_PLUGIN_DIR . 'includes/class-provider-factory.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/class-ai-factory.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/class-session-manager.php';
        require_once SIXLAB_PLUGIN_DIR . 'database/class-sixlab-database.php';
        
        // Automation classes
        require_once SIXLAB_PLUGIN_DIR . 'includes/class-automation-manager.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/automation-ajax.php';
        
        // Provider classes
        require_once SIXLAB_PLUGIN_DIR . 'includes/providers/abstract-lab-provider.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/providers/class-gns3-provider.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/providers/class-guacamole-provider.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/providers/class-eveng-provider.php';
        
        // AI provider classes
        require_once SIXLAB_PLUGIN_DIR . 'includes/ai/abstract-ai-provider.php';
        require_once SIXLAB_PLUGIN_DIR . 'includes/ai-providers/class-openrouter-provider.php';
        
        // Admin classes
        if (is_admin()) {
            require_once SIXLAB_PLUGIN_DIR . 'includes/class-sixlab-admin.php';
        }
        
        // Public classes
        require_once SIXLAB_PLUGIN_DIR . 'includes/class-sixlab-public.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // WordPress init hook
        add_action('init', array($this, 'init'));
        
        // REST API hooks
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // AJAX hooks
        add_action('wp_ajax_sixlab_validate_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_sixlab_ai_chat', array($this, 'ajax_ai_chat'));
        add_action('wp_ajax_sixlab_end_session', array($this, 'ajax_end_session'));
        
        // LearnDash integration hooks
        add_action('learndash_lesson_completed', array($this, 'handle_lesson_completion'), 10, 1);
        add_action('learndash_quiz_completed', array($this, 'handle_quiz_completion'), 10, 2);
        
        // Custom post types registration
        add_action('init', array($this, 'register_post_types'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Shortcode support
        add_action('init', array($this, 'register_shortcodes'));
        
        // Custom rewrite rules for lab workspace
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Template redirect for custom pages
        add_action('template_redirect', array($this, 'template_redirect'));
        
        // Query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Initialize core components
     */
    private function init_components() {
        // Initialize factories
        $this->provider_factory = new SixLab_Provider_Factory();
        $this->ai_factory = new SixLab_AI_Factory();
        $this->session_manager = new SixLab_Session_Manager();
        
        // Initialize admin interface
        if (is_admin()) {
            $this->admin = new SixLab_Admin();
        }
        
        // Initialize public interface
        $this->public = new SixLab_Public();
    }
    
    /**
     * WordPress init callback
     */
    public function init() {
        // Load plugin textdomain
        load_plugin_textdomain('sixlab-tool', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Add user capabilities
        $this->add_custom_capabilities();
        
        // Maybe upgrade database
        $this->maybe_upgrade_database();
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Lab Templates post type
        register_post_type('sixlab_template', array(
            'label' => __('Lab Templates', 'sixlab-tool'),
            'labels' => array(
                'name' => __('Lab Templates', 'sixlab-tool'),
                'singular_name' => __('Lab Template', 'sixlab-tool'),
                'menu_name' => __('Lab Templates', 'sixlab-tool'),
                'add_new' => __('Add New Template', 'sixlab-tool'),
                'add_new_item' => __('Add New Lab Template', 'sixlab-tool'),
                'edit_item' => __('Edit Lab Template', 'sixlab-tool'),
                'new_item' => __('New Lab Template', 'sixlab-tool'),
                'view_item' => __('View Lab Template', 'sixlab-tool'),
                'search_items' => __('Search Lab Templates', 'sixlab-tool'),
                'not_found' => __('No lab templates found', 'sixlab-tool'),
                'not_found_in_trash' => __('No lab templates found in trash', 'sixlab-tool'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'sixlab-admin',
            'capability_type' => 'sixlab_template',
            'map_meta_cap' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false,
        ));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('sixlab_workspace', array($this->public, 'render_workspace_shortcode'));
        add_shortcode('sixlab_dashboard', array($this->public, 'render_dashboard_shortcode'));
        add_shortcode('sixlab_template', array($this->public, 'render_template_shortcode'));
        add_shortcode('sixlab_progress', array($this->public, 'render_progress_shortcode'));
        
        // Legacy shortcode support
        add_shortcode('sixlab_interface', array($this->public, 'render_workspace_shortcode'));
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        if ($this->is_sixlab_page()) {
            wp_enqueue_script(
                'sixlab-public',
                SIXLAB_PLUGIN_URL . 'public/js/sixlab-public.js',
                array('jquery'),
                SIXLAB_PLUGIN_VERSION,
                true
            );
            
            wp_enqueue_style(
                'sixlab-public',
                SIXLAB_PLUGIN_URL . 'public/css/sixlab-public.css',
                array(),
                SIXLAB_PLUGIN_VERSION
            );
            
            // Localize script with AJAX URL and nonce
            wp_localize_script('sixlab-public', 'sixlab_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('sixlab/v1/'),
                'nonce' => wp_create_nonce('sixlab_nonce'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'sixlab') !== false) {
            wp_enqueue_script(
                'sixlab-admin',
                SIXLAB_PLUGIN_URL . 'admin/assets/js/sixlab-admin.js',
                array('jquery'),
                SIXLAB_PLUGIN_VERSION,
                true
            );
            
            wp_enqueue_style(
                'sixlab-admin',
                SIXLAB_PLUGIN_URL . 'admin/assets/css/sixlab-admin.css',
                array(),
                SIXLAB_PLUGIN_VERSION
            );
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('sixlab/v1', '/sessions', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_create_session'),
            'permission_callback' => array($this, 'check_session_permissions')
        ));
        
        register_rest_route('sixlab/v1', '/sessions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_session'),
            'permission_callback' => array($this, 'check_session_permissions')
        ));
        
        register_rest_route('sixlab/v1', '/templates', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_templates'),
            'permission_callback' => array($this, 'check_session_permissions')
        ));
    }
    
    /**
     * Add custom capabilities
     */
    private function add_custom_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_sixlab');
            $role->add_cap('edit_sixlab_templates');
            $role->add_cap('delete_sixlab_templates');
        }
        
        $instructor_role = get_role('group_leader'); // LearnDash instructor role
        if ($instructor_role) {
            $instructor_role->add_cap('edit_sixlab_templates');
        }
    }
    
    /**
     * Check if current page is a SixLab page
     */
    private function is_sixlab_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if post content contains SixLab shortcodes
        return has_shortcode($post->post_content, 'sixlab_interface') || 
               has_shortcode($post->post_content, 'sixlab_progress') ||
               has_shortcode($post->post_content, 'sixlab_workspace') ||
               has_shortcode($post->post_content, 'sixlab_dashboard') ||
               has_shortcode($post->post_content, 'sixlab_template');
    }
    
    /**
     * Maybe upgrade database
     */
    private function maybe_upgrade_database() {
        $current_version = get_option('sixlab_version');
        
        if (version_compare($current_version, SIXLAB_PLUGIN_VERSION, '<')) {
            SixLab_Database::maybe_upgrade($current_version);
            update_option('sixlab_version', SIXLAB_PLUGIN_VERSION);
        }
    }
    
    /**
     * AJAX: Validate step
     */
    public function ajax_validate_step() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id']);
        $step = intval($_POST['step']);
        $validation_data = wp_unslash($_POST['validation_data']);
        
        $result = $this->session_manager->validate_step($session_id, $step, $validation_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: AI Chat
     */
    public function ajax_ai_chat() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $context_type = sanitize_text_field($_POST['context_type']);
        
        $response = $this->ai_factory->get_response($session_id, $message, $context_type);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: End session
     */
    public function ajax_end_session() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id']);
        
        $result = $this->session_manager->end_session($session_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Handle LearnDash lesson completion
     */
    public function handle_lesson_completion($data) {
        // Integration with LearnDash lesson completion
        // Could trigger automatic lab session start or validation
    }

    /**
     * Handle LearnDash quiz completion
     */
    public function handle_quiz_completion($data, $user) {
        // Integration with LearnDash quiz completion
        // Could trigger final lab validations or grade passback
    }
    
    /**
     * REST: Create session
     */
    public function rest_create_session($request) {
        $lab_id = $request->get_param('lab_id');
        $provider = $request->get_param('provider');
        
        $session = $this->session_manager->create_session(get_current_user_id(), $lab_id, $provider);
        
        if (is_wp_error($session)) {
            return new WP_Error('session_creation_failed', $session->get_error_message(), array('status' => 400));
        }
        
        return rest_ensure_response($session);
    }
    
    /**
     * Check session permissions for REST API
     */
    public function check_session_permissions($request) {
        return is_user_logged_in();
    }
    
    /**
     * Check admin permissions for REST API
     */
    public function check_admin_permissions($request) {
        return current_user_can('manage_sixlab');
    }
    
    /**
     * Add rewrite rules for custom pages
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^sixlab-workspace/?$',
            'index.php?sixlab_page=workspace',
            'top'
        );
        
        add_rewrite_rule(
            '^sixlab-preview/?',
            'index.php?sixlab_page=preview',
            'top'
        );
        
        add_rewrite_rule(
            '^sixlab-progress/?$',
            'index.php?sixlab_page=progress',
            'top'
        );
        
        // Flush rewrite rules if needed
        if (get_option('sixlab_rewrite_rules_flushed') !== SIXLAB_PLUGIN_VERSION) {
            flush_rewrite_rules();
            update_option('sixlab_rewrite_rules_flushed', SIXLAB_PLUGIN_VERSION);
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'sixlab_page';
        $vars[] = 'session';
        $vars[] = 'template';
        return $vars;
    }
    
    /**
     * Template redirect for custom pages
     */
    public function template_redirect() {
        $sixlab_page = get_query_var('sixlab_page');
        
        if (!$sixlab_page) {
            return;
        }
        
        switch ($sixlab_page) {
            case 'workspace':
                $this->load_workspace_template();
                break;
            case 'preview':
                $this->load_preview_template();
                break;
            case 'progress':
                $this->load_progress_template();
                break;
        }
    }
    
    /**
     * Load workspace template
     */
    private function load_workspace_template() {
        $session_id = get_query_var('session');
        
        // Fallback to $_GET if query var is not working
        if (!$session_id && isset($_GET['session'])) {
            $session_id = sanitize_text_field($_GET['session']);
        }
        
        if (!$session_id) {
            wp_die(__('No session specified.', 'sixlab-tool'));
        }
        
        include SIXLAB_PLUGIN_DIR . 'public/templates/enhanced-lab-interface.php';
        exit;
    }
    
    /**
     * Load preview template
     */
    private function load_preview_template() {
        $template_id = get_query_var('template');
        
        // Fallback to $_GET if query var is not working
        if (!$template_id && isset($_GET['template'])) {
            $template_id = sanitize_text_field($_GET['template']);
        }
        
        if (!$template_id) {
            wp_die(__('No template specified.', 'sixlab-tool'));
        }
        
        // Create a minimal preview interface
        include SIXLAB_PLUGIN_DIR . 'public/templates/preview-interface.php';
        exit;
    }
    
    /**
     * Load progress template
     */
    private function load_progress_template() {
        // Create a dedicated progress page
        include SIXLAB_PLUGIN_DIR . 'public/templates/standalone-progress.php';
        exit;
    }
}
