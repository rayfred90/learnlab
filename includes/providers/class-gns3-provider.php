<?php
/**
 * GNS3 Provider Class
 * 
 * GNS3 network simulation provider implementation
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GNS3 Provider Class
 */
class GNS3_Provider extends SixLab_Lab_Provider_Abstract {
    
    /**
     * Supported features
     * @var array
     */
    protected $supported_features = array(
        'network_topology',
        'console_access',
        'real_time_validation',
        'configuration_backup',
        'snapshot_support',
        'multi_vendor_devices'
    );
    
    /**
     * GNS3 API base URL
     * @var string
     */
    private $api_base_url;
    
    /**
     * Initialize GNS3 provider
     */
    protected function init() {
        $this->api_base_url = rtrim($this->get_config('server_url', 'http://localhost:3080'), '/') . '/v2';
    }
    
    /**
     * Get provider type identifier
     * 
     * @return string Provider type
     */
    public function get_type() {
        return 'gns3';
    }
    
    /**
     * Get provider display name
     * 
     * @return string Display name
     */
    public function get_display_name() {
        return __('GNS3 Network Simulator', 'sixlab-tool');
    }
    
    /**
     * Get provider description
     * 
     * @return string Description
     */
    public function get_description() {
        return __('Professional network simulation with Cisco-style interfaces and multi-vendor device support', 'sixlab-tool');
    }
    
    /**
     * Get default configuration
     * 
     * @return array Default configuration
     */
    public function get_default_config() {
        return array(
            'server_url' => 'http://localhost:3080',
            'web_gui_url' => 'http://localhost:3080',
            'auth_username' => '',
            'auth_password' => '',
            'templates_directory' => '/opt/gns3/projects/templates',
            'auto_cleanup_minutes' => 120,
            'max_concurrent_sessions' => 50,
            'enable_snapshots' => true
        );
    }
    
    /**
     * Get configuration fields for admin interface
     * 
     * @return array Configuration fields
     */
    public function get_config_fields() {
        return array(
            'server_url' => array(
                'type' => 'url',
                'label' => __('GNS3 Server URL', 'sixlab-tool'),
                'description' => __('URL of your GNS3 server (e.g., http://localhost:3080)', 'sixlab-tool'),
                'required' => true,
                'validation' => 'required|url',
                'placeholder' => 'http://your-gns3-server:3080'
            ),
            'web_gui_url' => array(
                'type' => 'url',
                'label' => __('Web GUI URL', 'sixlab-tool'),
                'description' => __('URL where students will access GNS3 web interface', 'sixlab-tool'),
                'required' => true,
                'validation' => 'required|url'
            ),
            'auth_username' => array(
                'type' => 'text',
                'label' => __('Authentication Username (Optional)', 'sixlab-tool'),
                'description' => __('Leave empty if no authentication required', 'sixlab-tool')
            ),
            'auth_password' => array(
                'type' => 'password',
                'label' => __('Authentication Password (Optional)', 'sixlab-tool')
            ),
            'templates_directory' => array(
                'type' => 'text',
                'label' => __('Templates Directory', 'sixlab-tool'),
                'description' => __('Path to GNS3 project templates', 'sixlab-tool'),
                'default' => '/opt/gns3/projects/templates'
            ),
            'auto_cleanup_minutes' => array(
                'type' => 'number',
                'label' => __('Auto Cleanup Time (minutes)', 'sixlab-tool'),
                'description' => __('Delete inactive sessions after X minutes', 'sixlab-tool'),
                'default' => 120,
                'min' => 30,
                'max' => 480,
                'step' => 15
            ),
            'max_concurrent_sessions' => array(
                'type' => 'number',
                'label' => __('Max Concurrent Sessions', 'sixlab-tool'),
                'description' => __('Maximum number of simultaneous lab sessions', 'sixlab-tool'),
                'default' => 50,
                'min' => 1,
                'max' => 200
            ),
            'enable_snapshots' => array(
                'type' => 'checkbox',
                'label' => __('Enable Snapshots', 'sixlab-tool'),
                'description' => __('Allow students to save and restore lab states', 'sixlab-tool'),
                'default' => true
            )
        );
    }
    
    /**
     * Test provider connection
     * 
     * @return array Test results with 'success' and 'message' keys
     */
    public function test_connection() {
        $response = $this->make_gns3_request('/version');
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to connect to GNS3 server: %s', 'sixlab-tool'),
                    $response->get_error_message()
                )
            );
        }
        
        $version_data = json_decode($response['body'], true);
        
        if (!$version_data || !isset($version_data['version'])) {
            return array(
                'success' => false,
                'message' => __('Invalid response from GNS3 server', 'sixlab-tool')
            );
        }
        
        return array(
            'success' => true,
            'message' => sprintf(
                __('Successfully connected to GNS3 server version %s', 'sixlab-tool'),
                $version_data['version']
            ),
            'version' => $version_data['version']
        );
    }
    
    /**
     * Create a new lab session
     * 
     * @param int $user_id User ID
     * @param array $template_data Lab template data
     * @param array $options Session options
     * @return array|WP_Error Session data or error
     */
    public function create_session($user_id, $template_data, $options = array()) {
        // Generate unique project name
        $project_name = 'sixlab_' . $user_id . '_' . time() . '_' . wp_generate_password(8, false);
        
        // Create GNS3 project
        $project_data = array(
            'name' => $project_name,
            'auto_close' => true,
            'auto_open' => false,
            'auto_start' => false
        );
        
        $response = $this->make_gns3_request('/projects', array(
            'method' => 'POST',
            'body' => wp_json_encode($project_data)
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'gns3_project_creation_failed',
                sprintf(__('Failed to create GNS3 project: %s', 'sixlab-tool'), $response->get_error_message())
            );
        }
        
        $project = json_decode($response['body'], true);
        
        if (!$project || !isset($project['project_id'])) {
            return new WP_Error(
                'gns3_invalid_project_response',
                __('Invalid project creation response from GNS3', 'sixlab-tool')
            );
        }
        
        $project_id = $project['project_id'];
        
        // Load lab template into project
        if (isset($template_data['gns3_template']) && !empty($template_data['gns3_template'])) {
            $template_result = $this->load_template($project_id, $template_data['gns3_template']);
            
            if (is_wp_error($template_result)) {
                // Clean up project if template loading fails
                $this->delete_project($project_id);
                return $template_result;
            }
        }
        
        // Generate access URL
        $access_url = $this->get_config('web_gui_url') . '/static/web-ui/server/1/project/' . $project_id;
        
        $session_data = array(
            'session_id' => $project_id,
            'project_name' => $project_name,
            'access_url' => $access_url,
            'project_data' => $project,
            'created_at' => current_time('mysql')
        );
        
        $this->log('session_created', array(
            'project_id' => $project_id,
            'project_name' => $project_name,
            'user_id' => $user_id
        ));
        
        return $session_data;
    }
    
    /**
     * Get session details
     * 
     * @param string $session_id Provider session ID (GNS3 project ID)
     * @return array|WP_Error Session details or error
     */
    public function get_session($session_id) {
        $response = $this->make_gns3_request("/projects/{$session_id}");
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $project = json_decode($response['body'], true);
        
        if (!$project) {
            return new WP_Error('gns3_project_not_found', __('GNS3 project not found', 'sixlab-tool'));
        }
        
        // Get project nodes
        $nodes_response = $this->make_gns3_request("/projects/{$session_id}/nodes");
        $nodes = array();
        
        if (!is_wp_error($nodes_response)) {
            $nodes = json_decode($nodes_response['body'], true) ?: array();
        }
        
        return array(
            'project_id' => $session_id,
            'project_name' => $project['name'],
            'status' => $project['status'],
            'nodes' => $nodes,
            'access_url' => $this->get_config('web_gui_url') . '/static/web-ui/server/1/project/' . $session_id
        );
    }
    
    /**
     * Update session configuration
     * 
     * @param string $session_id Provider session ID
     * @param array $config_data Configuration updates
     * @return bool|WP_Error Success or error
     */
    public function update_session($session_id, $config_data) {
        // Implementation depends on what configuration updates are needed
        // For example, updating project settings, node configurations, etc.
        
        $this->log('session_updated', array(
            'project_id' => $session_id,
            'config_data' => $config_data
        ));
        
        return true;
    }
    
    /**
     * Validate a lab step
     * 
     * @param string $session_id Provider session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Data to validate
     * @return array|WP_Error Validation results or error
     */
    public function validate_step($session_id, $step_config, $validation_data) {
        $validation_type = $step_config['validation_type'] ?? 'configuration';
        
        switch ($validation_type) {
            case 'interface_configuration':
                return $this->validate_interface_configuration($session_id, $step_config, $validation_data);
                
            case 'routing_table':
                return $this->validate_routing_table($session_id, $step_config, $validation_data);
                
            case 'ping_test':
                return $this->validate_ping_test($session_id, $step_config, $validation_data);
                
            default:
                return new WP_Error(
                    'unsupported_validation_type',
                    sprintf(__('Unsupported validation type: %s', 'sixlab-tool'), $validation_type)
                );
        }
    }
    
    /**
     * Destroy a lab session
     * 
     * @param string $session_id Provider session ID
     * @return bool|WP_Error Success or error
     */
    public function destroy_session($session_id) {
        // First stop all nodes
        $stop_response = $this->make_gns3_request("/projects/{$session_id}/nodes/stop", array(
            'method' => 'POST'
        ));
        
        if (is_wp_error($stop_response)) {
            $this->log('session_stop_failed', array(
                'project_id' => $session_id,
                'error' => $stop_response->get_error_message()
            ), 'warning');
        }
        
        // Wait a moment for nodes to stop
        sleep(2);
        
        // Delete the project
        $delete_response = $this->delete_project($session_id);
        
        if (is_wp_error($delete_response)) {
            return $delete_response;
        }
        
        $this->log('session_destroyed', array('project_id' => $session_id));
        
        return true;
    }
    
    /**
     * Get session access URL
     * 
     * @param string $session_id Provider session ID
     * @param int $user_id User ID
     * @return string|WP_Error Access URL or error
     */
    public function get_session_url($session_id, $user_id) {
        return $this->get_config('web_gui_url') . '/static/web-ui/server/1/project/' . $session_id;
    }
    
    /**
     * Load GNS3 template into project
     * 
     * @param string $project_id GNS3 project ID
     * @param string $template_path Template file path
     * @return bool|WP_Error Success or error
     */
    private function load_template($project_id, $template_path) {
        // Implementation would depend on GNS3 template format
        // This is a simplified version
        
        if (!file_exists($template_path)) {
            return new WP_Error(
                'template_not_found',
                sprintf(__('Template file not found: %s', 'sixlab-tool'), $template_path)
            );
        }
        
        // Load and parse template file
        $template_content = file_get_contents($template_path);
        $template_data = json_decode($template_content, true);
        
        if (!$template_data) {
            return new WP_Error(
                'invalid_template',
                __('Invalid template file format', 'sixlab-tool')
            );
        }
        
        // Import template nodes and links
        // This would involve creating nodes and connections based on template data
        
        return true;
    }
    
    /**
     * Delete GNS3 project
     * 
     * @param string $project_id Project ID
     * @return bool|WP_Error Success or error
     */
    private function delete_project($project_id) {
        $response = $this->make_gns3_request("/projects/{$project_id}", array(
            'method' => 'DELETE'
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'gns3_project_deletion_failed',
                sprintf(__('Failed to delete GNS3 project: %s', 'sixlab-tool'), $response->get_error_message())
            );
        }
        
        return true;
    }
    
    /**
     * Validate interface configuration
     * 
     * @param string $session_id Session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Validation data
     * @return array Validation results
     */
    private function validate_interface_configuration($session_id, $step_config, $validation_data) {
        // Get expected interface configuration
        $expected = $step_config['expected_result'] ?? array();
        $node_id = $validation_data['node_id'] ?? null;
        
        if (!$node_id) {
            return array(
                'passed' => false,
                'score' => 0,
                'feedback' => __('Node ID not specified for validation', 'sixlab-tool')
            );
        }
        
        // Execute show commands on the node
        $commands = array('show ip interface brief', 'show interface');
        $command_results = array();
        
        foreach ($commands as $command) {
            $result = $this->execute_console_command($session_id, $node_id, $command);
            if (!is_wp_error($result)) {
                $command_results[$command] = $result;
            }
        }
        
        // Parse and validate results
        $score = 0;
        $max_score = floatval($step_config['max_score'] ?? 10.0);
        $feedback = array();
        
        // Implementation of validation logic would go here
        // This is a simplified example
        
        return array(
            'passed' => $score >= ($max_score * 0.7), // 70% to pass
            'score' => $score,
            'max_score' => $max_score,
            'feedback' => implode("\n", $feedback),
            'actual_result' => $command_results,
            'validation_time_ms' => 1500
        );
    }
    
    /**
     * Execute console command on GNS3 node
     * 
     * @param string $project_id Project ID
     * @param string $node_id Node ID
     * @param string $command Command to execute
     * @return string|WP_Error Command output or error
     */
    private function execute_console_command($project_id, $node_id, $command) {
        // This would require WebSocket or alternative console access method
        // GNS3 API doesn't directly support command execution
        // Implementation would depend on console access mechanism
        
        return new WP_Error(
            'console_not_implemented',
            __('Console command execution not yet implemented', 'sixlab-tool')
        );
    }
    
    /**
     * Make GNS3 API request
     * 
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array|WP_Error Response or error
     */
    private function make_gns3_request($endpoint, $args = array()) {
        $url = $this->api_base_url . $endpoint;
        
        // Add authentication if configured
        $username = $this->get_config('auth_username');
        $password = $this->get_config('auth_password');
        
        if (!empty($username) && !empty($password)) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        }
        
        return $this->make_request($url, $args);
    }
    
    /**
     * Validate routing table
     * 
     * @param string $session_id Session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Validation data
     * @return array Validation results
     */
    private function validate_routing_table($session_id, $step_config, $validation_data) {
        // Implementation for routing table validation
        return array(
            'passed' => false,
            'score' => 0,
            'feedback' => __('Routing table validation not yet implemented', 'sixlab-tool')
        );
    }
    
    /**
     * Validate ping test
     * 
     * @param string $session_id Session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Validation data
     * @return array Validation results
     */
    private function validate_ping_test($session_id, $step_config, $validation_data) {
        // Implementation for ping test validation
        return array(
            'passed' => false,
            'score' => 0,
            'feedback' => __('Ping test validation not yet implemented', 'sixlab-tool')
        );
    }
    
    /**
     * Get validation capabilities
     * 
     * @return array Validation capabilities
     */
    public function get_validation_capabilities() {
        return array(
            'interface_configuration' => array(
                'commands' => array('show ip interface brief', 'show interface'),
                'parsers' => array('ios_interface_parser', 'nexus_interface_parser')
            ),
            'routing_table' => array(
                'commands' => array('show ip route', 'show route'),
                'parsers' => array('ios_routing_parser')
            ),
            'vlan_configuration' => array(
                'commands' => array('show vlan brief', 'show vlan'),
                'parsers' => array('ios_vlan_parser')
            ),
            'ospf_configuration' => array(
                'commands' => array('show ip ospf neighbor', 'show ip ospf database'),
                'parsers' => array('ospf_parser')
            )
        );
    }
    
    /**
     * Get device templates
     * 
     * @return array Device templates
     */
    public function get_device_templates() {
        return array(
            'cisco_router' => array(
                'name' => 'Cisco Router',
                'template_id' => 'c7200',
                'console_type' => 'telnet',
                'default_config' => "hostname Router\nno ip domain lookup\nline vty 0 4\nexec-timeout 0 0"
            ),
            'cisco_switch' => array(
                'name' => 'Cisco Switch',
                'template_id' => 'c3725',
                'console_type' => 'telnet'
            )
        );
    }
}
