<?php
/**
 * AI Factory Class
 * 
 * Handles creation and management of AI providers
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SixLab AI Factory Class
 */
class SixLab_AI_Factory {
    
    /**
     * Available AI provider types
     * @var array
     */
    private static $ai_provider_types = array(
        'openrouter' => 'SixLab_OpenRouter_Provider'
    );
    
    /**
     * Cached AI provider instances
     * @var array
     */
    private $ai_instances = array();
    
    /**
     * Current active AI provider
     * @var SixLab_AI_Provider_Abstract
     */
    private $active_provider = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'initialize_default_provider'));
    }
    
    /**
     * Initialize default AI provider
     */
    public function initialize_default_provider() {
        $active_provider_type = get_option('sixlab_ai_provider', 'openrouter');
        
        if ($active_provider_type && isset(self::$ai_provider_types[$active_provider_type])) {
            $this->active_provider = $this->create_ai_provider($active_provider_type);
        }
    }
    
    /**
     * Create an AI provider instance
     * 
     * @param string $provider_type AI provider type (openrouter)
     * @param array $config Provider configuration (optional)
     * @return SixLab_AI_Provider_Abstract|WP_Error
     */
    public function create_ai_provider($provider_type, $config = array()) {
        if (!isset(self::$ai_provider_types[$provider_type])) {
            return new WP_Error(
                'invalid_ai_provider_type',
                sprintf(__('Invalid AI provider type: %s', 'sixlab-tool'), $provider_type)
            );
        }
        
        $provider_class = self::$ai_provider_types[$provider_type];
        
        if (!class_exists($provider_class)) {
            return new WP_Error(
                'ai_provider_class_not_found',
                sprintf(__('AI provider class not found: %s', 'sixlab-tool'), $provider_class)
            );
        }
        
        // Use saved config if not provided
        if (empty($config)) {
            $config = get_option("sixlab_ai_{$provider_type}_config", array());
        }
        
        try {
            $provider = new $provider_class($config);
            
            if (!($provider instanceof SixLab_AI_Provider_Abstract)) {
                return new WP_Error(
                    'invalid_ai_provider_instance',
                    __('AI provider must extend SixLab_AI_Provider_Abstract', 'sixlab-tool')
                );
            }
            
            return $provider;
            
        } catch (Exception $e) {
            return new WP_Error(
                'ai_provider_creation_failed',
                sprintf(__('Failed to create AI provider: %s', 'sixlab-tool'), $e->getMessage())
            );
        }
    }
    
    /**
     * Get the active AI provider
     * 
     * @return SixLab_AI_Provider_Abstract|WP_Error
     */
    public function get_active_provider() {
        if (!$this->active_provider) {
            return new WP_Error(
                'no_active_ai_provider',
                __('No active AI provider configured', 'sixlab-tool')
            );
        }
        
        return $this->active_provider;
    }
    
    /**
     * Set active AI provider
     * 
     * @param string $provider_type Provider type
     * @param array $config Provider configuration
     * @return bool|WP_Error
     */
    public function set_active_provider($provider_type, $config = array()) {
        $provider = $this->create_ai_provider($provider_type, $config);
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        $this->active_provider = $provider;
        update_option('sixlab_ai_provider', $provider_type);
        
        if (!empty($config)) {
            update_option("sixlab_ai_{$provider_type}_config", $config);
        }
        
        return true;
    }
    
    /**
     * Get contextual help from AI
     * 
     * @param int $session_id Lab session ID
     * @param string $context_type Type of help needed
     * @param array $additional_context Additional context data
     * @return array|WP_Error AI response data
     */
    public function get_contextual_help($session_id, $context_type = 'general', $additional_context = array()) {
        $provider = $this->get_active_provider();
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        // Get session context
        $session_context = $this->get_session_context($session_id);
        
        if (is_wp_error($session_context)) {
            return $session_context;
        }
        
        // Merge contexts
        $full_context = array_merge($session_context, $additional_context, array(
            'context_type' => $context_type,
            'session_id' => $session_id
        ));
        
        $response = $provider->get_contextual_help($full_context);
        
        if (!is_wp_error($response)) {
            $this->log_ai_interaction($session_id, 'contextual_help', $full_context, $response, $provider->get_type());
        }
        
        return $response;
    }
    
    /**
     * Analyze configuration with AI
     * 
     * @param int $session_id Lab session ID
     * @param string $configuration Configuration to analyze
     * @param string $expected_outcome Expected configuration outcome
     * @return array|WP_Error AI analysis results
     */
    public function analyze_configuration($session_id, $configuration, $expected_outcome = '') {
        $provider = $this->get_active_provider();
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        $session_context = $this->get_session_context($session_id);
        
        if (is_wp_error($session_context)) {
            return $session_context;
        }
        
        $analysis_context = array_merge($session_context, array(
            'configuration' => $configuration,
            'expected_outcome' => $expected_outcome,
            'context_type' => 'configuration_analysis'
        ));
        
        $response = $provider->analyze_configuration($analysis_context);
        
        if (!is_wp_error($response)) {
            $this->log_ai_interaction($session_id, 'configuration_analysis', $analysis_context, $response, $provider->get_type());
        }
        
        return $response;
    }
    
    /**
     * Get AI chat response
     * 
     * @param int $session_id Lab session ID
     * @param string $message User message
     * @param array $conversation_history Previous conversation
     * @return array|WP_Error AI response
     */
    public function get_response($session_id, $message, $context_type = 'chat', $conversation_history = array()) {
        $provider = $this->get_active_provider();
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        $session_context = $this->get_session_context($session_id);
        
        if (is_wp_error($session_context)) {
            return $session_context;
        }
        
        // Get recent conversation history if not provided
        if (empty($conversation_history)) {
            $conversation_history = $this->get_conversation_history($session_id, 10);
        }
        
        $chat_context = array_merge($session_context, array(
            'message' => $message,
            'conversation_history' => $conversation_history,
            'context_type' => $context_type
        ));
        
        $response = $provider->chat_response($chat_context);
        
        if (!is_wp_error($response)) {
            $this->log_ai_interaction($session_id, $context_type, $chat_context, $response, $provider->get_type());
        }
        
        return $response;
    }
    
    /**
     * Explain error with AI
     * 
     * @param int $session_id Lab session ID
     * @param string $error_message Error message
     * @param string $user_config User's configuration when error occurred
     * @return array|WP_Error AI explanation
     */
    public function explain_error($session_id, $error_message, $user_config = '') {
        $provider = $this->get_active_provider();
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        $session_context = $this->get_session_context($session_id);
        
        if (is_wp_error($session_context)) {
            return $session_context;
        }
        
        $error_context = array_merge($session_context, array(
            'error_message' => $error_message,
            'user_config' => $user_config,
            'context_type' => 'error_explanation'
        ));
        
        $response = $provider->explain_error($error_context);
        
        if (!is_wp_error($response)) {
            $this->log_ai_interaction($session_id, 'error_explanation', $error_context, $response, $provider->get_type());
        }
        
        return $response;
    }
    
    /**
     * Generate progressive hints
     * 
     * @param int $session_id Lab session ID
     * @param int $step Current step
     * @param int $attempt_count Number of attempts
     * @return array|WP_Error AI-generated hints
     */
    public function generate_hints($session_id, $step, $attempt_count = 1) {
        $provider = $this->get_active_provider();
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        $session_context = $this->get_session_context($session_id);
        
        if (is_wp_error($session_context)) {
            return $session_context;
        }
        
        // Get previous hints for this step
        $previous_hints = $this->get_previous_hints($session_id, $step);
        
        $hint_context = array_merge($session_context, array(
            'current_step' => $step,
            'attempt_count' => $attempt_count,
            'previous_hints' => $previous_hints,
            'context_type' => 'hint_generation'
        ));
        
        $response = $provider->generate_hints($hint_context);
        
        if (!is_wp_error($response)) {
            $this->log_ai_interaction($session_id, 'hint_request', $hint_context, $response, $provider->get_type());
        }
        
        return $response;
    }
    
    /**
     * Get session context for AI
     * 
     * @param int $session_id Session ID
     * @return array|WP_Error Session context data
     */
    private function get_session_context($session_id) {
        global $wpdb;
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.user_login, u.display_name 
             FROM {$wpdb->prefix}sixlab_sessions s 
             JOIN {$wpdb->users} u ON s.user_id = u.ID 
             WHERE s.id = %d",
            $session_id
        ));
        
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'sixlab-tool'));
        }
        
        // Get lab template data
        $lab_template = get_post($session->lab_id);
        
        if (!$lab_template) {
            return new WP_Error('lab_template_not_found', __('Lab template not found', 'sixlab-tool'));
        }
        
        $session_data = json_decode($session->session_data, true) ?: array();
        $ai_context = json_decode($session->ai_context, true) ?: array();
        
        return array(
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'user_login' => $session->user_login,
            'user_display_name' => $session->display_name,
            'lab_id' => $session->lab_id,
            'lab_name' => $lab_template->post_title,
            'lab_description' => $lab_template->post_content,
            'provider' => $session->provider,
            'current_step' => $session->current_step,
            'total_steps' => $session->total_steps,
            'session_data' => $session_data,
            'ai_context' => $ai_context,
            'status' => $session->status,
            'created_at' => $session->created_at
        );
    }
    
    /**
     * Get conversation history
     * 
     * @param int $session_id Session ID
     * @param int $limit Number of messages to retrieve
     * @return array Conversation history
     */
    private function get_conversation_history($session_id, $limit = 10) {
        global $wpdb;
        
        $interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT interaction_type, request_data, response_data, created_at 
             FROM {$wpdb->prefix}sixlab_ai_interactions 
             WHERE session_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $session_id, $limit
        ));
        
        $history = array();
        
        foreach (array_reverse($interactions) as $interaction) {
            $request_data = json_decode($interaction->request_data, true);
            $response_data = json_decode($interaction->response_data, true);
            
            if (isset($request_data['message'])) {
                $history[] = array(
                    'role' => 'user',
                    'content' => $request_data['message'],
                    'timestamp' => $interaction->created_at
                );
            }
            
            if (isset($response_data['message'])) {
                $history[] = array(
                    'role' => 'assistant',
                    'content' => $response_data['message'],
                    'timestamp' => $interaction->created_at
                );
            }
        }
        
        return $history;
    }
    
    /**
     * Get previous hints for a step
     * 
     * @param int $session_id Session ID
     * @param int $step Step number
     * @return array Previous hints
     */
    private function get_previous_hints($session_id, $step) {
        global $wpdb;
        
        $hints = $wpdb->get_results($wpdb->prepare(
            "SELECT response_data FROM {$wpdb->prefix}sixlab_ai_interactions 
             WHERE session_id = %d 
             AND interaction_type = 'hint_request' 
             AND JSON_EXTRACT(request_data, '$.current_step') = %d 
             ORDER BY created_at ASC",
            $session_id, $step
        ));
        
        $previous_hints = array();
        
        foreach ($hints as $hint) {
            $response_data = json_decode($hint->response_data, true);
            if (isset($response_data['hints'])) {
                $previous_hints = array_merge($previous_hints, $response_data['hints']);
            }
        }
        
        return $previous_hints;
    }
    
    /**
     * Log AI interaction
     * 
     * @param int $session_id Session ID
     * @param string $interaction_type Type of interaction
     * @param array $request_data Request data
     * @param array $response_data Response data
     * @param string $ai_provider AI provider used
     * @return bool
     */
    private function log_ai_interaction($session_id, $interaction_type, $request_data, $response_data, $ai_provider) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        
        return $wpdb->insert(
            $wpdb->prefix . 'sixlab_ai_interactions',
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'ai_provider' => $ai_provider,
                'interaction_type' => $interaction_type,
                'request_data' => wp_json_encode($request_data),
                'response_data' => wp_json_encode($response_data),
                'tokens_used' => isset($response_data['tokens_used']) ? $response_data['tokens_used'] : 0,
                'response_time_ms' => isset($response_data['response_time_ms']) ? $response_data['response_time_ms'] : null,
                'cost_usd' => isset($response_data['cost_usd']) ? $response_data['cost_usd'] : null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s')
        ) !== false;
    }
    
    /**
     * Register a new AI provider type
     * 
     * @param string $type Provider type identifier
     * @param string $class_name Provider class name
     * @return bool
     */
    public static function register_ai_provider_type($type, $class_name) {
        if (isset(self::$ai_provider_types[$type])) {
            return false; // Type already registered
        }
        
        self::$ai_provider_types[$type] = $class_name;
        return true;
    }
    
    /**
     * Get registered AI provider types
     * 
     * @return array
     */
    public static function get_ai_provider_types() {
        return self::$ai_provider_types;
    }
    
    /**
     * Get AI usage statistics
     * 
     * @param int $session_id Optional session ID to filter by
     * @param string $date_from Optional start date
     * @param string $date_to Optional end date
     * @return array Usage statistics
     */
    public function get_usage_statistics($session_id = null, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        if ($session_id) {
            $where_conditions[] = 'session_id = %d';
            $where_values[] = $session_id;
        }
        
        if ($date_from) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $date_from;
        }
        
        if ($date_to) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT 
                    ai_provider,
                    interaction_type,
                    COUNT(*) as interaction_count,
                    SUM(tokens_used) as total_tokens,
                    SUM(cost_usd) as total_cost,
                    AVG(response_time_ms) as avg_response_time
                  FROM {$wpdb->prefix}sixlab_ai_interactions 
                  {$where_clause} 
                  GROUP BY ai_provider, interaction_type";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, ...$where_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Update AI context for session
     * 
     * @param int $session_id Session ID
     * @param array $ai_context AI context data
     * @return bool
     */
    public function update_session_ai_context($session_id, $ai_context) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'ai_context' => wp_json_encode($ai_context),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        ) !== false;
    }
    
    /**
     * Get available AI provider types
     * 
     * @return array Array of AI provider types and their class names
     */
    public function get_available_providers() {
        return self::$ai_provider_types;
    }
}
