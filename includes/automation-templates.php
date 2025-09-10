<?php
/**
 * 6Lab Tool - Automation Templates
 * Contains template generators for automation scripts
 */

if (!defined('ABSPATH')) {
    exit;
}

trait SixLab_Automation_Templates {
    
    /**
     * Get PHP provider template
     * 
     * @param string $provider_name
     * @param string $provider_type
     * @param string $class_name
     * @return string
     */
    private function get_provider_php_template($provider_name, $provider_type, $class_name) {
        return "<?php
/**
 * {$class_name} - Generated provider class
 * Lab provider for {$provider_name}
 */

if (!defined('ABSPATH')) {
    exit;
}

class {$class_name} extends SixLab_Lab_Provider_Abstract {
    
    /**
     * Provider type identifier
     * @var string
     */
    protected \$provider_type = '{$provider_type}';
    
    /**
     * Provider display name
     * @var string
     */
    protected \$display_name = '" . ucwords(str_replace('_', ' ', $provider_name)) . " Provider';
    
    /**
     * Supported features
     * @var array
     */
    protected \$supported_features = array(
        'console_access',
        'session_management'
    );
    
    /**
     * Initialize provider
     */
    protected function init() {
        // Provider-specific initialization
        \$this->load_config();
    }
    
    /**
     * Get configuration fields for admin interface
     * 
     * @return array
     */
    public function get_config_fields() {
        return array(
            'server_url' => array(
                'type' => 'url',
                'label' => __('Server URL', 'sixlab-tool'),
                'required' => true,
                'default' => 'http://localhost'
            ),
            'username' => array(
                'type' => 'text',
                'label' => __('Username', 'sixlab-tool'),
                'required' => false
            ),
            'password' => array(
                'type' => 'password',
                'label' => __('Password', 'sixlab-tool'),
                'required' => false
            )
        );
    }
    
    /**
     * Test provider connection
     * 
     * @return array
     */
    public function test_connection() {
        // Implement connection test logic
        return array(
            'success' => true,
            'message' => __('Connection test not implemented yet', 'sixlab-tool')
        );
    }
    
    /**
     * Create lab session
     * 
     * @param array \$lab_config
     * @param int \$user_id
     * @return array
     */
    public function create_lab_session(\$lab_config, \$user_id) {
        // Implement session creation logic
        return array(
            'success' => false,
            'message' => __('Session creation not implemented yet', 'sixlab-tool')
        );
    }
    
    /**
     * Get session status
     * 
     * @param string \$session_id
     * @return array
     */
    public function get_session_status(\$session_id) {
        // Implement session status check
        return array(
            'status' => 'unknown',
            'message' => __('Status check not implemented yet', 'sixlab-tool')
        );
    }
    
    /**
     * Validate lab step
     * 
     * @param string \$session_id
     * @param int \$step
     * @param array \$validation_data
     * @return array
     */
    public function validate_step(\$session_id, \$step, \$validation_data) {
        // Implement step validation logic
        return array(
            'passed' => false,
            'score' => 0,
            'feedback' => __('Validation not implemented yet', 'sixlab-tool')
        );
    }
    
    /**
     * Cleanup lab session
     * 
     * @param string \$session_id
     * @return bool
     */
    public function cleanup_session(\$session_id) {
        // Implement session cleanup
        return true;
    }
    
    /**
     * Get session access URL
     * 
     * @param string \$session_id
     * @return string|false
     */
    public function get_session_url(\$session_id) {
        // Return URL where students can access the lab
        return false;
    }
}
";
    }
    
    /**
     * Get JavaScript provider adapter template
     * 
     * @param string $provider_name
     * @param string $provider_type
     * @return string
     */
    private function get_provider_js_template($provider_name, $provider_type) {
        return "/**
 * {$provider_name} Provider Adapter
 * Frontend JavaScript for {$provider_name} provider integration
 */

class " . ucfirst($provider_name) . "Adapter {
    constructor(sessionId, config) {
        this.sessionId = sessionId;
        this.config = config;
        this.providerType = '{$provider_type}';
        this.init();
    }
    
    /**
     * Initialize adapter
     */
    init() {
        this.setupEventListeners();
        this.loadProviderInterface();
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Provider-specific event listeners
        document.addEventListener('sixlab:validateStep', (e) => {
            this.validateCurrentStep(e.detail);
        });
        
        document.addEventListener('sixlab:resetSession', (e) => {
            this.resetSession();
        });
    }
    
    /**
     * Load provider interface
     */
    loadProviderInterface() {
        const iframe = document.querySelector('.provider-iframe');
        if (iframe && this.config.interface_url) {
            iframe.src = this.config.interface_url + '?session=' + this.sessionId;
        }
    }
    
    /**
     * Validate current step
     * @param {Object} stepData 
     */
    async validateCurrentStep(stepData) {
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_validate_step',
                    provider: this.providerType,
                    session_id: this.sessionId,
                    step_data: JSON.stringify(stepData),
                    nonce: sixlabAjax.nonce
                })
            });
            
            const result = await response.json();
            this.handleValidationResult(result);
            
        } catch (error) {
            console.error('{$provider_name} validation error:', error);
            this.showError('Validation failed. Please try again.');
        }
    }
    
    /**
     * Handle validation result
     * @param {Object} result 
     */
    handleValidationResult(result) {
        if (result.success) {
            this.showSuccess(result.data.feedback || 'Step completed successfully!');
            
            if (result.data.advance_step) {
                this.advanceToNextStep();
            }
        } else {
            this.showError(result.data.message || 'Validation failed.');
        }
    }
    
    /**
     * Reset session
     */
    async resetSession() {
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_reset_session',
                    provider: this.providerType,
                    session_id: this.sessionId,
                    nonce: sixlabAjax.nonce
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.loadProviderInterface();
                this.showSuccess('Session reset successfully.');
            } else {
                this.showError('Failed to reset session.');
            }
            
        } catch (error) {
            console.error('{$provider_name} reset error:', error);
            this.showError('Reset failed. Please try again.');
        }
    }
    
    /**
     * Advance to next step
     */
    advanceToNextStep() {
        const event = new CustomEvent('sixlab:stepAdvanced', {
            detail: { provider: this.providerType }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Show success message
     * @param {string} message 
     */
    showSuccess(message) {
        // Implementation depends on your notification system
        console.log('{$provider_name} Success:', message);
    }
    
    /**
     * Show error message
     * @param {string} message 
     */
    showError(message) {
        // Implementation depends on your notification system
        console.error('{$provider_name} Error:', message);
    }
    
    /**
     * Get session data
     * @returns {Object}
     */
    getSessionData() {
        return {
            sessionId: this.sessionId,
            provider: this.providerType,
            timestamp: Date.now()
        };
    }
    
    /**
     * Cleanup adapter
     */
    destroy() {
        // Cleanup event listeners and resources
        document.removeEventListener('sixlab:validateStep', this.validateCurrentStep);
        document.removeEventListener('sixlab:resetSession', this.resetSession);
    }
}

// Export for use in main application
window." . ucfirst($provider_name) . "Adapter = " . ucfirst($provider_name) . "Adapter;
";
    }
    
    /**
     * Get provider config admin template
     * 
     * @param string $provider_name
     * @param string $provider_type
     * @return string
     */
    private function get_provider_config_template($provider_name, $provider_type) {
        $display_name = ucwords(str_replace('_', ' ', $provider_name));
        
        return "<?php
/**
 * Admin configuration view for {$display_name} Provider
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get provider instance
\$provider = SixLab_Provider_Factory::get_provider('{$provider_type}');
\$config_fields = \$provider ? \$provider->get_config_fields() : array();
\$current_config = get_option('sixlab_{$provider_type}_config', array());
?>

<div class=\"wrap\">
    <h1><?php echo esc_html__('{$display_name} Provider Configuration', 'sixlab-tool'); ?></h1>
    
    <form method=\"post\" action=\"options.php\">
        <?php
        settings_fields('sixlab_{$provider_type}_config');
        do_settings_sections('sixlab_{$provider_type}_config');
        ?>
        
        <table class=\"form-table\">
            <?php foreach (\$config_fields as \$field_key => \$field_config): ?>
                <tr>
                    <th scope=\"row\">
                        <label for=\"sixlab_{$provider_type}_{\$field_key}\">
                            <?php echo esc_html(\$field_config['label']); ?>
                            <?php if (!empty(\$field_config['required'])): ?>
                                <span class=\"required\">*</span>
                            <?php endif; ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        \$field_name = \"sixlab_{$provider_type}_config[{\$field_key}]\";
                        \$field_value = \$current_config[\$field_key] ?? \$field_config['default'] ?? '';
                        \$field_id = \"sixlab_{$provider_type}_{\$field_key}\";
                        
                        switch (\$field_config['type']):
                            case 'text':
                            case 'url':
                            case 'email':
                                ?>
                                <input type=\"<?php echo esc_attr(\$field_config['type']); ?>\" 
                                       id=\"<?php echo esc_attr(\$field_id); ?>\"
                                       name=\"<?php echo esc_attr(\$field_name); ?>\" 
                                       value=\"<?php echo esc_attr(\$field_value); ?>\"
                                       class=\"regular-text\"
                                       <?php echo !empty(\$field_config['required']) ? 'required' : ''; ?>
                                       <?php echo !empty(\$field_config['placeholder']) ? 'placeholder=\"' . esc_attr(\$field_config['placeholder']) . '\"' : ''; ?>
                                />
                                <?php
                                break;
                                
                            case 'password':
                                ?>
                                <input type=\"password\" 
                                       id=\"<?php echo esc_attr(\$field_id); ?>\"
                                       name=\"<?php echo esc_attr(\$field_name); ?>\" 
                                       value=\"<?php echo esc_attr(\$field_value); ?>\"
                                       class=\"regular-text\"
                                       <?php echo !empty(\$field_config['required']) ? 'required' : ''; ?>
                                />
                                <?php
                                break;
                                
                            case 'number':
                                ?>
                                <input type=\"number\" 
                                       id=\"<?php echo esc_attr(\$field_id); ?>\"
                                       name=\"<?php echo esc_attr(\$field_name); ?>\" 
                                       value=\"<?php echo esc_attr(\$field_value); ?>\"
                                       class=\"regular-text\"
                                       <?php echo !empty(\$field_config['min']) ? 'min=\"' . esc_attr(\$field_config['min']) . '\"' : ''; ?>
                                       <?php echo !empty(\$field_config['max']) ? 'max=\"' . esc_attr(\$field_config['max']) . '\"' : ''; ?>
                                       <?php echo !empty(\$field_config['step']) ? 'step=\"' . esc_attr(\$field_config['step']) . '\"' : ''; ?>
                                />
                                <?php
                                break;
                                
                            case 'checkbox':
                                ?>
                                <input type=\"checkbox\" 
                                       id=\"<?php echo esc_attr(\$field_id); ?>\"
                                       name=\"<?php echo esc_attr(\$field_name); ?>\" 
                                       value=\"1\"
                                       <?php checked(\$field_value, 1); ?>
                                />
                                <?php
                                break;
                                
                            case 'select':
                                if (!empty(\$field_config['options'])):
                                    ?>
                                    <select id=\"<?php echo esc_attr(\$field_id); ?>\"
                                            name=\"<?php echo esc_attr(\$field_name); ?>\">
                                        <?php foreach (\$field_config['options'] as \$option_value => \$option_label): ?>
                                            <option value=\"<?php echo esc_attr(\$option_value); ?>\"
                                                    <?php selected(\$field_value, \$option_value); ?>>
                                                <?php echo esc_html(\$option_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php
                                endif;
                                break;
                                
                            case 'textarea':
                                ?>
                                <textarea id=\"<?php echo esc_attr(\$field_id); ?>\"
                                          name=\"<?php echo esc_attr(\$field_name); ?>\" 
                                          rows=\"5\" 
                                          class=\"large-text\"><?php echo esc_textarea(\$field_value); ?></textarea>
                                <?php
                                break;
                        endswitch;
                        ?>
                        
                        <?php if (!empty(\$field_config['description'])): ?>
                            <p class=\"description\"><?php echo esc_html(\$field_config['description']); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <?php submit_button(__('Save Configuration', 'sixlab-tool')); ?>
    </form>
    
    <div class=\"postbox\" style=\"margin-top: 20px;\">
        <h3 class=\"hndle\"><?php esc_html_e('Connection Test', 'sixlab-tool'); ?></h3>
        <div class=\"inside\">
            <p><?php esc_html_e('Test the connection to your {$display_name} server.', 'sixlab-tool'); ?></p>
            <button type=\"button\" class=\"button button-secondary\" id=\"test-{$provider_type}-connection\">
                <?php esc_html_e('Test Connection', 'sixlab-tool'); ?>
            </button>
            <div id=\"{$provider_type}-test-result\" style=\"margin-top: 10px;\"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function(\$) {
    \$('#test-{$provider_type}-connection').on('click', function() {
        var button = \$(this);
        var resultDiv = \$('#{$provider_type}-test-result');
        
        button.prop('disabled', true).text('<?php esc_js_e('Testing...', 'sixlab-tool'); ?>');
        resultDiv.html('');
        
        \$.post(ajaxurl, {
            action: 'sixlab_test_provider_connection',
            provider: '{$provider_type}',
            nonce: '<?php echo wp_create_nonce('sixlab_test_connection'); ?>'
        }, function(response) {
            button.prop('disabled', false).text('<?php esc_js_e('Test Connection', 'sixlab-tool'); ?>');
            
            if (response.success) {
                resultDiv.html('<div class=\"notice notice-success inline\"><p>' + response.data.message + '</p></div>');
            } else {
                resultDiv.html('<div class=\"notice notice-error inline\"><p>' + response.data.message + '</p></div>');
            }
        }).fail(function() {
            button.prop('disabled', false).text('<?php esc_js_e('Test Connection', 'sixlab-tool'); ?>');
            resultDiv.html('<div class=\"notice notice-error inline\"><p><?php esc_js_e('Connection test failed.', 'sixlab-tool'); ?></p></div>');
        });
    });
});
</script>
";
    }
    
    /**
     * Get provider icon SVG template
     * 
     * @param string $provider_name
     * @return string
     */
    private function get_provider_icon_template($provider_name) {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<svg width=\"64\" height=\"64\" viewBox=\"0 0 64 64\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">
    <!-- Generated icon for {$provider_name} provider -->
    <rect width=\"64\" height=\"64\" rx=\"12\" fill=\"#3498db\"/>
    <path d=\"M20 32h24M32 20v24M26 26l12 12M26 38l12-12\" stroke=\"white\" stroke-width=\"2\" stroke-linecap=\"round\"/>
    <circle cx=\"32\" cy=\"32\" r=\"4\" fill=\"white\"/>
</svg>";
    }
    
    /**
     * Get AI provider PHP template
     * 
     * @param string $ai_provider
     * @param string $class_name
     * @return string
     */
    private function get_ai_provider_php_template($ai_provider, $class_name) {
        return "<?php
/**
 * {$class_name} - Generated AI provider class
 * AI integration for {$ai_provider}
 */

if (!defined('ABSPATH')) {
    exit;
}

class {$class_name} extends SixLab_AI_Provider_Abstract {
    
    /**
     * Provider identifier
     * @var string
     */
    protected \$provider_id = '{$ai_provider}';
    
    /**
     * Provider display name
     * @var string
     */
    protected \$display_name = '" . ucwords(str_replace('_', ' ', $ai_provider)) . "';
    
    /**
     * API base URL
     * @var string
     */
    protected \$api_base_url = '';
    
    /**
     * Supported features
     * @var array
     */
    protected \$supported_features = array(
        'contextual_help',
        'configuration_analysis',
        'chat_conversation',
        'error_explanation'
    );
    
    /**
     * Initialize provider
     */
    protected function init() {
        \$this->load_config();
        \$this->setup_api_client();
    }
    
    /**
     * Get configuration fields
     * 
     * @return array
     */
    public function get_config_fields() {
        return array(
            'api_key' => array(
                'type' => 'password',
                'label' => __('API Key', 'sixlab-tool'),
                'description' => __('Your {$ai_provider} API key', 'sixlab-tool'),
                'required' => true
            ),
            'model' => array(
                'type' => 'select',
                'label' => __('Model', 'sixlab-tool'),
                'options' => array(
                    'default' => __('Default Model', 'sixlab-tool')
                ),
                'default' => 'default'
            ),
            'max_tokens' => array(
                'type' => 'number',
                'label' => __('Max Tokens', 'sixlab-tool'),
                'default' => 1000,
                'min' => 100,
                'max' => 4000
            )
        );
    }
    
    /**
     * Send request to AI provider
     * 
     * @param string \$message
     * @param array \$context
     * @param string \$interaction_type
     * @return array
     */
    public function send_request(\$message, \$context = array(), \$interaction_type = 'chat') {
        // Implement API request logic
        return array(
            'success' => false,
            'message' => 'AI provider integration not implemented yet',
            'response' => '',
            'tokens_used' => 0,
            'cost' => 0
        );
    }
    
    /**
     * Get contextual help
     * 
     * @param array \$context
     * @return array
     */
    public function get_contextual_help(\$context) {
        \$prompt = \$this->build_contextual_help_prompt(\$context);
        return \$this->send_request(\$prompt, \$context, 'contextual_help');
    }
    
    /**
     * Analyze configuration
     * 
     * @param string \$configuration
     * @param array \$context
     * @return array
     */
    public function analyze_configuration(\$configuration, \$context) {
        \$prompt = \$this->build_configuration_analysis_prompt(\$configuration, \$context);
        return \$this->send_request(\$prompt, \$context, 'configuration_analysis');
    }
    
    /**
     * Explain error
     * 
     * @param string \$error_message
     * @param array \$context
     * @return array
     */
    public function explain_error(\$error_message, \$context) {
        \$prompt = \$this->build_error_explanation_prompt(\$error_message, \$context);
        return \$this->send_request(\$prompt, \$context, 'error_explanation');
    }
    
    /**
     * Test API connection
     * 
     * @return array
     */
    public function test_connection() {
        // Implement connection test
        return array(
            'success' => true,
            'message' => 'Connection test not implemented yet'
        );
    }
    
    /**
     * Build contextual help prompt
     * 
     * @param array \$context
     * @return string
     */
    private function build_contextual_help_prompt(\$context) {
        // Build prompt based on context
        return 'Provide help for the current lab step.';
    }
    
    /**
     * Build configuration analysis prompt
     * 
     * @param string \$configuration
     * @param array \$context
     * @return string
     */
    private function build_configuration_analysis_prompt(\$configuration, \$context) {
        // Build analysis prompt
        return \"Analyze this configuration: {\$configuration}\";
    }
    
    /**
     * Build error explanation prompt
     * 
     * @param string \$error_message
     * @param array \$context
     * @return string
     */
    private function build_error_explanation_prompt(\$error_message, \$context) {
        // Build error explanation prompt
        return \"Explain this error: {\$error_message}\";
    }
    
    /**
     * Setup API client
     */
    private function setup_api_client() {
        // Initialize API client
    }
}
";
    }
    
    /**
     * Get AI provider config template
     * 
     * @param string $ai_provider
     * @return string
     */
    private function get_ai_provider_config_template($ai_provider) {
        $display_name = ucwords(str_replace('_', ' ', $ai_provider));
        
        return "<?php
/**
 * Admin configuration view for {$display_name} AI Provider
 */

if (!defined('ABSPATH')) {
    exit;
}

// Similar structure to provider config template but for AI providers
echo '<div class=\"wrap\">';
echo '<h1>' . esc_html__('{$display_name} AI Configuration', 'sixlab-tool') . '</h1>';
echo '<p>' . esc_html__('Configure {$display_name} AI integration settings.', 'sixlab-tool') . '</p>';
echo '</div>';
";
    }
    
    /**
     * Get PHPUnit configuration template
     * 
     * @return string
     */
    private function get_phpunit_xml_template() {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<phpunit xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
         xsi:noNamespaceSchemaLocation=\"https://schema.phpunit.de/9.5/phpunit.xsd\"
         bootstrap=\"tests/bootstrap.php\"
         cacheResultFile=\".phpunit.cache/test-results\"
         executionOrder=\"depends,defects\"
         forceCoversAnnotation=\"true\"
         beStrictAboutCoversAnnotation=\"true\"
         beStrictAboutOutputDuringTests=\"true\"
         beStrictAboutTodoAnnotatedTests=\"true\"
         convertDeprecationsToExceptions=\"true\"
         failOnRisky=\"true\"
         failOnWarning=\"true\"
         verbose=\"true\">
    <testsuites>
        <testsuite name=\"6Lab Tool Test Suite\">
            <directory>tests/unit</directory>
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>

    <coverage cacheDirectory=\".phpunit.cache/code-coverage\"
              processUncoveredFiles=\"true\">
        <include>
            <directory suffix=\".php\">includes</directory>
            <directory suffix=\".php\">admin</directory>
            <directory suffix=\".php\">public</directory>
        </include>
        <exclude>
            <directory>tests</directory>
            <directory>vendor</directory>
        </exclude>
    </coverage>
</phpunit>";
    }
    
    /**
     * Get Jest configuration template
     * 
     * @return string
     */
    private function get_jest_config_template() {
        return "module.exports = {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests'],
  testMatch: [
    '**/__tests__/**/*.+(ts|tsx|js)',
    '**/*.(test|spec).(ts|tsx|js)'
  ],
  transform: {
    '^.+\\.(ts|tsx)$': 'ts-jest',
    '^.+\\.(js|jsx)$': 'babel-jest'
  },
  collectCoverageFrom: [
    'public/js/**/*.{js,ts}',
    '!public/js/**/*.d.ts',
    '!public/js/vendor/**',
    '!**/node_modules/**'
  ],
  coverageDirectory: 'coverage',
  coverageReporters: [
    'text',
    'lcov',
    'html'
  ],
  setupFilesAfterEnv: [
    '<rootDir>/tests/jest.setup.js'
  ],
  moduleNameMapping: {
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy'
  }
};";
    }
}
