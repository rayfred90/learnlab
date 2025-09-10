<?php
/**
 * Uninstall script for 6Lab Tool
 * 
 * Fired when the plugin is uninstalled.
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin data on uninstall
 */
function sixlab_uninstall_cleanup() {
    global $wpdb;
    
    // Remove custom tables
    $tables = array(
        $wpdb->prefix . 'sixlab_sessions',
        $wpdb->prefix . 'sixlab_providers', 
        $wpdb->prefix . 'sixlab_ai_interactions',
        $wpdb->prefix . 'sixlab_validations',
        $wpdb->prefix . 'sixlab_lab_templates',
        $wpdb->prefix . 'sixlab_analytics'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Remove options
    $options = array(
        'sixlab_version',
        'sixlab_activation_time',
        'sixlab_ai_provider',
        'sixlab_ai_openai_config',
        'sixlab_ai_anthropic_config', 
        'sixlab_ai_gemini_config',
        'sixlab_completion_threshold',
        'sixlab_default_provider',
        'sixlab_session_timeout',
        'sixlab_cleanup_frequency'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Remove user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'sixlab_%'");
    
    // Remove post meta for lab templates
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'sixlab_%'");
    
    // Remove lab template posts
    $lab_templates = get_posts(array(
        'post_type' => 'sixlab_template',
        'numberposts' => -1,
        'post_status' => 'any'
    ));
    
    foreach ($lab_templates as $template) {
        wp_delete_post($template->ID, true);
    }
    
    // Clear scheduled events
    wp_clear_scheduled_hook('sixlab_cleanup_expired_sessions');
    
    // Remove custom capabilities
    $roles = array('administrator', 'group_leader');
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_sixlab');
            $role->remove_cap('edit_sixlab_templates');
            $role->remove_cap('delete_sixlab_templates');
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Only run cleanup if user has confirmed uninstall
if (get_option('sixlab_confirm_uninstall', false)) {
    sixlab_uninstall_cleanup();
}
