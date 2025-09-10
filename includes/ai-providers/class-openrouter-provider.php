<?php
/**
 * OpenRouter AI Provider Class
 * 
 * Provides AI capabilities through OpenRouter API
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OpenRouter AI Provider Class
 */
class SixLab_OpenRouter_Provider extends SixLab_AI_Provider_Abstract {
    
    /**
     * API base URL
     * @var string
     */
    private $api_base = 'https://openrouter.ai/api/v1';
    
    /**
     * Supported models
     * @var array
     */
    private $supported_models = array(
        // OpenAI Models
        'openai/gpt-4o' => array(
            'name' => 'GPT-4o',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.005,
                'output' => 0.015
            )
        ),
        'openai/gpt-4o-mini' => array(
            'name' => 'GPT-4o Mini',
            'context_window' => 128000,
            'max_output' => 16384,
            'cost_per_1k_tokens' => array(
                'input' => 0.00015,
                'output' => 0.0006
            )
        ),
        'openai/gpt-4-turbo' => array(
            'name' => 'GPT-4 Turbo',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.01,
                'output' => 0.03
            )
        ),
        'openai/gpt-3.5-turbo' => array(
            'name' => 'GPT-3.5 Turbo',
            'context_window' => 16385,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.0005,
                'output' => 0.0015
            )
        ),
        
        // Anthropic Models
        'anthropic/claude-3.5-sonnet' => array(
            'name' => 'Claude 3.5 Sonnet',
            'context_window' => 200000,
            'max_output' => 8192,
            'cost_per_1k_tokens' => array(
                'input' => 0.003,
                'output' => 0.015
            )
        ),
        'anthropic/claude-3-opus' => array(
            'name' => 'Claude 3 Opus',
            'context_window' => 200000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.015,
                'output' => 0.075
            )
        ),
        'anthropic/claude-3-haiku' => array(
            'name' => 'Claude 3 Haiku',
            'context_window' => 200000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.00025,
                'output' => 0.00125
            )
        ),
        
        // Meta Models
        'meta-llama/llama-3.1-405b-instruct' => array(
            'name' => 'Llama 3.1 405B Instruct',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.003,
                'output' => 0.003
            )
        ),
        'meta-llama/llama-3.1-70b-instruct' => array(
            'name' => 'Llama 3.1 70B Instruct',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.0009,
                'output' => 0.0009
            )
        ),
        'meta-llama/llama-3.1-8b-instruct' => array(
            'name' => 'Llama 3.1 8B Instruct',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.0002,
                'output' => 0.0002
            )
        ),
        
        // Google Models
        'google/gemini-pro-1.5' => array(
            'name' => 'Gemini Pro 1.5',
            'context_window' => 2000000,
            'max_output' => 8192,
            'cost_per_1k_tokens' => array(
                'input' => 0.00125,
                'output' => 0.005
            )
        ),
        
        // Mistral Models
        'mistralai/mistral-large' => array(
            'name' => 'Mistral Large',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.004,
                'output' => 0.012
            )
        ),
        'mistralai/mistral-medium' => array(
            'name' => 'Mistral Medium',
            'context_window' => 32768,
            'max_output' => 4096,
            'cost_per_1k_tokens' => array(
                'input' => 0.00275,
                'output' => 0.0081
            )
        )
    );
    
    /**
     * Get provider type identifier
     * 
     * @return string Provider type
     */
    public function get_type() {
        return 'openrouter';
    }
    
    /**
     * Get provider display name
     * 
     * @return string Display name
     */
    public function get_display_name() {
        return $this->get_name();
    }
    
    /**
     * Get provider name
     * 
     * @return string
     */
    public function get_name() {
        return 'OpenRouter';
    }
    
    /**
     * Get provider description
     * 
     * @return string
     */
    public function get_description() {
        return __('OpenRouter provides access to multiple AI models through a unified API, including GPT-4, Claude, Llama, and more.', 'sixlab-tool');
    }
    
    /**
     * Get default configuration
     * 
     * @return array
     */
    public function get_default_config() {
        return array(
            'api_key' => '',
            'app_name' => 'SixLab Tool',
            'model' => 'openai/gpt-4o-mini',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'timeout' => 30,
            'rate_limit_per_minute' => 60,
            'input_token_rate' => 0.00015,
            'output_token_rate' => 0.0006
        );
    }
    
    /**
     * Get configuration fields for admin interface
     * 
     * @return array
     */
    public function get_config_fields() {
        return array(
            'api_key' => array(
                'type' => 'password',
                'label' => __('OpenRouter API Key', 'sixlab-tool'),
                'description' => __('Your OpenRouter API key from https://openrouter.ai/keys', 'sixlab-tool'),
                'required' => true
            ),
            'app_name' => array(
                'type' => 'text',
                'label' => __('Application Name', 'sixlab-tool'),
                'description' => __('Name of your application (for analytics)', 'sixlab-tool'),
                'default' => 'SixLab Tool'
            ),
            'model' => array(
                'type' => 'select',
                'label' => __('Default Model', 'sixlab-tool'),
                'description' => __('Default AI model to use for requests', 'sixlab-tool'),
                'options' => $this->get_model_options(),
                'default' => 'openai/gpt-4o-mini'
            ),
            'temperature' => array(
                'type' => 'number',
                'label' => __('Temperature', 'sixlab-tool'),
                'description' => __('Controls randomness (0.0 = deterministic, 1.0 = creative)', 'sixlab-tool'),
                'min' => 0.0,
                'max' => 2.0,
                'step' => 0.1,
                'default' => 0.7
            ),
            'max_tokens' => array(
                'type' => 'number',
                'label' => __('Max Tokens', 'sixlab-tool'),
                'description' => __('Maximum tokens in response', 'sixlab-tool'),
                'min' => 100,
                'max' => 16384,
                'default' => 2000
            ),
            'timeout' => array(
                'type' => 'number',
                'label' => __('Request Timeout', 'sixlab-tool'),
                'description' => __('API request timeout in seconds', 'sixlab-tool'),
                'min' => 10,
                'max' => 120,
                'default' => 30
            ),
            'rate_limit_per_minute' => array(
                'type' => 'number',
                'label' => __('Rate Limit per Minute', 'sixlab-tool'),
                'description' => __('Maximum requests per minute', 'sixlab-tool'),
                'min' => 1,
                'max' => 1000,
                'default' => 60
            )
        );
    }
    
    /**
     * Get model options for select field
     * 
     * @return array
     */
    private function get_model_options() {
        $options = array();
        foreach ($this->supported_models as $model_id => $model_info) {
            $options[$model_id] = $model_info['name'] . ' (' . $model_id . ')';
        }
        return $options;
    }
    
    /**
     * Test provider connection
     * 
     * @return array
     */
    public function test_connection() {
        if (empty($this->config['api_key'])) {
            return array(
                'success' => false,
                'message' => __('API key is required', 'sixlab-tool')
            );
        }
        
        try {
            $response = $this->make_request('chat/completions', array(
                'model' => 'openai/gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello, this is a test message.'
                    )
                ),
                'max_tokens' => 10
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message()
                );
            }
            
            return array(
                'success' => true,
                'message' => __('Connection successful', 'sixlab-tool'),
                'data' => array(
                    'model' => $response['model'] ?? 'Unknown',
                    'usage' => $response['usage'] ?? array()
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Generate contextual help
     * 
     * @param array $context Lab context
     * @return array
     */
    public function generate_contextual_help($context) {
        $model = $this->config['model'];
        $prompt = $this->build_contextual_help_prompt($context);
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are an expert network lab instructor helping students with practical networking exercises. Provide clear, actionable guidance.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        return $this->generate_response($messages, $model);
    }
    
    /**
     * Analyze configuration (Helper method)
     * 
     * @param array $config_data Configuration to analyze
     * @param array $context Lab context
     * @return array
     */
    public function analyze_lab_configuration($config_data, $context = array()) {
        $model = $this->config['model'];
        $prompt = $this->build_configuration_analysis_prompt($config_data, $context);
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are a network configuration expert. Analyze the provided configuration and identify issues, improvements, and best practices.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        return $this->generate_response($messages, $model);
    }
    
    /**
     * Handle chat interaction
     * 
     * @param string $message User message
     * @param array $context Chat context
     * @return array
     */
    public function chat($message, $context = array()) {
        $model = $this->config['model'];
        $messages = $this->build_chat_messages($message, $context);
        
        return $this->generate_response($messages, $model);
    }
    
    /**
     * Generate response from OpenRouter API
     * 
     * @param array $messages Message array
     * @param string $model Model to use
     * @return array
     */
    private function generate_response($messages, $model) {
        try {
            $start_time = microtime(true);
            
            $request_data = array(
                'model' => $model,
                'messages' => $messages,
                'temperature' => floatval($this->config['temperature']),
                'max_tokens' => intval($this->config['max_tokens'])
            );
            
            $response = $this->make_request('chat/completions', $request_data);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'error' => $response->get_error_message()
                );
            }
            
            $response_time = (microtime(true) - $start_time) * 1000;
            $usage = $response['usage'] ?? array();
            
            return array(
                'success' => true,
                'content' => $response['choices'][0]['message']['content'] ?? '',
                'model' => $response['model'] ?? $model,
                'usage' => $usage,
                'response_time_ms' => round($response_time),
                'cost_estimate' => $this->calculate_cost($usage, $model),
                'metadata' => array(
                    'provider' => 'openrouter',
                    'model_info' => $this->supported_models[$model] ?? array(),
                    'request_id' => $response['id'] ?? null
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Make API request to OpenRouter
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|WP_Error
     */
    private function make_request($endpoint, $data) {
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit', __('Rate limit exceeded', 'sixlab-tool'));
        }
        
        $url = $this->api_base . '/' . ltrim($endpoint, '/');
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
            'HTTP-Referer' => home_url(),
            'X-Title' => $this->config['app_name']
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => intval($this->config['timeout'])
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = $error_data['error']['message'] ?? 'Unknown API error';
            return new WP_Error('api_error', $error_message);
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON response', 'sixlab-tool'));
        }
        
        $this->update_rate_limit();
        
        return $decoded_response;
    }
    
    /**
     * Calculate cost for API usage
     * 
     * @param array $usage Usage data from API response
     * @param string $model Model used
     * @return float Estimated cost in USD
     */
    protected function calculate_cost($usage, $model) {
        if (!isset($this->supported_models[$model])) {
            return 0.0;
        }
        
        $model_info = $this->supported_models[$model];
        $cost_info = $model_info['cost_per_1k_tokens'];
        
        $input_tokens = $usage['prompt_tokens'] ?? 0;
        $output_tokens = $usage['completion_tokens'] ?? 0;
        
        $input_cost = ($input_tokens / 1000) * $cost_info['input'];
        $output_cost = ($output_tokens / 1000) * $cost_info['output'];
        
        return $input_cost + $output_cost;
    }
    
    /**
     * Get available models
     * 
     * @return array
     */
    public function get_available_models() {
        return $this->supported_models;
    }
    
    /**
     * Check if model is supported
     * 
     * @param string $model Model identifier
     * @return bool
     */
    public function is_model_supported($model) {
        return isset($this->supported_models[$model]);
    }
    
    /**
     * Get model capabilities
     * 
     * @param string $model Model identifier
     * @return array
     */
    public function get_model_capabilities($model) {
        if (!$this->is_model_supported($model)) {
            return array();
        }
        
        $model_info = $this->supported_models[$model];
        
        return array(
            'name' => $model_info['name'],
            'context_window' => $model_info['context_window'],
            'max_output' => $model_info['max_output'],
            'supports_streaming' => true,
            'supports_functions' => strpos($model, 'openai/') === 0 || strpos($model, 'anthropic/') === 0,
            'cost_per_1k_tokens' => $model_info['cost_per_1k_tokens']
        );
    }
    
    /**
     * Get default prompt templates
     * 
     * @return array Default prompt templates
     */
    public function get_default_prompts() {
        return array(
            'contextual_help' => "You are an expert network lab instructor using OpenRouter's multi-model capabilities. Help the student with their current lab step.\n\nLab Context: {lab_context}\nCurrent Step: {current_step}\nStudent Question: {question}\n\nProvide clear, practical guidance without giving away the complete solution.",
            'configuration_analysis' => "Analyze the following network configuration and provide feedback:\n\nConfiguration: {configuration}\nExpected Outcome: {expected}\n\nIdentify any issues, suggest improvements, and explain networking concepts.",
            'error_explanation' => "Explain this error and suggest fixes:\n\nError: {error_message}\nContext: {context}\nConfiguration: {configuration}\n\nProvide a clear explanation and step-by-step solution.",
            'hint_generation' => "Generate a progressive hint for this lab step:\n\nStep: {step_description}\nAttempt: {attempt_number}\nPrevious Hints: {previous_hints}\n\nProvide a helpful hint that guides without solving.",
            'chat_response' => "You are a helpful networking lab assistant powered by OpenRouter. Respond to the student's question:\n\nQuestion: {message}\nLab Context: {lab_context}\n\nBe helpful and educational."
        );
    }
    
    /**
     * Get contextual help for lab step (Abstract method implementation)
     * 
     * @param array $context Context data including lab info, step, user data
     * @return array|WP_Error AI response or error
     */
    public function get_contextual_help($context) {
        return $this->generate_contextual_help($context);
    }
    
    /**
     * Analyze configuration and provide feedback (Abstract method implementation)
     * 
     * @param array $context Context data including configuration and expected results
     * @return array|WP_Error Analysis results or error
     */
    public function analyze_configuration($context) {
        $config_data = $context['configuration'] ?? $context;
        return $this->analyze_lab_configuration($config_data, $context);
    }
    
    /**
     * Explain error and suggest fixes
     * 
     * @param array $context Context data including error message and configuration
     * @return array|WP_Error Error explanation or error
     */
    public function explain_error($context) {
        $model = $this->config['model'];
        $prompt = $this->build_prompt('error_explanation', $context);
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are a network debugging expert. Analyze errors and provide clear solutions.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        return $this->generate_response($messages, $model);
    }
    
    /**
     * Generate progressive hints for lab step
     * 
     * @param array $context Context data including step, attempts, previous hints
     * @return array|WP_Error Generated hints or error
     */
    public function generate_hints($context) {
        $model = $this->config['model'];
        $prompt = $this->build_prompt('hint_generation', $context);
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are a lab instructor providing progressive hints. Guide without solving.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        return $this->generate_response($messages, $model);
    }
    
    /**
     * Handle chat conversation (Abstract method implementation)
     * 
     * @param array $context Context data including message and conversation history
     * @return array|WP_Error Chat response or error
     */
    public function chat_response($context) {
        $message = $context['message'] ?? '';
        return $this->chat($message, $context);
    }
    
    /**
     * Build prompt from template and context
     * 
     * @param string $template_name Template name
     * @param array $context Context data
     * @return string Built prompt
     */
    protected function build_prompt($template_name, $context) {
        $templates = $this->get_default_prompts();
        $template = $templates[$template_name] ?? '';
        
        // Replace placeholders with context data
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $template = str_replace('{' . $key . '}', $value, $template);
            }
        }
        
        return $template;
    }
}
