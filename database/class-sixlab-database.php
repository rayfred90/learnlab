<?php
/**
 * Database Management Class
 * 
 * Handles database table creation and migrations
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SixLab Database Class
 */
class SixLab_Database {
    
    /**
     * Database version
     * @var string
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sessions table
        self::create_sessions_table($charset_collate);
        
        // Providers table
        self::create_providers_table($charset_collate);
        
        // AI Interactions table
        self::create_ai_interactions_table($charset_collate);
        
        // Validations table
        self::create_validations_table($charset_collate);
        
        // Lab Templates table
        self::create_lab_templates_table($charset_collate);
        
        // Analytics table
        self::create_analytics_table($charset_collate);
        
        // Update database version
        update_option('sixlab_db_version', self::DB_VERSION);
        
        // Insert default data
        self::insert_default_data();
    }
    
    /**
     * Create sessions table
     */
    private static function create_sessions_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_sessions';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            lab_id bigint(20) UNSIGNED NOT NULL,
            provider varchar(50) NOT NULL,
            provider_session_id varchar(255) NOT NULL,
            session_data longtext,
            ai_context longtext,
            current_step int(11) DEFAULT 1,
            total_steps int(11) DEFAULT 1,
            status enum('active','paused','completed','expired','error') DEFAULT 'active',
            score decimal(5,2) DEFAULT NULL,
            max_score decimal(5,2) DEFAULT 100.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY lab_id (lab_id),
            KEY provider (provider),
            KEY status (status),
            KEY expires_at (expires_at),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create providers table
     */
    private static function create_providers_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_providers';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            type varchar(50) NOT NULL,
            display_name varchar(100) NOT NULL,
            config longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            supported_features longtext,
            last_health_check datetime DEFAULT NULL,
            health_status enum('healthy','warning','error','unknown') DEFAULT 'unknown',
            health_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY type (type),
            KEY is_active (is_active),
            KEY is_default (is_default)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create AI interactions table
     */
    private static function create_ai_interactions_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_ai_interactions';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            ai_provider varchar(50) NOT NULL,
            interaction_type enum('contextual_help','configuration_analysis','chat','error_explanation','hint_request') NOT NULL,
            request_data longtext,
            response_data longtext,
            tokens_used int(11) DEFAULT 0,
            response_time_ms int(11) DEFAULT NULL,
            user_rating tinyint(1) DEFAULT NULL,
            cost_usd decimal(10,6) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY ai_provider (ai_provider),
            KEY interaction_type (interaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create validations table
     */
    private static function create_validations_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_validations';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id bigint(20) UNSIGNED NOT NULL,
            step int(11) NOT NULL,
            validation_type varchar(50) NOT NULL,
            validation_data longtext,
            expected_result longtext,
            actual_result longtext,
            score decimal(5,2) NOT NULL DEFAULT 0.00,
            max_score decimal(5,2) NOT NULL DEFAULT 100.00,
            passed tinyint(1) DEFAULT 0,
            feedback longtext,
            ai_analysis longtext,
            validation_time_ms int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY step (step),
            KEY passed (passed),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create lab templates table
     */
    private static function create_lab_templates_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            description text,
            provider_type varchar(50) NOT NULL,
            difficulty_level enum('beginner','intermediate','advanced') DEFAULT 'beginner',
            estimated_duration int(11) DEFAULT NULL,
            template_data longtext NOT NULL,
            validation_rules longtext,
            instructions longtext,
            prerequisites longtext,
            learning_objectives longtext,
            tags varchar(500) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_featured tinyint(1) DEFAULT 0,
            author_id bigint(20) UNSIGNED NOT NULL,
            usage_count int(11) DEFAULT 0,
            average_score decimal(4,2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY provider_type (provider_type),
            KEY difficulty_level (difficulty_level),
            KEY is_active (is_active),
            KEY is_featured (is_featured),
            KEY author_id (author_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create analytics table
     */
    private static function create_analytics_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_analytics';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id bigint(20) UNSIGNED DEFAULT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            event_type varchar(100) NOT NULL,
            event_category varchar(50) NOT NULL,
            event_data longtext,
            user_agent text,
            ip_address varchar(45),
            referrer text,
            page_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY event_category (event_category),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Insert default data
     */
    private static function insert_default_data() {
        global $wpdb;
        
        // Insert default GNS3 provider
        $providers_table = $wpdb->prefix . 'sixlab_providers';
        
        $default_gns3_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$providers_table} WHERE type = %s",
            'gns3'
        ));
        
        if (!$default_gns3_exists) {
            $default_gns3_config = array(
                'server_url' => 'http://localhost:3080',
                'web_gui_url' => 'http://localhost:3080',
                'templates_path' => '/opt/gns3/templates',
                'auto_cleanup_minutes' => 120
            );
            
            $supported_features = array(
                'network_topology',
                'console_access',
                'real_time_validation',
                'snapshot_support',
                'configuration_backup'
            );
            
            $wpdb->insert(
                $providers_table,
                array(
                    'name' => 'gns3_default',
                    'type' => 'gns3',
                    'display_name' => 'GNS3 Default Server',
                    'config' => wp_json_encode($default_gns3_config),
                    'is_active' => 1,
                    'is_default' => 1,
                    'supported_features' => wp_json_encode($supported_features),
                    'health_status' => 'unknown',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
            );
        }
        
        // Set default options
        add_option('sixlab_completion_threshold', 70);
        add_option('sixlab_session_timeout', 4);
        add_option('sixlab_cleanup_frequency', 'hourly');
        add_option('sixlab_ai_provider', 'openrouter'); // Changed to OpenRouter as default
        
        // Insert AI provider configurations
        self::insert_default_ai_providers();
    }
    
    /**
     * Insert default AI provider configurations
     */
    private static function insert_default_ai_providers() {
        // OpenRouter Provider (Multi-Model Access)
        add_option('sixlab_ai_provider_openrouter_config', wp_json_encode(array(
            'api_key' => '',
            'app_name' => 'SixLab Tool',
            'model' => 'openai/gpt-4o-mini',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'timeout' => 30,
            'rate_limit_per_minute' => 60,
            'input_token_rate' => 0.00015,
            'output_token_rate' => 0.0006
        )));
        
        // Available AI providers list (only OpenRouter)
        add_option('sixlab_available_ai_providers', wp_json_encode(array(
            'openrouter'
        )));
        
        // AI provider display names
        add_option('sixlab_ai_provider_names', wp_json_encode(array(
            'openrouter' => 'OpenRouter (Multi-Model AI)'
        )));
        
        // AI provider capabilities
        add_option('sixlab_ai_provider_capabilities', wp_json_encode(array(
            'openrouter' => array('contextual_help', 'configuration_analysis', 'chat', 'error_explanation', 'hint_request', 'multi_model_access')
        )));
    }
    
    /**
     * Check if database upgrade is needed
     */
    public static function maybe_upgrade($current_version) {
        $db_version = get_option('sixlab_db_version', '0.0.0');
        
        if (version_compare($db_version, self::DB_VERSION, '<')) {
            self::upgrade_database($db_version);
        }
    }
    
    /**
     * Upgrade database from older versions
     */
    private static function upgrade_database($from_version) {
        global $wpdb;
        
        // Add upgrade logic for future versions
        if (version_compare($from_version, '1.0.0', '<')) {
            // Future upgrade logic will go here
            // For now, just recreate tables
            self::create_tables();
        }
        
        // Run additional migrations for date/time fields
        self::run_migration_010();
        
        // Run migration for delete/reset scripts
        self::run_migration_011();
        
        // Update database version
        update_option('sixlab_db_version', self::DB_VERSION);
    }
    
    /**
     * Migration 010: Add date/time fields to lab templates
     */
    private static function run_migration_010() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        // Check if columns already exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'lab_start_date'");
        
        if (empty($columns)) {
            // Add new columns
            $wpdb->query("ALTER TABLE {$table_name} 
                ADD COLUMN lab_start_date DATE NULL AFTER estimated_duration,
                ADD COLUMN lab_start_time TIME NULL AFTER lab_start_date,
                ADD COLUMN lab_end_date DATE NULL AFTER lab_start_time,
                ADD COLUMN lab_end_time TIME NULL AFTER lab_end_date");
            
            // Add index
            $wpdb->query("CREATE INDEX idx_lab_schedule ON {$table_name}(lab_start_date, lab_start_time)");
        }
    }
    
    /**
     * Migration 011: Add delete/reset script columns to lab templates
     */
    private static function run_migration_011() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sixlab_lab_templates';
        
        // Check if columns already exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'delete_reset_script'");
        
        if (empty($columns)) {
            // Add new columns
            $wpdb->query("ALTER TABLE {$table_name} 
                ADD COLUMN delete_reset_script LONGTEXT NULL AFTER validation_rules,
                ADD COLUMN guided_delete_reset_script LONGTEXT NULL AFTER delete_reset_script");
            
            // Add index for template type queries
            $wpdb->query("CREATE INDEX idx_lab_template_type ON {$table_name}(template_type)");
        }
    }
    
    /**
     * Get table names
     */
    public static function get_table_names() {
        global $wpdb;
        
        return array(
            'sessions' => $wpdb->prefix . 'sixlab_sessions',
            'providers' => $wpdb->prefix . 'sixlab_providers',
            'ai_interactions' => $wpdb->prefix . 'sixlab_ai_interactions',
            'validations' => $wpdb->prefix . 'sixlab_validations',
            'lab_templates' => $wpdb->prefix . 'sixlab_lab_templates',
            'analytics' => $wpdb->prefix . 'sixlab_analytics'
        );
    }
    
    /**
     * Backup database tables
     */
    public static function backup_tables($backup_file = null) {
        global $wpdb;
        
        if (!$backup_file) {
            $backup_file = SIXLAB_PLUGIN_DIR . 'database/backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $tables = self::get_table_names();
        $sql_dump = '';
        
        foreach ($tables as $table_name => $table) {
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
            if ($create_table) {
                $sql_dump .= "\n\n-- Table structure for {$table_name}\n";
                $sql_dump .= "DROP TABLE IF EXISTS {$table};\n";
                $sql_dump .= $create_table[1] . ";\n";
            }
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
            if ($rows) {
                $sql_dump .= "\n-- Data for {$table_name}\n";
                foreach ($rows as $row) {
                    $values = array();
                    foreach ($row as $value) {
                        $values[] = is_null($value) ? 'NULL' : "'" . $wpdb->_escape($value) . "'";
                    }
                    $sql_dump .= "INSERT INTO {$table} VALUES (" . implode(',', $values) . ");\n";
                }
            }
        }
        
        // Write to file
        $result = file_put_contents($backup_file, $sql_dump);
        
        return $result !== false ? $backup_file : false;
    }
    
    /**
     * Restore database from backup
     */
    public static function restore_from_backup($backup_file) {
        global $wpdb;
        
        if (!file_exists($backup_file)) {
            return new WP_Error('backup_not_found', __('Backup file not found', 'sixlab-tool'));
        }
        
        $sql_content = file_get_contents($backup_file);
        
        if ($sql_content === false) {
            return new WP_Error('backup_read_failed', __('Failed to read backup file', 'sixlab-tool'));
        }
        
        // Split SQL into individual queries
        $queries = explode(';', $sql_content);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $result = $wpdb->query($query);
                if ($result === false) {
                    return new WP_Error('restore_query_failed', sprintf(__('Failed to execute query: %s', 'sixlab-tool'), $query));
                }
            }
        }
        
        return true;
    }
    
    /**
     * Optimize database tables
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = self::get_table_names();
        $results = array();
        
        foreach ($tables as $table_name => $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            $results[$table_name] = $result !== false;
        }
        
        return $results;
    }
    
    /**
     * Get database statistics
     */
    public static function get_database_statistics() {
        global $wpdb;
        
        $tables = self::get_table_names();
        $stats = array();
        
        foreach ($tables as $table_name => $table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $size = $wpdb->get_var("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = '{$wpdb->dbname}' AND table_name = '{$table}'");
            
            $stats[$table_name] = array(
                'rows' => intval($count),
                'size_mb' => floatval($size)
            );
        }
        
        return $stats;
    }
}
