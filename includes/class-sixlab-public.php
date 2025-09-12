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
        add_shortcode('sixlab_template', array($this, 'render_template_shortcode'));
        add_shortcode('sixlab_progress', array($this, 'render_progress_shortcode'));
        
        // Legacy shortcode support
        add_shortcode('sixlab_interface', array($this, 'render_workspace_shortcode'));
        
        // AJAX hooks for non-admin users
        add_action('wp_ajax_nopriv_sixlab_start_session', array($this, 'ajax_start_session'));
        add_action('wp_ajax_nopriv_sixlab_validate_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_nopriv_sixlab_ai_chat', array($this, 'ajax_ai_chat'));
        add_action('wp_ajax_nopriv_sixlab_end_session', array($this, 'ajax_end_session'));
        add_action('wp_ajax_nopriv_sixlab_get_templates', array($this, 'ajax_get_templates'));
        
        // Authenticated AJAX hooks
        add_action('wp_ajax_sixlab_start_session', array($this, 'ajax_start_session'));
        add_action('wp_ajax_sixlab_validate_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_sixlab_ai_chat', array($this, 'ajax_ai_chat'));
        add_action('wp_ajax_sixlab_end_session', array($this, 'ajax_end_session'));
        add_action('wp_ajax_sixlab_get_templates', array($this, 'ajax_get_templates'));
        add_action('wp_ajax_sixlab_save_progress', array($this, 'ajax_save_progress'));
        add_action('wp_ajax_sixlab_get_lab_preview', array($this, 'ajax_get_lab_preview'));
        
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
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'site_url' => home_url()
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
            'width' => '100%',
            'enhanced' => 'true'
        ), $atts, 'sixlab_workspace');
        
        // Check if we should use enhanced interface
        if ($atts['enhanced'] === 'true' && !empty($_GET['session'])) {
            ob_start();
            include SIXLAB_PLUGIN_DIR . 'public/templates/enhanced-lab-interface.php';
            return ob_get_clean();
        }
        
        // Fallback to original interface
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
     * Render template shortcode - for specific lab templates
     */
    public function render_template_shortcode($atts) {
        $atts = shortcode_atts(array(
            'template_id' => '',
            'id' => '', // For backward compatibility
            'slug' => '',
            'show_preview' => 'false',
            'preview_text' => 'Preview Lab',
            'start_text' => 'Start Lab',
            'height' => '600px'
        ), $atts, 'sixlab_template');
        
        // Support both 'id' and 'template_id' attributes
        $template_id = !empty($atts['template_id']) ? $atts['template_id'] : $atts['id'];
        
        // Get template data
        $template = $this->get_template_by_id_or_slug($template_id, $atts['slug']);
        
        if (!$template) {
            return '<div class="sixlab-error">Lab template not found.</div>';
        }
        
        ob_start();
        include SIXLAB_PLUGIN_DIR . 'public/templates/template-interface.php';
        return ob_get_clean();
    }
    
    /**
     * Render progress shortcode - enhanced for WordPress pages
     */
    public function render_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_templates' => 'true',
            'show_user_progress' => 'true',
            'show_leaderboard' => 'false',
            'template_filter' => '',
            'difficulty' => '',
            'view' => 'full' // 'full', 'compact', 'templates-only'
        ), $atts, 'sixlab_progress');
        
        // Check if on LearnDash lesson
        $is_learndash = function_exists('learndash_get_post_type_slug') && 
                       is_singular(learndash_get_post_type_slug('lesson'));
        
        ob_start();
        include SIXLAB_PLUGIN_DIR . 'public/templates/progress-interface.php';
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
     * AJAX handler for getting lab templates
     */
    public function ajax_get_templates() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $difficulty = sanitize_text_field($_POST['difficulty'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        try {
            $templates = $this->get_available_templates($difficulty, $search, $provider);
            wp_send_json_success($templates);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get template by ID or slug
     */
    private function get_template_by_id_or_slug($template_id, $slug) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        if (!empty($template_id)) {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d AND is_active = 1",
                $template_id
            ));
        } elseif (!empty($slug)) {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE slug = %s AND is_active = 1",
                $slug
            ));
        } else {
            return null;
        }
        
        return $template;
    }
    
    /**
     * Get available lab templates
     */
    private function get_available_templates($difficulty = '', $search = '', $provider = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        $where_clauses = array('is_active = 1');
        $params = array();
        
        if (!empty($difficulty)) {
            $where_clauses[] = 'difficulty_level = %s';
            $params[] = $difficulty;
        }
        
        if (!empty($search)) {
            $where_clauses[] = '(name LIKE %s OR description LIKE %s OR tags LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($provider)) {
            $where_clauses[] = 'provider_type = %s';
            $params[] = $provider;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY is_featured DESC, usage_count DESC, name ASC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * AJAX handler for saving progress
     */
    public function ajax_save_progress() {
        check_ajax_referer('sixlab_enhanced_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $current_step = intval($_POST['current_step']);
        $progress_data = $_POST['progress_data']; // JSON data
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        try {
            global $wpdb;
            $sessions_table = $wpdb->prefix . 'sixlab_sessions';
            
            $result = $wpdb->update(
                $sessions_table,
                array(
                    'current_step' => $current_step,
                    'progress_data' => $progress_data,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'id' => $session_id,
                    'user_id' => $user_id
                ),
                array('%d', '%s', '%s'),
                array('%s', '%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Progress saved successfully'));
            } else {
                wp_send_json_error('Failed to save progress');
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for getting lab preview
     */
    public function ajax_get_lab_preview() {
        check_ajax_referer('sixlab_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id']);
        
        if (!$template_id) {
            wp_send_json_error('Invalid template ID');
            return;
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sixlab_lab_templates';
            
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d AND is_active = 1",
                $template_id
            ));
            
            if (!$template) {
                wp_send_json_error('Template not found');
                return;
            }
            
            // Generate preview HTML
            ob_start();
            ?>
            <div class="sixlab-preview-content">
                <div class="preview-header">
                    <h3><?php echo esc_html($template->name); ?></h3>
                    <div class="preview-meta">
                        <span class="difficulty-badge difficulty-<?php echo esc_attr($template->difficulty_level); ?>">
                            <?php echo esc_html(ucfirst($template->difficulty_level)); ?>
                        </span>
                        <span class="provider-badge">
                            <?php echo esc_html(ucfirst($template->provider_type)); ?>
                        </span>
                    </div>
                </div>
                
                <div class="preview-description">
                    <?php echo wp_kses_post(wpautop($template->description)); ?>
                </div>
                
                <?php if (!empty($template->instructions)): ?>
                    <div class="preview-instructions">
                        <h4>Lab Instructions</h4>
                        <div class="instructions-content">
                            <?php echo wp_kses_post(wpautop($template->instructions)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($template->learning_objectives)): ?>
                    <div class="preview-objectives">
                        <h4>Learning Objectives</h4>
                        <ul>
                            <?php
                            $objectives = explode("\n", $template->learning_objectives);
                            foreach ($objectives as $objective) {
                                $objective = trim($objective);
                                if (!empty($objective)) {
                                    echo '<li>' . esc_html($objective) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="preview-footer">
                    <div class="preview-stats">
                        <?php if ($template->estimated_duration): ?>
                            <span><i class="fas fa-clock"></i> <?php echo esc_html($template->estimated_duration); ?> minutes</span>
                        <?php endif; ?>
                        <span><i class="fas fa-users"></i> <?php echo esc_html($template->usage_count); ?> completions</span>
                    </div>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
            
            wp_send_json_success(array('html' => $html));
            
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
