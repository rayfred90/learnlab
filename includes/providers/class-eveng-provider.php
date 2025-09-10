<?php
/**
 * EVE-NG Provider Class
 * 
 * EVE-NG network emulation provider implementation
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EVE-NG Provider Class
 */
class EVENG_Provider extends SixLab_Lab_Provider_Abstract {
    
    /**
     * Supported features
     * @var array
     */
    protected $supported_features = array(
        'network_topology',
        'console_access',
        'multi_vendor_support',
        'wireshark_integration',
        'configuration_backup',
        'collaborative_labs',
        'real_time_validation'
    );
    
    /**
     * EVE-NG API base URL
     * @var string
     */
    private $api_base_url;
    
    /**
     * Authentication cookie
     * @var string
     */
    private $auth_cookie;
    
    /**
     * Initialize EVE-NG provider
     */
    protected function init() {
        $this->api_base_url = rtrim($this->get_config('server_url', 'https://localhost'), '/') . '/api';
    }
    
    /**
     * Get provider type identifier
     * 
     * @return string Provider type
     */
    public function get_type() {
        return 'eveng';
    }
    
    /**
     * Get provider display name
     * 
     * @return string Display name
     */
    public function get_display_name() {
        return __('EVE-NG Network Emulator', 'sixlab-tool');
    }
    
    /**
     * Get provider description
     * 
     * @return string Description
     */
    public function get_description() {
        return __('Enterprise network emulation with multi-vendor support and advanced collaboration features', 'sixlab-tool');
    }
    
    /**
     * Get default configuration
     * 
     * @return array Default configuration
     */
    public function get_default_config() {
        return array(
            'server_url' => 'https://your-eve-server',
            'username' => 'admin',
            'password' => '',
            'lab_template_path' => '/opt/unetlab/labs/templates',
            'enable_wireshark' => true,
            'max_lab_size' => 20,
            'auto_cleanup_minutes' => 180,
            'max_concurrent_sessions' => 30,
            'enable_telnet_console' => true,
            'enable_vnc_console' => true,
            'default_lab_folder' => 'sixlab'
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
                'label' => __('EVE-NG Server URL', 'sixlab-tool'),
                'default' => 'https://your-eve-server',
                'required' => true
            ),
            'username' => array(
                'type' => 'text',
                'label' => __('Admin Username', 'sixlab-tool'),
                'required' => true,
                'default' => 'admin'
            ),
            'password' => array(
                'type' => 'password',
                'label' => __('Admin Password', 'sixlab-tool'),
                'required' => true
            ),
            'lab_template_path' => array(
                'type' => 'text',
                'label' => __('Lab Templates Path', 'sixlab-tool'),
                'default' => '/opt/unetlab/labs/templates',
                'description' => __('Server path to EVE-NG lab templates', 'sixlab-tool')
            ),
            'enable_wireshark' => array(
                'type' => 'checkbox',
                'label' => __('Enable Wireshark Integration', 'sixlab-tool'),
                'default' => true
            ),
            'max_lab_size' => array(
                'type' => 'number',
                'label' => __('Maximum Lab Size (nodes)', 'sixlab-tool'),
                'default' => 20,
                'min' => 5,
                'max' => 100
            ),
            'auto_cleanup_minutes' => array(
                'type' => 'number',
                'label' => __('Auto Cleanup (minutes)', 'sixlab-tool'),
                'description' => __('Automatically cleanup inactive sessions after specified minutes', 'sixlab-tool'),
                'default' => 180,
                'min' => 30,
                'max' => 1440
            ),
            'max_concurrent_sessions' => array(
                'type' => 'number',
                'label' => __('Max Concurrent Sessions', 'sixlab-tool'),
                'description' => __('Maximum number of concurrent lab sessions', 'sixlab-tool'),
                'default' => 30,
                'min' => 1,
                'max' => 100
            ),
            'enable_telnet_console' => array(
                'type' => 'checkbox',
                'label' => __('Enable Telnet Console Access', 'sixlab-tool'),
                'description' => __('Allow Telnet access to device consoles', 'sixlab-tool'),
                'default' => true
            ),
            'enable_vnc_console' => array(
                'type' => 'checkbox',
                'label' => __('Enable VNC Console Access', 'sixlab-tool'),
                'description' => __('Allow VNC access to device consoles', 'sixlab-tool'),
                'default' => true
            ),
            'default_lab_folder' => array(
                'type' => 'text',
                'label' => __('Default Lab Folder', 'sixlab-tool'),
                'description' => __('Default folder for organizing lab sessions', 'sixlab-tool'),
                'default' => 'sixlab'
            )
        );
    }
    
    /**
     * Test provider connection
     * 
     * @return array Test results with 'success' and 'message' keys
     */
    public function test_connection() {
        // Authenticate first
        $auth_result = $this->authenticate();
        
        if (is_wp_error($auth_result)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to authenticate with EVE-NG: %s', 'sixlab-tool'),
                    $auth_result->get_error_message()
                )
            );
        }
        
        // Test system status
        $response = $this->make_eveng_request('/status');
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to get EVE-NG status: %s', 'sixlab-tool'),
                    $response->get_error_message()
                )
            );
        }
        
        $status_data = json_decode($response['body'], true);
        
        if (!$status_data) {
            return array(
                'success' => false,
                'message' => __('Invalid response from EVE-NG API', 'sixlab-tool')
            );
        }
        
        return array(
            'success' => true,
            'message' => sprintf(
                __('Successfully connected to EVE-NG server. Status: %s', 'sixlab-tool'),
                $status_data['message'] ?? 'OK'
            ),
            'server_info' => $status_data
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
        // Authenticate first
        $auth_result = $this->authenticate();
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }
        
        // Generate unique lab name
        $lab_name = 'sixlab_' . $user_id . '_' . time() . '_' . wp_generate_password(8, false);
        
        // Create lab folder if it doesn't exist
        $folder_path = '/' . $this->get_config('default_lab_folder');
        $this->ensure_folder_exists($folder_path);
        
        // Create the lab
        $lab_data = array(
            'name' => $lab_name,
            'description' => sprintf(__('Lab session for user %d', 'sixlab-tool'), $user_id),
            'body' => $this->generate_lab_body($template_data),
            'path' => $folder_path
        );
        
        $response = $this->make_eveng_request('/labs', array(
            'method' => 'POST',
            'body' => wp_json_encode($lab_data)
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'eveng_lab_creation_failed',
                sprintf(__('Failed to create EVE-NG lab: %s', 'sixlab-tool'), $response->get_error_message())
            );
        }
        
        $lab_response = json_decode($response['body'], true);
        
        if (!$lab_response || !isset($lab_response['status'])) {
            return new WP_Error(
                'eveng_invalid_lab_response',
                __('Invalid lab creation response from EVE-NG', 'sixlab-tool')
            );
        }
        
        $lab_id = $lab_response['data']['id'] ?? $lab_name;
        
        // Load template nodes and networks if provided
        if (isset($template_data['eveng_template']) && !empty($template_data['eveng_template'])) {
            $template_result = $this->load_lab_template($folder_path, $lab_name, $template_data['eveng_template']);
            
            if (is_wp_error($template_result)) {
                // Clean up lab if template loading fails
                $this->delete_lab($folder_path, $lab_name);
                return $template_result;
            }
        }
        
        // Generate access URL
        $access_url = $this->get_config('server_url') . '/lab' . $folder_path . '/' . urlencode($lab_name) . '.unl';
        
        $session_data = array(
            'session_id' => $lab_id,
            'lab_name' => $lab_name,
            'lab_path' => $folder_path . '/' . $lab_name . '.unl',
            'access_url' => $access_url,
            'lab_data' => $lab_response,
            'created_at' => current_time('mysql')
        );
        
        $this->log('session_created', array(
            'lab_id' => $lab_id,
            'lab_name' => $lab_name,
            'lab_path' => $folder_path . '/' . $lab_name . '.unl',
            'user_id' => $user_id
        ));
        
        return $session_data;
    }
    
    /**
     * Get session details
     * 
     * @param string $session_id Provider session ID (lab path)
     * @return array|WP_Error Session details or error
     */
    public function get_session($session_id) {
        $auth_result = $this->authenticate();
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }
        
        // Extract folder and lab name from session_id (lab path)
        $path_parts = explode('/', trim($session_id, '/'));
        $lab_file = array_pop($path_parts);
        $folder_path = '/' . implode('/', $path_parts);
        $lab_name = str_replace('.unl', '', $lab_file);
        
        // Get lab information
        $response = $this->make_eveng_request("/labs{$folder_path}/{$lab_name}.unl");
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $lab_data = json_decode($response['body'], true);
        
        if (!$lab_data) {
            return new WP_Error('eveng_lab_not_found', __('EVE-NG lab not found', 'sixlab-tool'));
        }
        
        // Get lab topology (nodes and networks)
        $topology_response = $this->make_eveng_request("/labs{$folder_path}/{$lab_name}.unl/topology");
        $topology = array();
        
        if (!is_wp_error($topology_response)) {
            $topology = json_decode($topology_response['body'], true) ?: array();
        }
        
        return array(
            'lab_id' => $session_id,
            'lab_name' => $lab_name,
            'lab_path' => $folder_path . '/' . $lab_name . '.unl',
            'lab_info' => $lab_data['data'] ?? array(),
            'topology' => $topology,
            'access_url' => $this->get_config('server_url') . '/lab' . $folder_path . '/' . urlencode($lab_name) . '.unl'
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
        $auth_result = $this->authenticate();
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }
        
        // Implementation would depend on what configuration updates are needed
        // For example, updating lab description, adding/removing nodes, etc.
        
        $this->log('session_updated', array(
            'lab_id' => $session_id,
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
            case 'node_configuration':
                return $this->validate_node_configuration($session_id, $step_config, $validation_data);
                
            case 'network_connectivity':
                return $this->validate_network_connectivity($session_id, $step_config, $validation_data);
                
            case 'routing_protocol':
                return $this->validate_routing_protocol($session_id, $step_config, $validation_data);
                
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
        $auth_result = $this->authenticate();
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }
        
        // Extract folder and lab name from session_id
        $path_parts = explode('/', trim($session_id, '/'));
        $lab_file = array_pop($path_parts);
        $folder_path = '/' . implode('/', $path_parts);
        $lab_name = str_replace('.unl', '', $lab_file);
        
        // Stop all nodes first
        $stop_response = $this->make_eveng_request("/labs{$folder_path}/{$lab_name}.unl/nodes/stop", array(
            'method' => 'GET'
        ));
        
        if (is_wp_error($stop_response)) {
            $this->log('session_stop_failed', array(
                'lab_id' => $session_id,
                'error' => $stop_response->get_error_message()
            ), 'warning');
        }
        
        // Wait a moment for nodes to stop
        sleep(3);
        
        // Delete the lab
        $delete_response = $this->delete_lab($folder_path, $lab_name);
        
        if (is_wp_error($delete_response)) {
            return $delete_response;
        }
        
        $this->log('session_destroyed', array('lab_id' => $session_id));
        
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
        // Extract folder and lab name from session_id
        $path_parts = explode('/', trim($session_id, '/'));
        $lab_file = array_pop($path_parts);
        $folder_path = '/' . implode('/', $path_parts);
        $lab_name = str_replace('.unl', '', $lab_file);
        
        return $this->get_config('server_url') . '/lab' . $folder_path . '/' . urlencode($lab_name) . '.unl';
    }
    
    /**
     * Authenticate with EVE-NG
     * 
     * @return bool|WP_Error Success or error
     */
    private function authenticate() {
        // Check if we already have a valid cookie
        if (!empty($this->auth_cookie)) {
            return true;
        }
        
        $auth_data = array(
            'username' => $this->get_config('username'),
            'password' => $this->get_config('password'),
            'html5' => '-1'
        );
        
        $response = wp_remote_post($this->api_base_url . '/auth/login', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($auth_data),
            'timeout' => 30,
            'sslverify' => false // EVE-NG often uses self-signed certificates
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'eveng_auth_failed',
                sprintf(__('Authentication request failed: %s', 'sixlab-tool'), $response->get_error_message())
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error(
                'eveng_auth_rejected',
                sprintf(__('Authentication failed with status %d', 'sixlab-tool'), $status_code)
            );
        }
        
        // Extract authentication cookie
        $cookies = wp_remote_retrieve_header($response, 'set-cookie');
        if (empty($cookies)) {
            return new WP_Error(
                'eveng_no_auth_cookie',
                __('No authentication cookie received', 'sixlab-tool')
            );
        }
        
        // Store the authentication cookie
        if (is_array($cookies)) {
            $this->auth_cookie = implode('; ', $cookies);
        } else {
            $this->auth_cookie = $cookies;
        }
        
        return true;
    }
    
    /**
     * Make EVE-NG API request
     * 
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array|WP_Error Response or error
     */
    private function make_eveng_request($endpoint, $args = array()) {
        $url = $this->api_base_url . $endpoint;
        
        // Set default headers
        if (!isset($args['headers'])) {
            $args['headers'] = array();
        }
        
        if (!isset($args['headers']['Content-Type']) && isset($args['body'])) {
            $args['headers']['Content-Type'] = 'application/json';
        }
        
        // Add authentication cookie
        if (!empty($this->auth_cookie)) {
            $args['headers']['Cookie'] = $this->auth_cookie;
        }
        
        // Disable SSL verification for self-signed certificates
        $args['sslverify'] = false;
        
        return $this->make_request($url, $args);
    }
    
    /**
     * Ensure folder exists
     * 
     * @param string $folder_path Folder path
     * @return bool|WP_Error Success or error
     */
    private function ensure_folder_exists($folder_path) {
        $response = $this->make_eveng_request('/folders' . $folder_path);
        
        if (!is_wp_error($response)) {
            return true; // Folder exists
        }
        
        // Create folder
        $folder_data = array(
            'name' => basename($folder_path),
            'path' => dirname($folder_path)
        );
        
        $create_response = $this->make_eveng_request('/folders', array(
            'method' => 'POST',
            'body' => wp_json_encode($folder_data)
        ));
        
        return !is_wp_error($create_response);
    }
    
    /**
     * Generate lab body from template
     * 
     * @param array $template_data Template data
     * @return string Lab body content
     */
    private function generate_lab_body($template_data) {
        // This would contain the UNL XML structure for the lab
        // For now, return a basic lab structure
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<lab name="' . esc_xml($template_data['name'] ?? 'Lab') . '" id="' . wp_generate_password(8, false) . '" version="1" scripttimeout="300" lock="0">
  <description>' . esc_xml($template_data['description'] ?? 'Generated lab') . '</description>
  <body>' . esc_xml($template_data['instructions'] ?? 'Lab instructions') . '</body>
</lab>';
    }
    
    /**
     * Load lab template
     * 
     * @param string $folder_path Folder path
     * @param string $lab_name Lab name
     * @param string $template_path Template file path
     * @return bool|WP_Error Success or error
     */
    private function load_lab_template($folder_path, $lab_name, $template_path) {
        // Implementation would depend on EVE-NG template format
        // This is a simplified version
        
        if (!file_exists($template_path)) {
            return new WP_Error(
                'template_not_found',
                sprintf(__('Template file not found: %s', 'sixlab-tool'), $template_path)
            );
        }
        
        // For now, just log that template loading was attempted
        $this->log('template_loaded', array(
            'template_path' => $template_path,
            'lab_name' => $lab_name
        ));
        
        return true;
    }
    
    /**
     * Delete lab
     * 
     * @param string $folder_path Folder path
     * @param string $lab_name Lab name
     * @return bool|WP_Error Success or error
     */
    private function delete_lab($folder_path, $lab_name) {
        $response = $this->make_eveng_request("/labs{$folder_path}/{$lab_name}.unl", array(
            'method' => 'DELETE'
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'eveng_lab_deletion_failed',
                sprintf(__('Failed to delete EVE-NG lab: %s', 'sixlab-tool'), $response->get_error_message())
            );
        }
        
        return true;
    }
    
    /**
     * Validate node configuration
     * 
     * @param string $session_id Session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Validation data
     * @return array Validation results
     */
    private function validate_node_configuration($session_id, $step_config, $validation_data) {
        // Implementation for node configuration validation
        return array(
            'passed' => false,
            'score' => 0,
            'feedback' => __('Node configuration validation not yet implemented', 'sixlab-tool')
        );
    }
    
    /**
     * Validate network connectivity
     * 
     * @param string $session_id Session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Validation data
     * @return array Validation results
     */
    private function validate_network_connectivity($session_id, $step_config, $validation_data) {
        // Implementation for network connectivity validation
        return array(
            'passed' => false,
            'score' => 0,
            'feedback' => __('Network connectivity validation not yet implemented', 'sixlab-tool')
        );
    }
    
    /**
     * Validate routing protocol
     * 
     * @param string $session_id Session ID
     * @param array $step_config Step configuration
     * @param array $validation_data Validation data
     * @return array Validation results
     */
    private function validate_routing_protocol($session_id, $step_config, $validation_data) {
        // Implementation for routing protocol validation
        return array(
            'passed' => false,
            'score' => 0,
            'feedback' => __('Routing protocol validation not yet implemented', 'sixlab-tool')
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
                'image' => 'vios-adventerprisek9-m',
                'type' => 'qemu',
                'ethernet' => 4,
                'serial' => 2,
                'ram' => 512,
                'cpulimit' => 1,
                'config' => array(
                    'startup_config' => 'router_basic.cfg',
                    'interfaces' => array('GigabitEthernet0/0', 'GigabitEthernet0/1', 'GigabitEthernet0/2', 'GigabitEthernet0/3')
                )
            ),
            'cisco_switch' => array(
                'name' => 'Cisco Switch',
                'image' => 'viosl2-adventerprisek9-m',
                'type' => 'qemu',
                'ethernet' => 16,
                'serial' => 1,
                'ram' => 768,
                'cpulimit' => 1,
                'config' => array(
                    'startup_config' => 'switch_basic.cfg',
                    'interfaces' => array_map(function($i) { return "GigabitEthernet0/$i"; }, range(0, 15))
                )
            ),
            'linux_host' => array(
                'name' => 'Linux Host',
                'image' => 'linux-ubuntu-server',
                'type' => 'qemu',
                'ethernet' => 1,
                'ram' => 1024,
                'cpulimit' => 1,
                'config' => array(
                    'startup_config' => 'host_basic.cfg',
                    'interfaces' => array('eth0')
                )
            )
        );
    }
    
    /**
     * Get validation capabilities
     *
     * @return array Validation capabilities
     */
    public function get_validation_capabilities() {
        return array(
            'interface_validation' => array(
                'commands' => array('show ip interface brief', 'show interfaces'),
                'parsers' => array('parse_interface_status', 'parse_ip_addresses')
            ),
            'routing_validation' => array(
                'commands' => array('show ip route', 'show ip protocols'),
                'parsers' => array('parse_routing_table', 'parse_routing_protocols')
            ),
            'vlan_validation' => array(
                'commands' => array('show vlan brief', 'show spanning-tree'),
                'parsers' => array('parse_vlan_config', 'parse_stp_status')
            ),
            'protocol_validation' => array(
                'commands' => array('show running-config', 'show ip ospf neighbor'),
                'parsers' => array('parse_running_config', 'parse_ospf_neighbors')
            )
        );
    }
}
