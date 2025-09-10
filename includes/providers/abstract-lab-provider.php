<?php
/**
 * Abstract Lab Provider Class
 * 
 * Base class that all lab providers must extend
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Lab Provider Class
 */
abstract class SixLab_Lab_Provider_Abstract {
    
    /**
     * Provider configuration
     * @var array
     */
    protected $config = array();
    
    /**
     * Provider ID (from database)
     * @var int
     */
    protected $provider_id;
    
    /**
     * Provider name
     * @var string
     */
    protected $name;
    
    /**
     * Provider display name
     * @var string
     */
    protected $display_name;
    
    /**
     * Supported features
     * @var array
     */
    protected $supported_features = array();
    
    /**
     * Constructor
     * 
     * @param array $config Provider configuration
     */
    public function __construct($config = array()) {
        $this->config = wp_parse_args($config, $this->get_default_config());
        $this->init();
    }
    
    /**
     * Initialize provider
     * Override in child classes for provider-specific initialization
     */
    protected function init() {
        // Override in child classes
    }
    
    /**
     * Get provider type identifier
     * 
     * @return string Provider type
     */
    abstract public function get_type();
    
    /**
     * Get provider display name
     * 
     * @return string Display name
     */
    abstract public function get_display_name();
    
    /**
     * Get provider description
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
     * Get supported features
     * 
     * @return array Supported features
     */
    public function get_supported_features() {
        return $this->supported_features;
    }
    
    /**
     * Test provider connection
     * 
     * @return array Test results with 'success' and 'message' keys
     */
    abstract public function test_connection();
    
    /**
     * Create a new lab session
     * 
     * @param int $user_id User ID
     * @param array $template_data Lab template data
     * @param array $options Session options
     * @return array|WP_Error Session data or error
     */
    abstract public function create_session($user_id, $template_data, $options = array());
    
    /**
     * Get session details
     * 
     * @param string $session_id Provider session ID
     * @return array|WP_Error Session details or error
     */
    abstract public function get_session($session_id);
    
    /**
     * Update session configuration
     * 
     * @param string $session_id Provider session ID
     * @param array $config_data Configuration updates
     * @return bool|WP_Error Success or error
     */
    abstract public function update_session($session_id, $config_data);
    
    /**
     * Validate a lab step
     * 
     * @param string $session_id Provider session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Data to validate
     * @return array|WP_Error Validation results or error
     */
    abstract public function validate_step($session_id, $step_config, $validation_data);
    
    /**
     * Destroy a lab session
     * 
     * @param string $session_id Provider session ID
     * @return bool|WP_Error Success or error
     */
    abstract public function destroy_session($session_id);
    
    /**
     * Get session access URL
     * 
     * @param string $session_id Provider session ID
     * @param int $user_id User ID
     * @return string|WP_Error Access URL or error
     */
    abstract public function get_session_url($session_id, $user_id);
    
    /**
     * Check if provider supports feature
     * 
     * @param string $feature Feature name
     * @return bool Whether feature is supported
     */
    public function supports_feature($feature) {
        return in_array($feature, $this->supported_features);
    }
    
    /**
     * Get provider health status
     * 
     * @return array Health status with 'status' and 'message' keys
     */
    public function get_health_status() {
        $test_result = $this->test_connection();
        
        return array(
            'status' => $test_result['success'] ? 'healthy' : 'error',
            'message' => $test_result['message'],
            'timestamp' => current_time('mysql')
        );
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
            
            if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                return new WP_Error('validation_failed', __('Field must be a valid URL', 'sixlab-tool'));
            }
            
            if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return new WP_Error('validation_failed', __('Field must be a valid email', 'sixlab-tool'));
            }
        }
        
        return true;
    }
    
    /**
     * Log provider activity
     * 
     * @param string $action Action performed
     * @param array $data Additional data
     * @param string $level Log level (info, warning, error)
     */
    protected function log($action, $data = array(), $level = 'info') {
        $log_entry = array(
            'provider' => $this->get_type(),
            'action' => $action,
            'data' => $data,
            'level' => $level,
            'timestamp' => current_time('mysql')
        );
        
        do_action('sixlab_provider_log', $log_entry);
        
        // Also log to WordPress debug log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(
                sprintf(
                    '[SixLab Provider %s] %s: %s',
                    $this->get_type(),
                    $action,
                    wp_json_encode($data)
                )
            );
        }
    }
    
    /**
     * Make HTTP request with error handling
     * 
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array|WP_Error Response or error
     */
    protected function make_request($url, $args = array()) {
        $default_args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'SixLab-Tool/' . SIXLAB_PLUGIN_VERSION
            )
        );
        
        $args = wp_parse_args($args, $default_args);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log('http_request_failed', array(
                'url' => $url,
                'error' => $response->get_error_message()
            ), 'error');
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code >= 400) {
            $error_message = sprintf(
                __('HTTP request failed with status %d: %s', 'sixlab-tool'),
                $status_code,
                $body
            );
            
            $this->log('http_request_error', array(
                'url' => $url,
                'status_code' => $status_code,
                'response_body' => $body
            ), 'error');
            
            return new WP_Error('http_request_failed', $error_message);
        }
        
        return array(
            'status_code' => $status_code,
            'body' => $body,
            'headers' => wp_remote_retrieve_headers($response)
        );
    }
    
    /**
     * Setters for provider metadata
     */
    public function set_id($id) {
        $this->provider_id = $id;
    }
    
    public function set_name($name) {
        $this->name = $name;
    }
    
    public function set_display_name($display_name) {
        $this->display_name = $display_name;
    }
    
    /**
     * Getters for provider metadata
     */
    public function get_id() {
        return $this->provider_id;
    }
    
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get provider icon URL
     * 
     * @return string Icon URL
     */
    public function get_icon_url() {
        $icon_file = $this->get_type() . '-icon.svg';
        $icon_path = SIXLAB_PLUGIN_DIR . 'assets/images/' . $icon_file;
        
        if (file_exists($icon_path)) {
            return SIXLAB_PLUGIN_URL . 'assets/images/' . $icon_file;
        }
        
        // Fallback to default icon
        return SIXLAB_PLUGIN_URL . 'assets/images/default-provider-icon.svg';
    }
    
    /**
     * Get provider capabilities
     * 
     * @return array Provider capabilities
     */
    public function get_capabilities() {
        return array(
            'features' => $this->get_supported_features(),
            'max_concurrent_sessions' => $this->get_config('max_concurrent_sessions', 50),
            'session_timeout' => $this->get_config('auto_cleanup_minutes', 120),
            'supports_snapshots' => $this->supports_feature('snapshot_support'),
            'supports_recording' => $this->supports_feature('session_recording'),
            'supports_collaboration' => $this->supports_feature('collaborative_labs')
        );
    }
}
