<?php
/**
 * 6Lab Tool - Automation Manager
 * Handles development automation scripts and build pipeline
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include automation templates
require_once plugin_dir_path(__FILE__) . 'automation-templates.php';

class SixLab_Automation_Manager {
    use SixLab_Automation_Templates;
    
    /**
     * Available automation scripts
     * @var array
     */
    private $automation_scripts = array(
        'project_init' => array(
            'command' => 'create_plugin_structure',
            'description' => 'Initialize complete plugin directory structure',
            'steps' => array(
                'create_main_plugin_file',
                'setup_autoloader',
                'create_directory_structure', 
                'generate_base_classes',
                'setup_database_migrations',
                'create_admin_interfaces',
                'setup_frontend_assets'
            )
        ),
        'database_setup' => array(
            'command' => 'setup_database',
            'description' => 'Create all required database tables and initial data',
            'migrations' => array(
                '001_create_sessions_table.sql',
                '002_create_providers_table.sql',
                '003_create_ai_interactions_table.sql', 
                '004_create_validations_table.sql',
                '005_create_lab_templates_table.sql',
                '006_create_analytics_table.sql',
                '007_insert_default_providers.sql'
            )
        ),
        'provider_scaffold' => array(
            'command' => 'generate_provider',
            'parameters' => array('provider_name', 'provider_type'),
            'generates' => array(
                'includes/providers/class-{provider_name}-provider.php',
                'admin/views/provider-{provider_name}-config.php',
                'public/js/providers/{provider_name}-adapter.js',
                'assets/icons/{provider_name}-icon.svg'
            )
        ),
        'ai_integration' => array(
            'command' => 'setup_ai_provider',
            'parameters' => array('ai_provider_name'),
            'generates' => array(
                'includes/ai/class-{ai_provider}-provider.php',
                'admin/views/ai-{ai_provider}-config.php'
            )
        ),
        'testing_setup' => array(
            'command' => 'create_test_suite',
            'generates' => array(
                'tests/unit/',
                'tests/integration/',
                'tests/e2e/',
                'phpunit.xml',
                'jest.config.js'
            )
        )
    );
    
    /**
     * Build pipeline configuration
     * @var array
     */
    private $build_pipeline = array(
        'development' => array(
            'watch_files' => array('**/*.php', '**/*.js', '**/*.css'),
            'tasks' => array('lint_php', 'lint_js', 'compile_sass', 'run_unit_tests')
        ),
        'production' => array(
            'tasks' => array(
                'lint_all',
                'run_full_test_suite',
                'minify_assets',
                'optimize_images', 
                'generate_documentation',
                'create_deployment_package'
            )
        )
    );
    
    /**
     * Plugin base path
     * @var string
     */
    private $plugin_path;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_path = plugin_dir_path(dirname(__FILE__, 2));
        add_action('wp_ajax_sixlab_run_automation', array($this, 'handle_automation_request'));
    }
    
    /**
     * Run automation script
     * 
     * @param string $script_name Script name to run
     * @param array $parameters Script parameters
     * @return array Result of script execution
     */
    public function run_automation_script($script_name, $parameters = array()) {
        if (!isset($this->automation_scripts[$script_name])) {
            return array(
                'success' => false,
                'message' => "Unknown automation script: {$script_name}"
            );
        }
        
        $script = $this->automation_scripts[$script_name];
        $method_name = 'run_' . $script_name;
        
        if (method_exists($this, $method_name)) {
            return $this->$method_name($parameters);
        }
        
        return array(
            'success' => false,
            'message' => "Automation script method not implemented: {$method_name}"
        );
    }
    
    /**
     * Run project initialization
     * 
     * @param array $parameters
     * @return array
     */
    public function run_project_init($parameters = array()) {
        $results = array();
        $script = $this->automation_scripts['project_init'];
        
        foreach ($script['steps'] as $step) {
            $step_method = 'step_' . $step;
            if (method_exists($this, $step_method)) {
                $results[$step] = $this->$step_method($parameters);
            } else {
                $results[$step] = array(
                    'success' => false,
                    'message' => "Step method not implemented: {$step_method}"
                );
            }
        }
        
        $success = !in_array(false, array_column($results, 'success'));
        
        return array(
            'success' => $success,
            'message' => $success ? 'Project initialization completed successfully' : 'Some steps failed during initialization',
            'details' => $results
        );
    }
    
    /**
     * Run database setup
     * 
     * @param array $parameters
     * @return array
     */
    public function run_database_setup($parameters = array()) {
        global $wpdb;
        
        $results = array();
        $script = $this->automation_scripts['database_setup'];
        $migrations_path = $this->plugin_path . 'database/migrations/';
        
        foreach ($script['migrations'] as $migration) {
            $migration_file = $migrations_path . $migration;
            
            if (!file_exists($migration_file)) {
                $results[$migration] = array(
                    'success' => false,
                    'message' => "Migration file not found: {$migration}"
                );
                continue;
            }
            
            $sql = file_get_contents($migration_file);
            
            // Replace placeholders
            $sql = str_replace('{prefix}', $wpdb->prefix, $sql);
            $sql = str_replace('{wp_prefix}', $wpdb->prefix, $sql);
            
            // Split multiple statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (empty($statement)) continue;
                
                $result = $wpdb->query($statement);
                
                if ($result === false) {
                    $results[$migration] = array(
                        'success' => false,
                        'message' => "Failed to execute migration: {$migration}",
                        'error' => $wpdb->last_error
                    );
                    continue 2;
                }
            }
            
            $results[$migration] = array(
                'success' => true,
                'message' => "Migration executed successfully: {$migration}"
            );
        }
        
        $success = !in_array(false, array_column($results, 'success'));
        
        return array(
            'success' => $success,
            'message' => $success ? 'Database setup completed successfully' : 'Some migrations failed',
            'details' => $results
        );
    }
    
    /**
     * Generate provider scaffold
     * 
     * @param array $parameters
     * @return array
     */
    public function run_provider_scaffold($parameters = array()) {
        if (empty($parameters['provider_name']) || empty($parameters['provider_type'])) {
            return array(
                'success' => false,
                'message' => 'Provider name and type are required'
            );
        }
        
        $provider_name = sanitize_key($parameters['provider_name']);
        $provider_type = sanitize_key($parameters['provider_type']);
        $class_name = ucfirst($provider_name) . '_Provider';
        
        $script = $this->automation_scripts['provider_scaffold'];
        $results = array();
        
        foreach ($script['generates'] as $template) {
            $file_path = str_replace('{provider_name}', $provider_name, $template);
            $full_path = $this->plugin_path . $file_path;
            
            // Create directory if needed
            $dir = dirname($full_path);
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
            
            $content = $this->generate_provider_file_content($template, $provider_name, $provider_type, $class_name);
            
            if (file_put_contents($full_path, $content)) {
                $results[$file_path] = array(
                    'success' => true,
                    'message' => "Generated: {$file_path}"
                );
            } else {
                $results[$file_path] = array(
                    'success' => false,
                    'message' => "Failed to generate: {$file_path}"
                );
            }
        }
        
        $success = !in_array(false, array_column($results, 'success'));
        
        return array(
            'success' => $success,
            'message' => $success ? "Provider '{$provider_name}' scaffolded successfully" : 'Some files failed to generate',
            'details' => $results
        );
    }
    
    /**
     * Setup AI integration
     * 
     * @param array $parameters
     * @return array
     */
    public function run_ai_integration($parameters = array()) {
        if (empty($parameters['ai_provider_name'])) {
            return array(
                'success' => false,
                'message' => 'AI provider name is required'
            );
        }
        
        $ai_provider = sanitize_key($parameters['ai_provider_name']);
        $class_name = ucfirst($ai_provider) . '_Provider';
        
        $script = $this->automation_scripts['ai_integration'];
        $results = array();
        
        foreach ($script['generates'] as $template) {
            $file_path = str_replace('{ai_provider}', $ai_provider, $template);
            $full_path = $this->plugin_path . $file_path;
            
            // Create directory if needed
            $dir = dirname($full_path);
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
            
            $content = $this->generate_ai_provider_file_content($template, $ai_provider, $class_name);
            
            if (file_put_contents($full_path, $content)) {
                $results[$file_path] = array(
                    'success' => true,
                    'message' => "Generated: {$file_path}"
                );
            } else {
                $results[$file_path] = array(
                    'success' => false,
                    'message' => "Failed to generate: {$file_path}"
                );
            }
        }
        
        $success = !in_array(false, array_column($results, 'success'));
        
        return array(
            'success' => $success,
            'message' => $success ? "AI provider '{$ai_provider}' integrated successfully" : 'Some files failed to generate',
            'details' => $results
        );
    }
    
    /**
     * Setup testing suite
     * 
     * @param array $parameters
     * @return array
     */
    public function run_testing_setup($parameters = array()) {
        $script = $this->automation_scripts['testing_setup'];
        $results = array();
        
        foreach ($script['generates'] as $path) {
            $full_path = $this->plugin_path . $path;
            
            if (substr($path, -1) === '/') {
                // Directory
                if (wp_mkdir_p($full_path)) {
                    $results[$path] = array(
                        'success' => true,
                        'message' => "Created directory: {$path}"
                    );
                } else {
                    $results[$path] = array(
                        'success' => false,
                        'message' => "Failed to create directory: {$path}"
                    );
                }
            } else {
                // File
                $dir = dirname($full_path);
                if (!is_dir($dir)) {
                    wp_mkdir_p($dir);
                }
                
                $content = $this->generate_test_file_content($path);
                
                if (file_put_contents($full_path, $content)) {
                    $results[$path] = array(
                        'success' => true,
                        'message' => "Generated: {$path}"
                    );
                } else {
                    $results[$path] = array(
                        'success' => false,
                        'message' => "Failed to generate: {$path}"
                    );
                }
            }
        }
        
        $success = !in_array(false, array_column($results, 'success'));
        
        return array(
            'success' => $success,
            'message' => $success ? 'Test suite setup completed successfully' : 'Some test files failed to generate',
            'details' => $results
        );
    }
    
    /**
     * Run build pipeline
     * 
     * @param string $environment development|production
     * @return array
     */
    public function run_build_pipeline($environment = 'development') {
        if (!isset($this->build_pipeline[$environment])) {
            return array(
                'success' => false,
                'message' => "Unknown build environment: {$environment}"
            );
        }
        
        $pipeline = $this->build_pipeline[$environment];
        $results = array();
        
        foreach ($pipeline['tasks'] as $task) {
            $task_method = 'build_task_' . $task;
            
            if (method_exists($this, $task_method)) {
                $results[$task] = $this->$task_method();
            } else {
                $results[$task] = array(
                    'success' => false,
                    'message' => "Build task not implemented: {$task}"
                );
            }
        }
        
        $success = !in_array(false, array_column($results, 'success'));
        
        return array(
            'success' => $success,
            'message' => $success ? "Build pipeline ({$environment}) completed successfully" : 'Some build tasks failed',
            'details' => $results
        );
    }
    
    /**
     * Handle AJAX automation requests
     */
    public function handle_automation_request() {
        check_ajax_referer('sixlab_automation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'sixlab-tool'));
        }
        
        $script_name = sanitize_key($_POST['script'] ?? '');
        $parameters = $_POST['parameters'] ?? array();
        
        if (empty($script_name)) {
            wp_send_json_error(array('message' => 'Script name is required'));
        }
        
        $result = $this->run_automation_script($script_name, $parameters);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Generate provider file content based on template
     * 
     * @param string $template
     * @param string $provider_name
     * @param string $provider_type
     * @param string $class_name
     * @return string
     */
    private function generate_provider_file_content($template, $provider_name, $provider_type, $class_name) {
        if (strpos($template, '.php') !== false) {
            return $this->get_provider_php_template($provider_name, $provider_type, $class_name);
        } elseif (strpos($template, '.js') !== false) {
            return $this->get_provider_js_template($provider_name, $provider_type);
        } elseif (strpos($template, 'config.php') !== false) {
            return $this->get_provider_config_template($provider_name, $provider_type);
        } elseif (strpos($template, '.svg') !== false) {
            return $this->get_provider_icon_template($provider_name);
        }
        
        return "// Generated file for {$provider_name} provider\n";
    }
    
    /**
     * Generate AI provider file content
     * 
     * @param string $template
     * @param string $ai_provider
     * @param string $class_name
     * @return string
     */
    private function generate_ai_provider_file_content($template, $ai_provider, $class_name) {
        if (strpos($template, '.php') !== false && strpos($template, 'config.php') === false) {
            return $this->get_ai_provider_php_template($ai_provider, $class_name);
        } elseif (strpos($template, 'config.php') !== false) {
            return $this->get_ai_provider_config_template($ai_provider);
        }
        
        return "// Generated AI provider file for {$ai_provider}\n";
    }
    
    /**
     * Generate test file content
     * 
     * @param string $file_path
     * @return string
     */
    private function generate_test_file_content($file_path) {
        if ($file_path === 'phpunit.xml') {
            return $this->get_phpunit_xml_template();
        } elseif ($file_path === 'jest.config.js') {
            return $this->get_jest_config_template();
        }
        
        return "// Generated test file\n";
    }
    
    /**
     * Build task: Lint PHP files
     * 
     * @param array $task
     * @return array
     */
    private function build_task_lint_php($task = array()) {
        $errors = array();
        $files_checked = 0;
        
        // Define directories to check
        $directories = array(
            $this->plugin_path . '/includes',
            $this->plugin_path . '/admin',
            $this->plugin_path . '/public'
        );
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );
            
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') continue;
                
                $files_checked++;
                $file_path = $file->getRealPath();
                
                // Basic PHP syntax check
                $output = array();
                $return_var = 0;
                exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_var);
                
                if ($return_var !== 0) {
                    $errors[] = array(
                        'file' => str_replace($this->plugin_path, '', $file_path),
                        'error' => implode("\n", $output)
                    );
                }
            }
        }
        
        if (empty($errors)) {
            return array(
                'success' => true,
                'message' => sprintf('PHP linting completed. %d files checked, no errors found.', $files_checked)
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf('PHP linting failed. %d errors found in %d files.', count($errors), $files_checked),
                'errors' => $errors
            );
        }
    }
    
    /**
     * Build task: Minify assets
     * 
     * @param array $task
     * @return array
     */
    private function build_task_minify_assets($task = array()) {
        $minified_files = array();
        $errors = array();
        
        // CSS files
        $css_files = glob($this->plugin_path . '/public/css/*.css');
        foreach ($css_files as $css_file) {
            if (strpos(basename($css_file), '.min.') !== false) continue;
            
            $minified_path = str_replace('.css', '.min.css', $css_file);
            $css_content = file_get_contents($css_file);
            
            if ($css_content !== false) {
                // Basic CSS minification
                $minified_css = $this->minify_css($css_content);
                
                if (file_put_contents($minified_path, $minified_css) !== false) {
                    $minified_files[] = basename($minified_path);
                } else {
                    $errors[] = 'Failed to write ' . basename($minified_path);
                }
            }
        }
        
        // JavaScript files
        $js_files = glob($this->plugin_path . '/public/js/*.js');
        foreach ($js_files as $js_file) {
            if (strpos(basename($js_file), '.min.') !== false) continue;
            
            $minified_path = str_replace('.js', '.min.js', $js_file);
            $js_content = file_get_contents($js_file);
            
            if ($js_content !== false) {
                // Basic JavaScript minification
                $minified_js = $this->minify_js($js_content);
                
                if (file_put_contents($minified_path, $minified_js) !== false) {
                    $minified_files[] = basename($minified_path);
                } else {
                    $errors[] = 'Failed to write ' . basename($minified_path);
                }
            }
        }
        
        if (empty($errors)) {
            return array(
                'success' => true,
                'message' => sprintf('Asset minification completed. %d files minified.', count($minified_files)),
                'files' => $minified_files
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Asset minification completed with errors.',
                'files' => $minified_files,
                'errors' => $errors
            );
        }
    }
    
    /**
     * Build task: Generate pot file
     * 
     * @param array $task
     * @return array
     */
    private function build_task_generate_pot($task = array()) {
        $pot_file = $this->plugin_path . '/languages/sixlab-tool.pot';
        
        // Ensure languages directory exists
        if (!is_dir(dirname($pot_file))) {
            wp_mkdir_p(dirname($pot_file));
        }
        
        // Generate POT file content
        $pot_content = $this->generate_pot_content();
        
        if (file_put_contents($pot_file, $pot_content) !== false) {
            return array(
                'success' => true,
                'message' => 'POT file generated successfully.',
                'file' => 'languages/sixlab-tool.pot'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to generate POT file.'
            );
        }
    }
    
    /**
     * Build task: Create distribution package
     * 
     * @param array $task
     * @return array
     */
    private function build_task_create_package($task = array()) {
        $version = $this->get_plugin_version();
        $package_name = "sixlab-tool-{$version}.zip";
        $package_path = dirname($this->plugin_path) . '/' . $package_name;
        
        // Create temporary directory for package
        $temp_dir = sys_get_temp_dir() . '/sixlab-tool-package';
        if (is_dir($temp_dir)) {
            $this->delete_directory($temp_dir);
        }
        wp_mkdir_p($temp_dir . '/sixlab-tool');
        
        // Copy plugin files (excluding development files)
        $exclude_patterns = array(
            '.git',
            '.gitignore',
            'node_modules',
            'tests',
            'phpunit.xml',
            'jest.config.js',
            'package.json',
            'package-lock.json',
            'webpack.config.js',
            '*.log'
        );
        
        $this->copy_directory($this->plugin_path, $temp_dir . '/sixlab-tool', $exclude_patterns);
        
        // Create ZIP archive
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($package_path, ZipArchive::CREATE) === TRUE) {
                $this->add_directory_to_zip($zip, $temp_dir, '');
                $zip->close();
                
                // Clean up temp directory
                $this->delete_directory($temp_dir);
                
                return array(
                    'success' => true,
                    'message' => 'Distribution package created successfully.',
                    'package' => $package_name,
                    'size' => filesize($package_path)
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Failed to create ZIP archive.'
                );
            }
        } else {
            return array(
                'success' => false,
                'message' => 'ZipArchive extension not available.'
            );
        }
    }
    
    /**
     * Minify CSS content
     * 
     * @param string $css
     * @return string
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        
        // Remove trailing semicolon before closing brace
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript content
     * 
     * @param string $js
     * @return string
     */
    private function minify_js($js) {
        // Remove single-line comments
        $js = preg_replace('!//.*$!m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('!/\*.*?\*/!s', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }
    
    /**
     * Generate POT file content
     * 
     * @return string
     */
    private function generate_pot_content() {
        $pot_header = '# Copyright (C) ' . date('Y') . ' 6Lab Tool
# This file is distributed under the same license as the 6Lab Tool package.
msgid ""
msgstr ""
"Project-Id-Version: 6Lab Tool\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n"

';
        
        return $pot_header;
    }
    
    /**
     * Get plugin version
     * 
     * @return string
     */
    private function get_plugin_version() {
        $plugin_data = get_file_data($this->plugin_path . '/sixlab-tool.php', array(
            'Version' => 'Version'
        ));
        
        return $plugin_data['Version'] ?? '1.0.0';
    }
    
    /**
     * Copy directory with exclusions
     * 
     * @param string $source
     * @param string $destination
     * @param array $exclude_patterns
     */
    private function copy_directory($source, $destination, $exclude_patterns = array()) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relative_path = $iterator->getSubPathName();
            
            // Check if file should be excluded
            $should_exclude = false;
            foreach ($exclude_patterns as $pattern) {
                if (strpos($relative_path, $pattern) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) continue;
            
            $destination_path = $destination . DIRECTORY_SEPARATOR . $relative_path;
            
            if ($item->isDir()) {
                wp_mkdir_p($destination_path);
            } else {
                copy($item, $destination_path);
            }
        }
    }
    
    /**
     * Add directory to ZIP archive
     * 
     * @param ZipArchive $zip
     * @param string $directory
     * @param string $base_path
     */
    private function add_directory_to_zip($zip, $directory, $base_path) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $relative_path = $base_path . $iterator->getSubPathName();
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
            } else {
                $zip->addFile($file, $relative_path);
            }
        }
    }
    
    /**
     * Delete directory recursively
     * 
     * @param string $directory
     */
    private function delete_directory($directory) {
        if (!is_dir($directory)) return;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        
        rmdir($directory);
    }
    
    // Template methods would continue here...
    // Due to length constraints, I'll create these in separate files
    
    /**
     * Get list of available automation scripts
     * 
     * @return array
     */
    public function get_automation_scripts() {
        return $this->automation_scripts;
    }
    
    /**
     * Get build pipeline configuration
     * 
     * @return array
     */
    public function get_build_pipeline() {
        return $this->build_pipeline;
    }
}
