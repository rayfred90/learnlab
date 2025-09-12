<?php
/**
 * Lab Sessions Admin Page Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_session']) && wp_verify_nonce($_POST['_wpnonce'], 'sixlab_sessions_nonce')) {
        // Create new lab session
        $lab_id = intval($_POST['lab_id']);
        $user_id = get_current_user_id();
        $status = sanitize_text_field($_POST['status'] ?? 'started');
        $current_step = intval($_POST['current_step'] ?? 1);
        $completed_steps = sanitize_text_field($_POST['completed_steps'] ?? '');
        $time_spent_minutes = intval($_POST['time_spent_minutes'] ?? 0);
        $score = floatval($_POST['score'] ?? 0);
        $commands_history = sanitize_textarea_field($_POST['commands_history'] ?? '');
        $device_configs = sanitize_textarea_field($_POST['device_configs'] ?? '');
        
        // Process completed steps array
        $completed_steps_array = array();
        if (!empty($_POST['completed_steps_array'])) {
            $completed_steps_array = array_map('intval', $_POST['completed_steps_array']);
        }
        
        // Process device configs JSON
        $device_configs_json = array();
        if (!empty($_POST['router_config'])) {
            $device_configs_json['router_config'] = array();
            
            // Process interfaces
            if (!empty($_POST['router_config']['interfaces'])) {
                foreach ($_POST['router_config']['interfaces'] as $interface_name => $interface_data) {
                    $device_configs_json['router_config']['interfaces'][$interface_name] = array(
                        'ip_address' => sanitize_text_field($interface_data['ip_address'] ?? ''),
                        'subnet_mask' => sanitize_text_field($interface_data['subnet_mask'] ?? ''),
                        'status' => sanitize_text_field($interface_data['status'] ?? '')
                    );
                }
            }
            
            // Process routing table
            if (!empty($_POST['router_config']['routing_table'])) {
                $device_configs_json['router_config']['routing_table'] = array_map('sanitize_text_field', $_POST['router_config']['routing_table']);
            }
        }
        
        // Process switch config
        if (!empty($_POST['switch_config'])) {
            $device_configs_json['switch_config'] = array();
            
            if (!empty($_POST['switch_config']['vlans'])) {
                $device_configs_json['switch_config']['vlans'] = array_map('sanitize_text_field', $_POST['switch_config']['vlans']);
            }
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'sixlab_sessions',
            array(
                'user_id' => $user_id,
                'lab_id' => $lab_id,
                'provider' => 'manual', // Since this is manually created
                'provider_session_id' => 'manual_' . time(),
                'current_step' => $current_step,
                'completed_steps' => json_encode($completed_steps_array),
                'status' => $status,
                'score' => $score,
                'time_spent_minutes' => $time_spent_minutes,
                'commands_history' => $commands_history,
                'device_configs' => json_encode($device_configs_json),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+4 hours'))
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            add_settings_error('sixlab_sessions', 'session_created', __('Lab session created successfully.', 'sixlab-tool'), 'success');
        } else {
            add_settings_error('sixlab_sessions', 'session_error', __('Error creating lab session.', 'sixlab-tool'), 'error');
        }
    }
}

// Get all lab sessions
$sessions = $wpdb->get_results("
    SELECT s.*, u.display_name, t.name as lab_name 
    FROM {$wpdb->prefix}sixlab_sessions s 
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
    LEFT JOIN {$wpdb->prefix}sixlab_lab_templates t ON s.lab_id = t.id 
    ORDER BY s.created_at DESC
");

// Get available labs for dropdown
$labs = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}sixlab_lab_templates WHERE is_active = 1 ORDER BY name");

$active_tab = $_GET['tab'] ?? 'overview';
?>

<div class="wrap">
    <h1><?php _e('Lab Sessions', 'sixlab-tool'); ?></h1>
    
    <?php settings_errors('sixlab_sessions'); ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=sixlab-sessions&tab=overview" 
           class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Overview', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-sessions&tab=create" 
           class="nav-tab <?php echo $active_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Create Session', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-sessions&tab=analytics" 
           class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Analytics', 'sixlab-tool'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($active_tab === 'overview'): ?>
            <div class="sixlab-sessions-overview">
                <h2><?php _e('Lab Sessions Management', 'sixlab-tool'); ?></h2>
                <p><?php _e('Monitor and manage active lab sessions for students.', 'sixlab-tool'); ?></p>
                
                <div class="sixlab-sessions-actions">
                    <a href="?page=sixlab-sessions&tab=create" class="button button-primary">
                        <?php _e('Create New Session', 'sixlab-tool'); ?>
                    </a>
                </div>
                
                <div class="sixlab-sessions-list">
                    <h3><?php _e('Current Sessions', 'sixlab-tool'); ?></h3>
                    
                    <?php if (!empty($sessions)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Lab ID', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Lab Name', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Student', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Status', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Current Step', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Completed Steps', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Time Spent', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Score', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Created', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Actions', 'sixlab-tool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><?php echo esc_html($session->lab_id); ?></td>
                                        <td><?php echo esc_html($session->lab_name ?: 'Unknown Lab'); ?></td>
                                        <td><?php echo esc_html($session->display_name ?: 'Unknown User'); ?></td>
                                        <td>
                                            <span class="sixlab-status-<?php echo esc_attr($session->status); ?>">
                                                <?php echo esc_html(ucfirst($session->status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($session->current_step); ?></td>
                                        <td>
                                            <?php 
                                            $completed = json_decode($session->completed_steps, true) ?: array();
                                            echo esc_html(implode(', ', $completed));
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($session->time_spent_minutes); ?> <?php _e('min', 'sixlab-tool'); ?></td>
                                        <td><?php echo esc_html($session->score); ?>%</td>
                                        <td><?php echo esc_html(date('M j, Y H:i', strtotime($session->created_at))); ?></td>
                                        <td>
                                            <button type="button" class="button button-small sixlab-view-session" 
                                                    data-session-id="<?php echo esc_attr($session->id); ?>">
                                                <?php _e('View', 'sixlab-tool'); ?>
                                            </button>
                                            <button type="button" class="button button-small sixlab-end-session" 
                                                    data-session-id="<?php echo esc_attr($session->id); ?>">
                                                <?php _e('End', 'sixlab-tool'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="sixlab-empty-state">
                            <h3><?php _e('No Lab Sessions Found', 'sixlab-tool'); ?></h3>
                            <p><?php _e('No active lab sessions at this time.', 'sixlab-tool'); ?></p>
                            <a href="?page=sixlab-sessions&tab=create" class="button button-primary">
                                <?php _e('Create First Session', 'sixlab-tool'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($active_tab === 'create'): ?>
            <div class="sixlab-session-form">
                <h2><?php _e('Create New Lab Session', 'sixlab-tool'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('sixlab_sessions_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="lab_id"><?php _e('Lab Template', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="lab_id" name="lab_id" required>
                                    <option value=""><?php _e('Select a Lab', 'sixlab-tool'); ?></option>
                                    <?php foreach ($labs as $lab): ?>
                                        <option value="<?php echo esc_attr($lab->id); ?>">
                                            <?php echo esc_html($lab->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('Choose the lab template for this session.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status"><?php _e('Initial Status', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="status" name="status">
                                    <option value="started"><?php _e('Started', 'sixlab-tool'); ?></option>
                                    <option value="in_progress"><?php _e('In Progress', 'sixlab-tool'); ?></option>
                                    <option value="completed"><?php _e('Completed', 'sixlab-tool'); ?></option>
                                    <option value="failed"><?php _e('Failed', 'sixlab-tool'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="current_step"><?php _e('Current Step', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="current_step" name="current_step" 
                                       min="1" value="1" class="small-text">
                                <p class="description">
                                    <?php _e('Current step number the student is on.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="completed_steps_input"><?php _e('Completed Steps', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <div id="completed_steps_container">
                                    <button type="button" class="button" onclick="addCompletedStep()">
                                        <?php _e('Add Step', 'sixlab-tool'); ?>
                                    </button>
                                </div>
                                <p class="description">
                                    <?php _e('List of completed step numbers.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="time_spent_minutes"><?php _e('Time Spent', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="time_spent_minutes" name="time_spent_minutes" 
                                       min="0" value="0" class="small-text"> <?php _e('minutes', 'sixlab-tool'); ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="score"><?php _e('Score', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="score" name="score" 
                                       min="0" max="100" step="0.01" value="0" class="small-text"> %
                                <p class="description">
                                    <?php _e('Current lab score (0-100).', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="commands_history"><?php _e('Commands History', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="commands_history" name="commands_history" 
                                          rows="5" class="large-text" 
                                          placeholder="<?php esc_attr_e('Enter command history (one command per line)', 'sixlab-tool'); ?>"></textarea>
                                <p class="description">
                                    <?php _e('History of commands executed by the student.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php _e('Device Configurations', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <h4><?php _e('Router Configuration', 'sixlab-tool'); ?></h4>
                                
                                <h5><?php _e('Interfaces', 'sixlab-tool'); ?></h5>
                                <div id="router_interfaces_container">
                                    <div class="interface-config">
                                        <label><?php _e('Interface Name:', 'sixlab-tool'); ?></label>
                                        <input type="text" name="interface_name_0" placeholder="GigabitEthernet0/0" class="regular-text">
                                        
                                        <label><?php _e('IP Address:', 'sixlab-tool'); ?></label>
                                        <input type="text" name="router_config[interfaces][GigabitEthernet0/0][ip_address]" class="regular-text">
                                        
                                        <label><?php _e('Subnet Mask:', 'sixlab-tool'); ?></label>
                                        <input type="text" name="router_config[interfaces][GigabitEthernet0/0][subnet_mask]" class="regular-text">
                                        
                                        <label><?php _e('Status:', 'sixlab-tool'); ?></label>
                                        <select name="router_config[interfaces][GigabitEthernet0/0][status]">
                                            <option value="up"><?php _e('Up', 'sixlab-tool'); ?></option>
                                            <option value="down"><?php _e('Down', 'sixlab-tool'); ?></option>
                                        </select>
                                        
                                        <button type="button" class="button remove-interface" onclick="removeInterface(this)">
                                            <?php _e('Remove', 'sixlab-tool'); ?>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="button" onclick="addInterface()">
                                    <?php _e('Add Interface', 'sixlab-tool'); ?>
                                </button>
                                
                                <h5><?php _e('Routing Table', 'sixlab-tool'); ?></h5>
                                <div id="routing_table_container">
                                    <input type="text" name="router_config[routing_table][]" placeholder="<?php esc_attr_e('Enter route', 'sixlab-tool'); ?>" class="regular-text">
                                </div>
                                <button type="button" class="button" onclick="addRoute()">
                                    <?php _e('Add Route', 'sixlab-tool'); ?>
                                </button>
                                
                                <h4><?php _e('Switch Configuration', 'sixlab-tool'); ?></h4>
                                
                                <h5><?php _e('VLANs', 'sixlab-tool'); ?></h5>
                                <div id="switch_vlans_container">
                                    <input type="text" name="switch_config[vlans][]" placeholder="<?php esc_attr_e('VLAN ID and name', 'sixlab-tool'); ?>" class="regular-text">
                                </div>
                                <button type="button" class="button" onclick="addVlan()">
                                    <?php _e('Add VLAN', 'sixlab-tool'); ?>
                                </button>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Create Session', 'sixlab-tool'), 'primary', 'create_session'); ?>
                </form>
            </div>
            
        <?php elseif ($active_tab === 'analytics'): ?>
            <div class="sixlab-sessions-analytics">
                <h2><?php _e('Lab Sessions Analytics', 'sixlab-tool'); ?></h2>
                <p><?php _e('View analytics and statistics for lab sessions.', 'sixlab-tool'); ?></p>
                
                <?php
                // Get analytics data
                $total_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions");
                $active_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions WHERE status IN ('active', 'started', 'in_progress')");
                $completed_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sixlab_sessions WHERE status = 'completed'");
                $average_score = $wpdb->get_var("SELECT AVG(score) FROM {$wpdb->prefix}sixlab_sessions WHERE score > 0");
                $average_time = $wpdb->get_var("SELECT AVG(time_spent_minutes) FROM {$wpdb->prefix}sixlab_sessions WHERE time_spent_minutes > 0");
                ?>
                
                <div class="sixlab-analytics-grid">
                    <div class="analytics-card">
                        <h3><?php _e('Total Sessions', 'sixlab-tool'); ?></h3>
                        <div class="analytics-number"><?php echo esc_html($total_sessions); ?></div>
                    </div>
                    
                    <div class="analytics-card">
                        <h3><?php _e('Active Sessions', 'sixlab-tool'); ?></h3>
                        <div class="analytics-number"><?php echo esc_html($active_sessions); ?></div>
                    </div>
                    
                    <div class="analytics-card">
                        <h3><?php _e('Completed Sessions', 'sixlab-tool'); ?></h3>
                        <div class="analytics-number"><?php echo esc_html($completed_sessions); ?></div>
                    </div>
                    
                    <div class="analytics-card">
                        <h3><?php _e('Average Score', 'sixlab-tool'); ?></h3>
                        <div class="analytics-number"><?php echo esc_html(round($average_score, 1)); ?>%</div>
                    </div>
                    
                    <div class="analytics-card">
                        <h3><?php _e('Average Time', 'sixlab-tool'); ?></h3>
                        <div class="analytics-number"><?php echo esc_html(round($average_time)); ?> min</div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<style>
.sixlab-sessions-overview,
.sixlab-session-form,
.sixlab-sessions-analytics {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 5px;
    margin-top: 20px;
}

.sixlab-sessions-actions {
    margin: 20px 0;
}

.sixlab-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border: 1px dashed #dee2e6;
    border-radius: 5px;
}

.sixlab-status-started,
.sixlab-status-active,
.sixlab-status-in_progress {
    color: #007cba;
    font-weight: bold;
}

.sixlab-status-completed {
    color: #00a32a;
    font-weight: bold;
}

.sixlab-status-failed,
.sixlab-status-error {
    color: #d63638;
    font-weight: bold;
}

.interface-config {
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
    background: #f9f9f9;
}

.interface-config label {
    display: inline-block;
    width: 120px;
    margin: 5px 10px 5px 0;
    font-weight: bold;
}

.interface-config input,
.interface-config select {
    margin: 5px 10px 5px 0;
}

.sixlab-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.analytics-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    text-align: center;
    border: 1px solid #dee2e6;
}

.analytics-card h3 {
    margin-top: 0;
    color: #2271b1;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.analytics-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2271b1;
    margin-top: 10px;
}
</style>

<script>
let completedStepCounter = 0;
let interfaceCounter = 1;

function addCompletedStep() {
    const container = document.getElementById('completed_steps_container');
    const div = document.createElement('div');
    div.innerHTML = `
        <input type="number" name="completed_steps_array[]" min="1" class="small-text" placeholder="<?php esc_attr_e('Step number', 'sixlab-tool'); ?>">
        <button type="button" class="button" onclick="this.parentElement.remove()"><?php _e('Remove', 'sixlab-tool'); ?></button>
    `;
    container.insertBefore(div, container.lastElementChild);
    completedStepCounter++;
}

function addInterface() {
    const container = document.getElementById('router_interfaces_container');
    const div = document.createElement('div');
    div.className = 'interface-config';
    div.innerHTML = `
        <label><?php _e('Interface Name:', 'sixlab-tool'); ?></label>
        <input type="text" name="interface_name_${interfaceCounter}" placeholder="GigabitEthernet0/${interfaceCounter}" class="regular-text" onchange="updateInterfaceName(this, ${interfaceCounter})">
        
        <label><?php _e('IP Address:', 'sixlab-tool'); ?></label>
        <input type="text" name="router_config[interfaces][GigabitEthernet0/${interfaceCounter}][ip_address]" class="regular-text" id="ip_${interfaceCounter}">
        
        <label><?php _e('Subnet Mask:', 'sixlab-tool'); ?></label>
        <input type="text" name="router_config[interfaces][GigabitEthernet0/${interfaceCounter}][subnet_mask]" class="regular-text" id="mask_${interfaceCounter}">
        
        <label><?php _e('Status:', 'sixlab-tool'); ?></label>
        <select name="router_config[interfaces][GigabitEthernet0/${interfaceCounter}][status]" id="status_${interfaceCounter}">
            <option value="up"><?php _e('Up', 'sixlab-tool'); ?></option>
            <option value="down"><?php _e('Down', 'sixlab-tool'); ?></option>
        </select>
        
        <button type="button" class="button remove-interface" onclick="removeInterface(this)">
            <?php _e('Remove', 'sixlab-tool'); ?>
        </button>
    `;
    container.appendChild(div);
    interfaceCounter++;
}

function removeInterface(button) {
    button.closest('.interface-config').remove();
}

function updateInterfaceName(input, counter) {
    const interfaceName = input.value;
    if (interfaceName) {
        const ipInput = document.getElementById(`ip_${counter}`);
        const maskInput = document.getElementById(`mask_${counter}`);
        const statusSelect = document.getElementById(`status_${counter}`);
        
        ipInput.name = `router_config[interfaces][${interfaceName}][ip_address]`;
        maskInput.name = `router_config[interfaces][${interfaceName}][subnet_mask]`;
        statusSelect.name = `router_config[interfaces][${interfaceName}][status]`;
    }
}

function addRoute() {
    const container = document.getElementById('routing_table_container');
    const div = document.createElement('div');
    div.innerHTML = `
        <input type="text" name="router_config[routing_table][]" placeholder="<?php esc_attr_e('Enter route', 'sixlab-tool'); ?>" class="regular-text">
        <button type="button" class="button" onclick="this.parentElement.remove()"><?php _e('Remove', 'sixlab-tool'); ?></button>
    `;
    container.appendChild(div);
}

function addVlan() {
    const container = document.getElementById('switch_vlans_container');
    const div = document.createElement('div');
    div.innerHTML = `
        <input type="text" name="switch_config[vlans][]" placeholder="<?php esc_attr_e('VLAN ID and name', 'sixlab-tool'); ?>" class="regular-text">
        <button type="button" class="button" onclick="this.parentElement.remove()"><?php _e('Remove', 'sixlab-tool'); ?></button>
    `;
    container.appendChild(div);
}
</script>