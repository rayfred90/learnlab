<?php
/**
 * Plugin Name: 6Lab Tool
 * Description: Multi-provider lab for learning
 * Version: 1.0.0
 * Author: Adebo
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Text Domain: sixlab-tool
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIXLAB_PLUGIN_FILE', __FILE__);
define('SIXLAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIXLAB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIXLAB_PLUGIN_VERSION', '1.0.0');
define('SIXLAB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements check
function sixlab_check_requirements() {
    $php_version = PHP_VERSION;
    $wp_version = get_bloginfo('version');
    
    if (version_compare($php_version, '8.0', '<')) {
        deactivate_plugins(SIXLAB_PLUGIN_BASENAME);
        wp_die(
            __('6Lab Tool requires PHP 8.0 or higher. You are running PHP ' . $php_version, 'sixlab-tool'),
            __('Plugin Activation Error', 'sixlab-tool'),
            array('back_link' => true)
        );
    }
    
    if (version_compare($wp_version, '6.0', '<')) {
        deactivate_plugins(SIXLAB_PLUGIN_BASENAME);
        wp_die(
            __('6Lab Tool requires WordPress 6.0 or higher. You are running WordPress ' . $wp_version, 'sixlab-tool'),
            __('Plugin Activation Error', 'sixlab-tool'),
            array('back_link' => true)
        );
    }
}

// Check requirements on activation
register_activation_hook(__FILE__, 'sixlab_check_requirements');

// Load the core plugin class
require_once SIXLAB_PLUGIN_DIR . 'includes/class-sixlab-core.php';

/**
 * Initialize the plugin
 */
function sixlab_init() {
    // Check if LearnDash is active
    if (!class_exists('SFWD_LMS')) {
        add_action('admin_notices', 'sixlab_learndash_missing_notice');
        return;
    }
    
    // Initialize the core plugin
    SixLab_Core::get_instance();
}

/**
 * Display notice if LearnDash is not active
 */
function sixlab_learndash_missing_notice() {
    $message = sprintf(
        /* translators: %s: LearnDash plugin name */
        __('6Lab Tool requires %s to be installed and activated.', 'sixlab-tool'),
        '<strong>LearnDash LMS</strong>'
    );
    
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        wp_kses_post($message)
    );
}

/**
 * Plugin activation hook
 */
function sixlab_activate() {
    sixlab_check_requirements();
    
    // Create database tables
    require_once SIXLAB_PLUGIN_DIR . 'database/class-sixlab-database.php';
    SixLab_Database::create_tables();
    
    // Run any pending migrations
    SixLab_Database::maybe_upgrade('0.8.0'); // Force upgrade from older version to run new migrations
    
    // Set default options
    add_option('sixlab_version', SIXLAB_PLUGIN_VERSION);
    add_option('sixlab_activation_time', current_time('timestamp'));
    
    // Schedule cleanup cron job
    if (!wp_next_scheduled('sixlab_cleanup_expired_sessions')) {
        wp_schedule_event(time(), 'hourly', 'sixlab_cleanup_expired_sessions');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sixlab_activate');

/**
 * Plugin deactivation hook
 */
function sixlab_deactivate() {
    // Clear scheduled cron jobs
    wp_clear_scheduled_hook('sixlab_cleanup_expired_sessions');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sixlab_deactivate');

/**
 * Initialize the plugin after all plugins are loaded
 */
add_action('plugins_loaded', 'sixlab_init');

/**
 * Load text domain for internationalization
 */
function sixlab_load_textdomain() {
    load_plugin_textdomain('sixlab-tool', false, dirname(SIXLAB_PLUGIN_BASENAME) . '/languages/');
}
add_action('init', 'sixlab_load_textdomain');

/**
 * Add action links to plugin page
 */
function sixlab_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=sixlab-settings'),
        __('Settings', 'sixlab-tool')
    );
    
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . SIXLAB_PLUGIN_BASENAME, 'sixlab_action_links');

/**
 * Cleanup expired sessions cron job
 */
function sixlab_cleanup_expired_sessions() {
    require_once SIXLAB_PLUGIN_DIR . 'includes/class-session-manager.php';
    SixLab_Session_Manager::cleanup_expired_sessions();
}
add_action('sixlab_cleanup_expired_sessions', 'sixlab_cleanup_expired_sessions');
