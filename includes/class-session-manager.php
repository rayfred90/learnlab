<?php
/**
 * Session Manager Class
 * 
 * Handles lab session lifecycle management
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SixLab Session Manager Class
 */
class SixLab_Session_Manager {
    
    /**
     * Default session timeout in hours
     * @var int
     */
    const DEFAULT_SESSION_TIMEOUT = 4;
    
    /**
     * Provider factory instance
     * @var SixLab_Provider_Factory
     */
    private $provider_factory;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->provider_factory = new SixLab_Provider_Factory();
    }
    
    /**
     * Create a new lab session
     * 
     * @param int $user_id User ID
     * @param int $lab_id Lab template ID
     * @param string $provider_type Provider type (optional)
     * @param array $session_options Additional session options
     * @return array|WP_Error Session data or error
     */
    public function create_session($user_id, $lab_id, $provider_type = null, $session_options = array()) {
        global $wpdb;
        
        // Validate user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Invalid user ID', 'sixlab-tool'));
        }
        
        // Validate lab template from database table
        $templates_table = $wpdb->prefix . 'sixlab_lab_templates';
        $lab_template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$templates_table} WHERE id = %d AND is_active = 1",
            $lab_id
        ));
        
        if (!$lab_template) {
            return new WP_Error('invalid_lab', __('Invalid lab template ID', 'sixlab-tool'));
        }
        
        // Check if user has permission to access this lab
        if (!$this->user_can_access_lab($user_id, $lab_id)) {
            return new WP_Error('access_denied', __('You do not have permission to access this lab', 'sixlab-tool'));
        }
        
        // Check for existing active session
        $existing_session = $this->get_active_user_session($user_id, $lab_id);
        if ($existing_session) {
            return new WP_Error('session_exists', __('You already have an active session for this lab', 'sixlab-tool'));
        }
        
        // Get provider
        if ($provider_type) {
            $provider = $this->provider_factory->get_provider_by_type($provider_type);
        } else {
            $provider = $this->provider_factory->get_default_provider();
        }
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        // Get lab template data from the database table
        $template_data = array();
        if (!empty($lab_template->template_data)) {
            $template_data = json_decode($lab_template->template_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('invalid_template_json', __('Lab template data is invalid JSON', 'sixlab-tool'));
            }
        }
        
        // Create provider session
        $provider_session = $provider->create_session($user_id, $template_data, $session_options);
        
        if (is_wp_error($provider_session)) {
            return $provider_session;
        }
        
        // Calculate session expiry
        $timeout_hours = isset($session_options['timeout_hours']) ? 
            intval($session_options['timeout_hours']) : 
            self::DEFAULT_SESSION_TIMEOUT;
        
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$timeout_hours} hours"));
        
        // Get total steps from template
        $total_steps = isset($template_data['steps']) ? count($template_data['steps']) : 1;
        
        // Create session record
        $session_data = array(
            'user_id' => $user_id,
            'lab_id' => $lab_id,
            'provider' => $provider->get_type(),
            'provider_session_id' => $provider_session['session_id'],
            'session_data' => wp_json_encode($provider_session),
            'ai_context' => wp_json_encode(array()),
            'current_step' => 1,
            'total_steps' => $total_steps,
            'status' => 'active',
            'score' => 0.00,
            'max_score' => isset($template_data['max_score']) ? floatval($template_data['max_score']) : 100.00,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'expires_at' => $expires_at
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'sixlab_sessions',
            $session_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            // Cleanup provider session if database insert failed
            $provider->destroy_session($provider_session['session_id']);
            return new WP_Error('session_create_failed', __('Failed to create session record', 'sixlab-tool'));
        }
        
        $session_id = $wpdb->insert_id;
        
        // Log session creation
        $this->log_session_event($session_id, 'session_created', array(
            'provider' => $provider->get_type(),
            'lab_id' => $lab_id,
            'timeout_hours' => $timeout_hours
        ));
        
        // Return session data
        $session_data['id'] = $session_id;
        $session_data['provider_session'] = $provider_session;
        
        return $session_data;
    }
    
    /**
     * Start a new lab session (alias for create_session with simplified parameters)
     * 
     * @param int $user_id User ID
     * @param int $template_id Lab template ID
     * @param string $provider_type Provider type
     * @return string|WP_Error Session ID or error
     */
    public function start_session($user_id, $template_id, $provider_type = null) {
        $result = $this->create_session($user_id, $template_id, $provider_type);
        
        if (is_wp_error($result)) {
            // Throw exception to be caught by the AJAX handler
            throw new Exception($result->get_error_message());
        }
        
        return $result['id'];
    }

    /**
     * Get session by ID
     * 
     * @param int $session_id Session ID
     * @return array|null Session data
     */
    public function get_session($session_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_sessions WHERE id = %d",
            $session_id
        ), ARRAY_A);
    }
    
    /**
     * Get active session for user and lab
     * 
     * @param int $user_id User ID
     * @param int $lab_id Lab ID
     * @return array|null Session data
     */
    public function get_active_user_session($user_id, $lab_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_sessions 
             WHERE user_id = %d AND lab_id = %d AND status = 'active' 
             ORDER BY created_at DESC LIMIT 1",
            $user_id, $lab_id
        ), ARRAY_A);
    }
    
    /**
     * Get user sessions for a lesson
     * 
     * @param int $user_id User ID
     * @param int $lesson_id Lesson ID
     * @return array Sessions
     */
    public function get_user_sessions_for_lesson($user_id, $lesson_id) {
        global $wpdb;
        
        // Get lab templates associated with this lesson
        $lab_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = 'sixlab_lesson_id' AND meta_value = %d",
            $lesson_id
        ));
        
        if (empty($lab_ids)) {
            return array();
        }
        
        $placeholders = implode(',', array_fill(0, count($lab_ids), '%d'));
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_sessions 
             WHERE user_id = %d AND lab_id IN ({$placeholders}) 
             ORDER BY created_at DESC",
            array_merge(array($user_id), $lab_ids)
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Update session step
     * 
     * @param int $session_id Session ID
     * @param int $step New step number
     * @return bool|WP_Error
     */
    public function update_session_step($session_id, $step) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        if ($session['status'] !== 'active') {
            return new WP_Error('session_not_active', __('Session is not active', 'sixlab-tool'));
        }
        
        if ($step < 1 || $step > $session['total_steps']) {
            return new WP_Error('invalid_step', __('Invalid step number', 'sixlab-tool'));
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'current_step' => $step,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('step_update_failed', __('Failed to update session step', 'sixlab-tool'));
        }
        
        $this->log_session_event($session_id, 'step_updated', array('step' => $step));
        
        return true;
    }
    
    /**
     * Validate a session step
     * 
     * @param int $session_id Session ID
     * @param int $step Step number
     * @param array $validation_data Data to validate
     * @return array|WP_Error Validation results
     */
    public function validate_step($session_id, $step, $validation_data) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        if ($session['status'] !== 'active') {
            return new WP_Error('session_not_active', __('Session is not active', 'sixlab-tool'));
        }
        
        // Get provider for validation
        $provider = $this->provider_factory->get_provider_by_type($session['provider']);
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        // Get lab template and step configuration
        $lab_template = get_post($session['lab_id']);
        $template_data = json_decode(get_post_meta($session['lab_id'], 'sixlab_template_data', true), true);
        
        if (!isset($template_data['steps'][$step - 1])) {
            return new WP_Error('step_not_found', __('Step configuration not found', 'sixlab-tool'));
        }
        
        $step_config = $template_data['steps'][$step - 1];
        
        // Perform provider-specific validation
        $provider_session_data = json_decode($session['session_data'], true);
        $validation_result = $provider->validate_step(
            $provider_session_data['session_id'],
            $step_config,
            $validation_data
        );
        
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Save validation result
        $validation_record = array(
            'session_id' => $session_id,
            'step' => $step,
            'validation_type' => $step_config['validation_type'] ?? 'configuration',
            'validation_data' => wp_json_encode($validation_data),
            'expected_result' => wp_json_encode($step_config['expected_result'] ?? array()),
            'actual_result' => wp_json_encode($validation_result['actual_result'] ?? array()),
            'score' => floatval($validation_result['score']),
            'max_score' => floatval($step_config['max_score'] ?? 10.0),
            'passed' => $validation_result['passed'] ? 1 : 0,
            'feedback' => $validation_result['feedback'] ?? '',
            'validation_time_ms' => intval($validation_result['validation_time_ms'] ?? 0),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'sixlab_validations',
            $validation_record,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%d', '%s')
        );
        
        // Update session score
        $this->update_session_score($session_id);
        
        // If step passed and it's the current step, advance to next step
        if ($validation_result['passed'] && $step == $session['current_step']) {
            if ($step < $session['total_steps']) {
                $this->update_session_step($session_id, $step + 1);
            } else {
                // Lab completed
                $this->complete_session($session_id);
            }
        }
        
        $this->log_session_event($session_id, 'step_validated', array(
            'step' => $step,
            'passed' => $validation_result['passed'],
            'score' => $validation_result['score']
        ));
        
        return $validation_result;
    }
    
    /**
     * Update session total score
     * 
     * @param int $session_id Session ID
     * @return bool
     */
    private function update_session_score($session_id) {
        global $wpdb;
        
        $total_score = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(score) FROM {$wpdb->prefix}sixlab_validations WHERE session_id = %d",
            $session_id
        ));
        
        return $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'score' => floatval($total_score),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%f', '%s'),
            array('%d')
        ) !== false;
    }
    
    /**
     * Complete a session
     * 
     * @param int $session_id Session ID
     * @return bool|WP_Error
     */
    public function complete_session($session_id) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        if ($session['status'] === 'completed') {
            return true; // Already completed
        }
        
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
        
        if ($result === false) {
            return new WP_Error('completion_failed', __('Failed to complete session', 'sixlab-tool'));
        }
        
        // Clean up provider session
        $provider = $this->provider_factory->get_provider_by_type($session['provider']);
        if (!is_wp_error($provider)) {
            $provider_session_data = json_decode($session['session_data'], true);
            $provider->destroy_session($provider_session_data['session_id']);
        }
        
        // Handle LearnDash integration
        $this->handle_learndash_completion($session);
        
        $this->log_session_event($session_id, 'session_completed', array(
            'final_score' => $session['score'],
            'max_score' => $session['max_score']
        ));
        
        return true;
    }
    
    /**
     * End a session (user-initiated)
     * 
     * @param int $session_id Session ID
     * @return bool|WP_Error
     */
    public function end_session($session_id) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        if ($session['status'] !== 'active') {
            return true; // Already ended
        }
        
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
        
        if ($result === false) {
            return new WP_Error('end_session_failed', __('Failed to end session', 'sixlab-tool'));
        }
        
        // Clean up provider session
        $provider = $this->provider_factory->get_provider_by_type($session['provider']);
        if (!is_wp_error($provider)) {
            $provider_session_data = json_decode($session['session_data'], true);
            $provider->destroy_session($provider_session_data['session_id']);
        }
        
        $this->log_session_event($session_id, 'session_ended', array('reason' => 'user_initiated'));
        
        return true;
    }
    
    /**
     * Pause a session
     * 
     * @param int $session_id Session ID
     * @return bool|WP_Error
     */
    public function pause_session($session_id) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        if ($session['status'] !== 'active') {
            return new WP_Error('session_not_active', __('Session is not active', 'sixlab-tool'));
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'status' => 'paused',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('pause_failed', __('Failed to pause session', 'sixlab-tool'));
        }
        
        $this->log_session_event($session_id, 'session_paused');
        
        return true;
    }
    
    /**
     * Resume a paused session
     * 
     * @param int $session_id Session ID
     * @return bool|WP_Error
     */
    public function resume_session($session_id) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        if ($session['status'] !== 'paused') {
            return new WP_Error('session_not_paused', __('Session is not paused', 'sixlab-tool'));
        }
        
        // Check if session has expired
        if (strtotime($session['expires_at']) < time()) {
            $this->end_session($session_id);
            return new WP_Error('session_expired', __('Session has expired', 'sixlab-tool'));
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'status' => 'active',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('resume_failed', __('Failed to resume session', 'sixlab-tool'));
        }
        
        $this->log_session_event($session_id, 'session_resumed');
        
        return true;
    }
    
    /**
     * Cleanup expired sessions
     * 
     * @return int Number of sessions cleaned up
     */
    public static function cleanup_expired_sessions() {
        global $wpdb;
        
        // Get expired sessions
        $expired_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, provider, session_data FROM {$wpdb->prefix}sixlab_sessions 
             WHERE status = 'active' AND expires_at < %s",
            current_time('mysql')
        ));
        
        $cleanup_count = 0;
        
        foreach ($expired_sessions as $session) {
            // Update status to expired
            $wpdb->update(
                $wpdb->prefix . 'sixlab_sessions',
                array('status' => 'expired', 'updated_at' => current_time('mysql')),
                array('id' => $session->id),
                array('%s', '%s'),
                array('%d')
            );
            
            // Clean up provider session
            $provider_factory = new SixLab_Provider_Factory();
            $provider = $provider_factory->get_provider_by_type($session->provider);
            
            if (!is_wp_error($provider)) {
                $session_data = json_decode($session->session_data, true);
                if (isset($session_data['session_id'])) {
                    $provider->destroy_session($session_data['session_id']);
                }
            }
            
            $cleanup_count++;
        }
        
        return $cleanup_count;
    }
    
    /**
     * Check if user can access lab
     * 
     * @param int $user_id User ID
     * @param int $lab_id Lab ID
     * @return bool
     */
    private function user_can_access_lab($user_id, $lab_id) {
        // Check if user has general access
        if (user_can($user_id, 'edit_posts')) {
            return true;
        }
        
        // Check LearnDash enrollment
        if (function_exists('sfwd_lms_has_access')) {
            return sfwd_lms_has_access($lab_id, $user_id);
        }
        
        // Default access check
        return true;
    }
    
    /**
     * Handle LearnDash completion integration
     * 
     * @param array $session Session data
     */
    private function handle_learndash_completion($session) {
        // Get associated lesson ID
        $lesson_id = get_post_meta($session['lab_id'], 'sixlab_lesson_id', true);
        
        if (!$lesson_id) {
            return;
        }
        
        // Calculate completion percentage
        $completion_percentage = ($session['score'] / $session['max_score']) * 100;
        
        // Mark lesson as completed if score is above threshold
        $completion_threshold = get_option('sixlab_completion_threshold', 70);
        
        if ($completion_percentage >= $completion_threshold) {
            if (function_exists('learndash_process_mark_complete')) {
                learndash_process_mark_complete($session['user_id'], $lesson_id);
            }
        }
        
        // Store score in user meta
        update_user_meta(
            $session['user_id'], 
            "sixlab_score_{$session['lab_id']}", 
            $session['score']
        );
    }
    
    /**
     * Log session event
     * 
     * @param int $session_id Session ID
     * @param string $event_type Event type
     * @param array $event_data Event data
     */
    private function log_session_event($session_id, $event_type, $event_data = array()) {
        global $wpdb;
        
        $session = $this->get_session($session_id);
        if (!$session) {
            return;
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'sixlab_analytics',
            array(
                'session_id' => $session_id,
                'user_id' => $session['user_id'],
                'event_type' => $event_type,
                'event_category' => 'session',
                'event_data' => wp_json_encode($event_data),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get session statistics
     * 
     * @param array $filters Optional filters
     * @return array Statistics
     */
    public function get_session_statistics($filters = array()) {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        if (isset($filters['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $filters['user_id'];
        }
        
        if (isset($filters['lab_id'])) {
            $where_conditions[] = 'lab_id = %d';
            $where_values[] = $filters['lab_id'];
        }
        
        if (isset($filters['provider'])) {
            $where_conditions[] = 'provider = %s';
            $where_values[] = $filters['provider'];
        }
        
        if (isset($filters['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT 
                    status,
                    COUNT(*) as session_count,
                    AVG(score) as avg_score,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(completed_at, updated_at))) as avg_duration_minutes
                  FROM {$wpdb->prefix}sixlab_sessions 
                  {$where_clause} 
                  GROUP BY status";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, ...$where_values);
        }
        
        return $wpdb->get_results($query);
    }
}
