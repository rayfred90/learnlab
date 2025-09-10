<?php
/**
 * 6Lab Tool - AJAX Automation Handlers
 * WordPress AJAX handlers for automation system
 */

if (!defined('ABSPATH')) {
    exit;
}

class SixLab_Automation_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_sixlab_run_automation', array($this, 'handle_run_automation'));
        add_action('wp_ajax_sixlab_get_automation_status', array($this, 'handle_get_automation_status'));
        add_action('wp_ajax_sixlab_build_pipeline', array($this, 'handle_build_pipeline'));
        add_action('wp_ajax_sixlab_get_build_logs', array($this, 'handle_get_build_logs'));
    }
    
    /**
     * Handle automation script execution
     */
    public function handle_run_automation() {
        check_ajax_referer('sixlab_automation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $script_name = sanitize_key($_POST['script'] ?? '');
        $parameters = $_POST['parameters'] ?? array();
        
        if (empty($script_name)) {
            wp_send_json_error(array('message' => 'Script name is required'));
        }
        
        // Sanitize parameters
        $parameters = array_map('sanitize_text_field', $parameters);
        
        $automation_manager = new SixLab_Automation_Manager();
        $result = $automation_manager->run_automation_script($script_name, $parameters);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle automation status check
     */
    public function handle_get_automation_status() {
        check_ajax_referer('sixlab_automation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $process_id = sanitize_text_field($_POST['process_id'] ?? '');
        
        if (empty($process_id)) {
            wp_send_json_error(array('message' => 'Process ID is required'));
        }
        
        // Get process status from transient
        $status = get_transient("sixlab_automation_status_{$process_id}");
        
        if ($status === false) {
            wp_send_json_error(array('message' => 'Process not found or completed'));
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * Handle build pipeline execution
     */
    public function handle_build_pipeline() {
        check_ajax_referer('sixlab_automation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $environment = sanitize_key($_POST['environment'] ?? 'development');
        
        $automation_manager = new SixLab_Automation_Manager();
        $result = $automation_manager->run_build_pipeline($environment);
        
        // Log build result
        $this->log_build_result($environment, $result);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle build logs request
     */
    public function handle_get_build_logs() {
        check_ajax_referer('sixlab_automation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $environment = sanitize_key($_POST['environment'] ?? 'development');
        $limit = intval($_POST['limit'] ?? 10);
        
        $logs = $this->get_build_logs($environment, $limit);
        
        wp_send_json_success(array(
            'logs' => $logs,
            'total' => count($logs)
        ));
    }
    
    /**
     * Log build result
     * 
     * @param string $environment
     * @param array $result
     */
    private function log_build_result($environment, $result) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'environment' => $environment,
            'success' => $result['success'],
            'message' => $result['message'],
            'details' => $result['details'] ?? array(),
            'user_id' => get_current_user_id()
        );
        
        $logs = get_option('sixlab_build_logs', array());
        array_unshift($logs, $log_entry);
        
        // Keep only last 50 logs
        $logs = array_slice($logs, 0, 50);
        
        update_option('sixlab_build_logs', $logs);
    }
    
    /**
     * Get build logs
     * 
     * @param string $environment
     * @param int $limit
     * @return array
     */
    private function get_build_logs($environment = '', $limit = 10) {
        $logs = get_option('sixlab_build_logs', array());
        
        if (!empty($environment)) {
            $logs = array_filter($logs, function($log) use ($environment) {
                return $log['environment'] === $environment;
            });
        }
        
        return array_slice($logs, 0, $limit);
    }
}

// Initialize AJAX handlers
new SixLab_Automation_Ajax();
