<?php
/**
 * Public Interface Class
 * 
 * WordPress public/frontend interface for 6Lab Tool
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public Interface Class
 */
class SixLab_Public {
    
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
     * Instance of the session manager
     * @var SixLab_Session_Manager
     */
    private $session_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->provider_factory = new SixLab_Provider_Factory();
        $this->ai_factory = new SixLab_AI_Factory();
        $this->session_manager = new SixLab_Session_Manager();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Enqueue public scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        
        // Shortcode registration
        add_shortcode('sixlab_workspace', array($this, 'render_workspace_shortcode'));
        add_shortcode('sixlab_dashboard', array($this, 'render_dashboard_shortcode'));
        
        // AJAX hooks for non-admin users
        add_action('wp_ajax_nopriv_sixlab_start_session', array($this, 'ajax_start_session'));
        add_action('wp_ajax_nopriv_sixlab_validate_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_nopriv_sixlab_ai_chat', array($this, 'ajax_ai_chat'));
        add_action('wp_ajax_nopriv_sixlab_end_session', array($this, 'ajax_end_session'));
        
        // REST API hooks
        add_action('rest_api_init', array($this, 'register_public_rest_routes'));
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_public_scripts() {
        // Only enqueue on pages that need it
        if (!$this->should_enqueue_scripts()) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'sixlab-frontend-styles',
            SIXLAB_PLUGIN_URL . 'public/css/frontend-styles.css',
            array(),
            SIXLAB_PLUGIN_VERSION
        );
        
        wp_enqueue_style(
            'sixlab-dashboard-styles',
            SIXLAB_PLUGIN_URL . 'public/css/dashboard-styles.css',
            array(),
            SIXLAB_PLUGIN_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'sixlab-frontend-interface',
            SIXLAB_PLUGIN_URL . 'public/js/frontend-interface.js',
            array('jquery'),
            SIXLAB_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_script(
            'sixlab-dashboard',
            SIXLAB_PLUGIN_URL . 'public/js/dashboard.js',
            array('jquery'),
            SIXLAB_PLUGIN_VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('sixlab-frontend-interface', 'sixlab_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sixlab_nonce'),
            'rest_url' => rest_url('sixlab/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    /**
     * Check if scripts should be enqueued
     */
    private function should_enqueue_scripts() {
        global $post;
        
        // Check if shortcodes are present
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'sixlab_workspace') ||
            has_shortcode($post->post_content, 'sixlab_dashboard')
        )) {
            return true;
        }
        
        // Check if it's a LearnDash lesson/course page
        if (function_exists('learndash_get_post_type_slug')) {
            $lesson_post_type = learndash_get_post_type_slug('lesson');
            $course_post_type = learndash_get_post_type_slug('course');
            
            if (is_singular($lesson_post_type) || is_singular($course_post_type)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Render workspace shortcode
     */
    public function render_workspace_shortcode($atts) {
        $atts = shortcode_atts(array(
            'template_id' => '',
            'provider' => '',
            'height' => '600px',
            'width' => '100%'
        ), $atts, 'sixlab_workspace');
        
        ob_start();
        include SIXLAB_PLUGIN_DIR . 'public/templates/lab-interface.php';
        return ob_get_clean();
    }
    
    /**
     * Render dashboard shortcode
     */
    public function render_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_stats' => 'true',
            'show_history' => 'true'
        ), $atts, 'sixlab_dashboard');
        
        ob_start();
        include SIXLAB_PLUGIN_DIR . 'public/templates/dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for starting a session
     */
    public function ajax_start_session() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $template_id = sanitize_text_field($_POST['template_id']);
        $provider = sanitize_text_field($_POST['provider']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        try {
            $session_id = $this->session_manager->start_session($user_id, $template_id, $provider);
            wp_send_json_success(array('session_id' => $session_id));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for validating a step
     */
    public function ajax_validate_step() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $step_data = $_POST['step_data']; // This might contain complex data
        
        if (!get_current_user_id()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        try {
            $result = $this->session_manager->validate_step($session_id, $step_data);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for AI chat
     */
    public function ajax_ai_chat() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id']);
        $context = $_POST['context']; // Lab context data
        
        if (!get_current_user_id()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        try {
            $response = $this->ai_factory->get_chat_response($message, $context, $session_id);
            wp_send_json_success(array('response' => $response));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for ending a session
     */
    public function ajax_end_session() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        try {
            $result = $this->session_manager->end_session($session_id, $user_id);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Register public REST API routes
     */
    public function register_public_rest_routes() {
        register_rest_route('sixlab/v1', '/sessions', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_create_session'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
        
        register_rest_route('sixlab/v1', '/sessions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_session'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
        
        register_rest_route('sixlab/v1', '/sessions/(?P<id>\d+)/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_validate_step'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
    }
    
    /**
     * REST API permission check
     */
    public function rest_permission_check() {
        return is_user_logged_in();
    }
    
    /**
     * REST API: Create session
     */
    public function rest_create_session($request) {
        $template_id = $request->get_param('template_id');
        $provider = $request->get_param('provider');
        $user_id = get_current_user_id();
        
        try {
            $session_id = $this->session_manager->start_session($user_id, $template_id, $provider);
            return rest_ensure_response(array('session_id' => $session_id));
        } catch (Exception $e) {
            return new WP_Error('session_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * REST API: Get session
     */
    public function rest_get_session($request) {
        $session_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        try {
            $session = $this->session_manager->get_session($session_id, $user_id);
            return rest_ensure_response($session);
        } catch (Exception $e) {
            return new WP_Error('session_error', $e->getMessage(), array('status' => 404));
        }
    }
    
    /**
     * REST API: Validate step
     */
    public function rest_validate_step($request) {
        $session_id = $request->get_param('id');
        $step_data = $request->get_param('step_data');
        
        try {
            $result = $this->session_manager->validate_step($session_id, $step_data);
            return rest_ensure_response($result);
        } catch (Exception $e) {
            return new WP_Error('validation_error', $e->getMessage(), array('status' => 500));
        }
    }
}
