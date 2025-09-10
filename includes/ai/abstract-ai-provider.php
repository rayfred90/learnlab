<?php
/**
 * Abstract AI Provider Class
 * 
 * Base class that all AI providers must extend
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract AI Provider Class
 */
abstract class SixLab_AI_Provider_Abstract {
    
    /**
     * AI provider configuration
     * @var array
     */
    protected $config = array();
    
    /**
     * Prompt templates
     * @var array
     */
    protected $prompt_templates = array();
    
    /**
     * Rate limiting counters
     * @var array
     */
    protected $rate_limits = array();
    
    /**
     * Constructor
     * 
     * @param array $config AI provider configuration
     */
    public function __construct($config = array()) {
        $this->config = wp_parse_args($config, $this->get_default_config());
        $this->prompt_templates = $this->get_default_prompts();
        $this->init();
    }
    
    /**
     * Initialize AI provider
     * Override in child classes for provider-specific initialization
     */
    protected function init() {
        // Override in child classes
    }
    
    /**
     * Get AI provider type identifier
     * 
     * @return string Provider type
     */
    abstract public function get_type();
    
    /**
     * Get AI provider display name
     * 
     * @return string Display name
     */
    abstract public function get_display_name();
    
    /**
     * Get AI provider description
     * 
     * @return string Description
     */
    abstract public function get_description();
    
    /**
     * Get default configuration
     * 
     * @return array Default configuration
     */
    abstract public function get_default_config();
    
    /**
     * Get configuration fields for admin interface
     * 
     * @return array Configuration fields
     */
    abstract public function get_config_fields();
    
    /**
     * Get default prompt templates
     * 
     * @return array Default prompt templates
     */
    abstract public function get_default_prompts();
    
    /**
     * Test AI provider connection and authentication
     * 
     * @return array Test results with 'success' and 'message' keys
     */
    abstract public function test_connection();
    
    /**
     * Get contextual help for lab step
     * 
     * @param array $context Context data including lab info, step, user data
     * @return array|WP_Error AI response or error
     */
    abstract public function get_contextual_help($context);
    
    /**
     * Analyze configuration and provide feedback
     * 
     * @param array $context Context data including configuration and expected results
     * @return array|WP_Error Analysis results or error
     */
    abstract public function analyze_configuration($context);
    
    /**
     * Explain error and suggest fixes
     * 
     * @param array $context Context data including error message and configuration
     * @return array|WP_Error Error explanation or error
     */
    abstract public function explain_error($context);
    
    /**
     * Generate progressive hints for lab step
     * 
     * @param array $context Context data including step, attempts, previous hints
     * @return array|WP_Error Generated hints or error
     */
    abstract public function generate_hints($context);
    
    /**
     * Handle chat conversation
     * 
     * @param array $context Context data including message and conversation history
     * @return array|WP_Error Chat response or error
     */
    abstract public function chat_response($context);
    
    /**
     * Get supported features
     * 
     * @return array Supported features
     */
    public function get_supported_features() {
        return array(
            'contextual_help',
            'configuration_analysis',
            'error_explanation',
            'hint_generation',
            'chat_conversation'
        );
    }
    
    /**
     * Check if provider supports feature
     * 
     * @param string $feature Feature name
     * @return bool Whether feature is supported
     */
    public function supports_feature($feature) {
        return in_array($feature, $this->get_supported_features());
    }
    
    /**
     * Build prompt from template and context
     * 
     * @param string $template_name Template name
     * @param array $context Context variables
     * @return string Formatted prompt
     */
    protected function build_prompt($template_name, $context) {
        if (!isset($this->prompt_templates[$template_name])) {
            return '';
        }
        
        $template = $this->prompt_templates[$template_name];
        
        // Replace variables in template
        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{' . $key . '}', $value, $template);
            }
        }
        
        return $template;
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    protected function get_config($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    protected function set_config($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Check rate limits
     * 
     * @param string $limit_type Limit type (minute, hour, day)
     * @return bool|WP_Error True if within limits, error if exceeded
     */
    protected function check_rate_limits($limit_type = 'minute') {
        $limits = array(
            'minute' => $this->get_config('rate_limit_per_minute', 20),
            'hour' => $this->get_config('rate_limit_per_hour', 100),
            'day' => $this->get_config('rate_limit_per_day', 500)
        );
        
        if (!isset($limits[$limit_type])) {
            return true;
        }
        
        $limit = $limits[$limit_type];
        $cache_key = 'sixlab_ai_rate_limit_' . $this->get_type() . '_' . $limit_type;
        $current_count = get_transient($cache_key);
        
        if ($current_count === false) {
            $current_count = 0;
        }
        
        if ($current_count >= $limit) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __('Rate limit exceeded for %s. Limit: %d requests per %s', 'sixlab-tool'),
                    $this->get_display_name(),
                    $limit,
                    $limit_type
                )
            );
        }
        
        return true;
    }
    
    /**
     * Increment rate limit counter
     * 
     * @param string $limit_type Limit type (minute, hour, day)
     */
    protected function increment_rate_limit($limit_type = 'minute') {
        $cache_key = 'sixlab_ai_rate_limit_' . $this->get_type() . '_' . $limit_type;
        $current_count = get_transient($cache_key);
        
        if ($current_count === false) {
            $current_count = 0;
        }
        
        $expiration = array(
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400
        );
        
        set_transient($cache_key, $current_count + 1, $expiration[$limit_type]);
    }
    
    /**
     * Calculate token usage cost
     * 
     * @param int $input_tokens Input tokens used
     * @param int $output_tokens Output tokens used
     * @return float Cost in USD
     */
    protected function calculate_cost($input_tokens, $output_tokens) {
        $input_rate = $this->get_config('input_token_rate', 0.0);
        $output_rate = $this->get_config('output_token_rate', 0.0);
        
        return ($input_tokens * $input_rate / 1000) + ($output_tokens * $output_rate / 1000);
    }
    
    /**
     * Sanitize context data
     * 
     * @param array $context Context data
     * @return array Sanitized context
     */
    protected function sanitize_context($context) {
        $sanitized = array();
        
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize_context($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Filter inappropriate content
     * 
     * @param string $content Content to filter
     * @return string Filtered content
     */
    protected function filter_content($content) {
        // Basic profanity filter
        if ($this->get_config('enable_profanity_filter', true)) {
            $profanity_list = apply_filters('sixlab_ai_profanity_list', array());
            foreach ($profanity_list as $word) {
                $content = str_ireplace($word, str_repeat('*', strlen($word)), $content);
            }
        }
        
        // Remove potential harmful instructions
        $harmful_patterns = array(
            '/ignore\s+previous\s+instructions/i',
            '/forget\s+everything/i',
            '/act\s+as\s+if/i'
        );
        
        foreach ($harmful_patterns as $pattern) {
            $content = preg_replace($pattern, '[FILTERED]', $content);
        }
        
        return $content;
    }
    
    /**
     * Log AI interaction
     * 
     * @param string $interaction_type Type of interaction
     * @param array $request_data Request data
     * @param array $response_data Response data
     * @param int $tokens_used Tokens used
     * @param float $cost_usd Cost in USD
     * @param int $response_time_ms Response time in milliseconds
     */
    protected function log_interaction($interaction_type, $request_data, $response_data, $tokens_used = 0, $cost_usd = 0.0, $response_time_ms = 0) {
        do_action('sixlab_ai_interaction_logged', array(
            'provider' => $this->get_type(),
            'interaction_type' => $interaction_type,
            'request_data' => $request_data,
            'response_data' => $response_data,
            'tokens_used' => $tokens_used,
            'cost_usd' => $cost_usd,
            'response_time_ms' => $response_time_ms,
            'timestamp' => current_time('mysql')
        ));
        
        // Track usage statistics
        $this->update_usage_stats($tokens_used, $cost_usd);
    }
    
    /**
     * Update usage statistics
     * 
     * @param int $tokens_used Tokens used
     * @param float $cost_usd Cost in USD
     */
    private function update_usage_stats($tokens_used, $cost_usd) {
        $stats_key = 'sixlab_ai_usage_' . $this->get_type();
        $stats = get_option($stats_key, array(
            'total_tokens' => 0,
            'total_cost' => 0.0,
            'total_requests' => 0,
            'last_reset' => current_time('mysql')
        ));
        
        $stats['total_tokens'] += $tokens_used;
        $stats['total_cost'] += $cost_usd;
        $stats['total_requests'] += 1;
        
        // Reset monthly if needed
        $last_reset = strtotime($stats['last_reset']);
        $current_month = date('Y-m');
        $reset_month = date('Y-m', $last_reset);
        
        if ($current_month !== $reset_month) {
            $stats = array(
                'total_tokens' => $tokens_used,
                'total_cost' => $cost_usd,
                'total_requests' => 1,
                'last_reset' => current_time('mysql')
            );
        }
        
        update_option($stats_key, $stats);
        
        // Check budget limits
        $monthly_budget = get_option('sixlab_ai_monthly_budget', 100.0);
        if ($stats['total_cost'] >= $monthly_budget) {
            do_action('sixlab_ai_budget_exceeded', $this->get_type(), $stats['total_cost'], $monthly_budget);
        }
    }
    
    /**
     * Get usage statistics
     * 
     * @return array Usage statistics
     */
    public function get_usage_stats() {
        $stats_key = 'sixlab_ai_usage_' . $this->get_type();
        return get_option($stats_key, array(
            'total_tokens' => 0,
            'total_cost' => 0.0,
            'total_requests' => 0,
            'last_reset' => current_time('mysql')
        ));
    }
    
    /**
     * Validate configuration
     * 
     * @param array $config Configuration to validate
     * @return bool|WP_Error True if valid, error if invalid
     */
    public function validate_config($config) {
        $fields = $this->get_config_fields();
        
        foreach ($fields as $field_name => $field_config) {
            if (isset($field_config['required']) && $field_config['required']) {
                if (!isset($config[$field_name]) || empty($config[$field_name])) {
                    return new WP_Error(
                        'required_field_missing',
                        sprintf(__('Required field missing: %s', 'sixlab-tool'), $field_config['label'])
                    );
                }
            }
            
            if (isset($config[$field_name]) && isset($field_config['validation'])) {
                $validation_result = $this->validate_field($config[$field_name], $field_config['validation']);
                if (is_wp_error($validation_result)) {
                    return $validation_result;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate individual field
     * 
     * @param mixed $value Field value
     * @param string $validation Validation rules
     * @return bool|WP_Error True if valid, error if invalid
     */
    private function validate_field($value, $validation) {
        $rules = explode('|', $validation);
        
        foreach ($rules as $rule) {
            if ($rule === 'required' && empty($value)) {
                return new WP_Error('validation_failed', __('Field is required', 'sixlab-tool'));
            }
            
            if (strpos($rule, 'min:') === 0) {
                $min_length = intval(substr($rule, 4));
                if (strlen($value) < $min_length) {
                    return new WP_Error(
                        'validation_failed',
                        sprintf(__('Field must be at least %d characters', 'sixlab-tool'), $min_length)
                    );
                }
            }
        }
        
        return true;
    }
    
    /**
     * Make HTTP request to AI provider API
     * 
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array|WP_Error Response or error
     */
    protected function make_api_request($url, $args = array()) {
        // Check rate limits first
        $rate_check = $this->check_rate_limits('minute');
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        $start_time = microtime(true);
        
        $default_args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'SixLab-Tool/' . SIXLAB_PLUGIN_VERSION
            )
        );
        
        $args = wp_parse_args($args, $default_args);
        
        $response = wp_remote_request($url, $args);
        
        $response_time = (microtime(true) - $start_time) * 1000; // Convert to milliseconds
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Increment rate limit counter
        $this->increment_rate_limit('minute');
        $this->increment_rate_limit('hour');
        $this->increment_rate_limit('day');
        
        if ($status_code >= 400) {
            return new WP_Error(
                'api_request_failed',
                sprintf(__('API request failed with status %d: %s', 'sixlab-tool'), $status_code, $body)
            );
        }
        
        return array(
            'status_code' => $status_code,
            'body' => $body,
            'headers' => wp_remote_retrieve_headers($response),
            'response_time_ms' => $response_time
        );
    }
    
    /**
     * Get provider icon URL
     * 
     * @return string Icon URL
     */
    public function get_icon_url() {
        $icon_file = $this->get_type() . '-ai-icon.svg';
        $icon_path = SIXLAB_PLUGIN_DIR . 'assets/images/' . $icon_file;
        
        if (file_exists($icon_path)) {
            return SIXLAB_PLUGIN_URL . 'assets/images/' . $icon_file;
        }
        
        // Fallback to default AI icon
        return SIXLAB_PLUGIN_URL . 'assets/images/default-ai-icon.svg';
    }
}
