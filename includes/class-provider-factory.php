<?php
/**
 * Provider Factory Class
 * 
 * Handles creation and management of lab providers
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SixLab Provider Factory Class
 */
class SixLab_Provider_Factory {
    
    /**
     * Available provider types
     * @var array
     */
    private static $provider_types = array(
        'gns3' => 'GNS3_Provider',
        'guacamole' => 'Guacamole_Provider', 
        'eveng' => 'EVENG_Provider',
        'custom' => 'SixLab_Custom_Provider'
    );
    
    /**
     * Cached provider instances
     * @var array
     */
    private $provider_instances = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_default_providers'));
    }
    
    /**
     * Register default providers in database
     */
    public function register_default_providers() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        // Check if GNS3 provider exists
        $gns3_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE type = %s",
            'gns3'
        ));
        
        if (!$gns3_exists) {
            $this->create_default_gns3_provider();
        }
    }
    
    /**
     * Create a provider instance
     * 
     * @param string $provider_type Provider type (gns3, guacamole, eveng, custom)
     * @param array $config Provider configuration
     * @return SixLab_Lab_Provider_Abstract|WP_Error
     */
    public function create_provider($provider_type, $config = array()) {
        if (!isset(self::$provider_types[$provider_type])) {
            return new WP_Error(
                'invalid_provider_type',
                sprintf(__('Invalid provider type: %s', 'sixlab-tool'), $provider_type)
            );
        }
        
        $provider_class = self::$provider_types[$provider_type];
        
        if (!class_exists($provider_class)) {
            return new WP_Error(
                'provider_class_not_found',
                sprintf(__('Provider class not found: %s', 'sixlab-tool'), $provider_class)
            );
        }
        
        try {
            $provider = new $provider_class($config);
            
            if (!($provider instanceof SixLab_Lab_Provider_Abstract)) {
                return new WP_Error(
                    'invalid_provider_instance',
                    __('Provider must extend SixLab_Lab_Provider_Abstract', 'sixlab-tool')
                );
            }
            
            return $provider;
            
        } catch (Exception $e) {
            return new WP_Error(
                'provider_creation_failed',
                sprintf(__('Failed to create provider: %s', 'sixlab-tool'), $e->getMessage())
            );
        }
    }
    
    /**
     * Get provider instance by ID
     * 
     * @param int $provider_id Provider database ID
     * @return SixLab_Lab_Provider_Abstract|WP_Error
     */
    public function get_provider($provider_id) {
        // Check cache first
        if (isset($this->provider_instances[$provider_id])) {
            return $this->provider_instances[$provider_id];
        }
        
        $provider_data = $this->get_provider_data($provider_id);
        
        if (!$provider_data) {
            return new WP_Error(
                'provider_not_found',
                sprintf(__('Provider not found with ID: %d', 'sixlab-tool'), $provider_id)
            );
        }
        
        $config = json_decode($provider_data->config, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'invalid_provider_config',
                __('Provider configuration is invalid JSON', 'sixlab-tool')
            );
        }
        
        $provider = $this->create_provider($provider_data->type, $config);
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        // Set provider metadata
        $provider->set_id($provider_id);
        $provider->set_name($provider_data->name);
        $provider->set_display_name($provider_data->display_name);
        
        // Cache the instance
        $this->provider_instances[$provider_id] = $provider;
        
        return $provider;
    }
    
    /**
     * Get default provider
     * 
     * @return SixLab_Lab_Provider_Abstract|WP_Error
     */
    public function get_default_provider() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $provider_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE is_default = %d AND is_active = %d LIMIT 1",
            1, 1
        ));
        
        if (!$provider_data) {
            // Fallback to first active provider
            $provider_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE is_active = %d ORDER BY id ASC LIMIT 1",
                1
            ));
        }
        
        if (!$provider_data) {
            return new WP_Error(
                'no_active_providers',
                __('No active providers found', 'sixlab-tool')
            );
        }
        
        return $this->get_provider($provider_data->id);
    }
    
    /**
     * Get provider by type
     * 
     * @param string $provider_type Provider type
     * @return SixLab_Lab_Provider_Abstract|WP_Error
     */
    public function get_provider_by_type($provider_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        // Get the default provider for this type first
        $provider_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE type = %s AND is_active = %d AND is_default = %d LIMIT 1",
            $provider_type, 1, 1
        ));
        
        // If no default, get the first active one
        if (!$provider_data) {
            $provider_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE type = %s AND is_active = %d ORDER BY id ASC LIMIT 1",
                $provider_type, 1
            ));
        }
        
        if (!$provider_data) {
            return new WP_Error(
                'provider_type_not_found',
                sprintf(__('No active provider found for type: %s', 'sixlab-tool'), $provider_type)
            );
        }
        
        return $this->get_provider($provider_data->id);
    }
    
    /**
     * Get all providers of a specific type
     * 
     * @param string $provider_type Provider type
     * @param bool $active_only Whether to return only active providers
     * @return array Array of provider instances
     */
    public function get_providers_by_type($provider_type, $active_only = true) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $where_clause = "WHERE type = %s";
        $params = array($provider_type);
        
        if ($active_only) {
            $where_clause .= " AND is_active = %d";
            $params[] = 1;
        }
        
        $providers_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} {$where_clause} ORDER BY is_default DESC, display_name ASC",
            $params
        ));
        
        $provider_instances = array();
        
        foreach ($providers_data as $provider_data) {
            $provider = $this->get_provider($provider_data->id);
            
            if (!is_wp_error($provider)) {
                $provider_instances[] = $provider;
            }
        }
        
        return $provider_instances;
    }
    
    /**
     * Get all available providers
     * 
     * @param bool $active_only Whether to return only active providers
     * @return array
     */
    public function get_all_providers($active_only = true) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $where_clause = $active_only ? 'WHERE is_active = 1' : '';
        
        $providers = $wpdb->get_results("SELECT * FROM {$table_name} {$where_clause} ORDER BY display_name ASC");
        
        $provider_instances = array();
        
        foreach ($providers as $provider_data) {
            $provider = $this->get_provider($provider_data->id);
            
            if (!is_wp_error($provider)) {
                $provider_instances[] = $provider;
            }
        }
        
        return $provider_instances;
    }
    
    /**
     * Register a new provider type
     * 
     * @param string $type Provider type identifier
     * @param string $class_name Provider class name
     * @return bool
     */
    public static function register_provider_type($type, $class_name) {
        if (isset(self::$provider_types[$type])) {
            return false; // Type already registered
        }
        
        self::$provider_types[$type] = $class_name;
        return true;
    }
    
    /**
     * Get registered provider types
     * 
     * @return array
     */
    public static function get_provider_types() {
        return self::$provider_types;
    }
    
    /**
     * Save provider configuration
     * 
     * @param string $name Provider name
     * @param string $type Provider type
     * @param string $display_name Provider display name
     * @param array $config Provider configuration
     * @param bool $is_active Whether provider is active
     * @param bool $is_default Whether this is the default provider
     * @return int|WP_Error Provider ID or error
     */
    public function save_provider($name, $type, $display_name, $config, $is_active = true, $is_default = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        // Validate provider type
        if (!isset(self::$provider_types[$type])) {
            return new WP_Error(
                'invalid_provider_type',
                sprintf(__('Invalid provider type: %s', 'sixlab-tool'), $type)
            );
        }
        
        // Validate configuration by creating a test instance
        $test_provider = $this->create_provider($type, $config);
        if (is_wp_error($test_provider)) {
            return $test_provider;
        }
        
        // If setting as default, unset other defaults
        if ($is_default) {
            $wpdb->update(
                $table_name,
                array('is_default' => 0),
                array('is_default' => 1)
            );
        }
        
        $config_json = wp_json_encode($config);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'type' => $type,
                'display_name' => $display_name,
                'config' => $config_json,
                'is_active' => $is_active ? 1 : 0,
                'is_default' => $is_default ? 1 : 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error(
                'provider_save_failed',
                __('Failed to save provider configuration', 'sixlab-tool')
            );
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update provider configuration
     * 
     * @param int $provider_id Provider ID
     * @param array $data Updated data
     * @return bool|WP_Error
     */
    public function update_provider($provider_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        // Remove cached instance
        unset($this->provider_instances[$provider_id]);
        
        $update_data = array();
        $update_format = array();
        
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'name':
                case 'display_name':
                    $update_data[$key] = $value;
                    $update_format[] = '%s';
                    break;
                case 'config':
                    $update_data[$key] = wp_json_encode($value);
                    $update_format[] = '%s';
                    break;
                case 'is_active':
                case 'is_default':
                    $update_data[$key] = $value ? 1 : 0;
                    $update_format[] = '%d';
                    break;
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_update_data', __('No valid update data provided', 'sixlab-tool'));
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $provider_id),
            $update_format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete provider
     * 
     * @param int $provider_id Provider ID
     * @return bool|WP_Error
     */
    public function delete_provider($provider_id) {
        global $wpdb;
        
        // Check if provider has active sessions
        $active_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions WHERE provider_id = %d AND status = 'active'",
            $provider_id
        ));
        
        if ($active_sessions > 0) {
            return new WP_Error(
                'provider_has_active_sessions',
                __('Cannot delete provider with active sessions', 'sixlab-tool')
            );
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'sixlab_providers',
            array('id' => $provider_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error(
                'provider_delete_failed',
                __('Failed to delete provider', 'sixlab-tool')
            );
        }
        
        // Remove from cache
        unset($this->provider_instances[$provider_id]);
        
        return true;
    }
    
    /**
     * Test provider connection
     * 
     * @param int $provider_id Provider ID
     * @return array|WP_Error Test results
     */
    public function test_provider($provider_id) {
        $provider = $this->get_provider($provider_id);
        
        if (is_wp_error($provider)) {
            return $provider;
        }
        
        $test_results = $provider->test_connection();
        
        // Update health status in database
        $this->update_provider_health($provider_id, $test_results);
        
        return $test_results;
    }
    
    /**
     * Update provider health status
     * 
     * @param int $provider_id Provider ID
     * @param array $test_results Test results
     */
    private function update_provider_health($provider_id, $test_results) {
        global $wpdb;
        
        $health_status = $test_results['success'] ? 'healthy' : 'error';
        $health_message = $test_results['message'];
        
        $wpdb->update(
            $wpdb->prefix . 'sixlab_providers',
            array(
                'health_status' => $health_status,
                'health_message' => $health_message,
                'last_health_check' => current_time('mysql')
            ),
            array('id' => $provider_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get provider data from database
     * 
     * @param int $provider_id Provider ID
     * @return object|null
     */
    private function get_provider_data($provider_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sixlab_providers WHERE id = %d",
            $provider_id
        ));
    }
    
    /**
     * Create default GNS3 provider
     */
    private function create_default_gns3_provider() {
        $default_config = array(
            'server_url' => 'http://localhost:3080',
            'web_gui_url' => 'http://localhost:3080',
            'templates_path' => '/opt/gns3/templates',
            'auto_cleanup_minutes' => 120
        );
        
        $this->save_provider(
            'gns3_default',
            'gns3',
            'GNS3 Default Server',
            $default_config,
            true,
            true
        );
    }
    
    /**
     * Get available provider types
     * 
     * @return array Array of provider types and their class names
     */
    public function get_available_providers() {
        return self::$provider_types;
    }
}
