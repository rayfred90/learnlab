<?php
/**
 * 6Lab Tool - Settings Page
 * Comprehensive settings interface with organized sections
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get settings sections configuration
$settings_sections = $settings_sections ?? array();
?>

<div class="wrap sixlab-admin-wrap">
    <!-- Header Section -->
    <div class="sixlab-admin-header">
        <div class="sixlab-header-content">
            <h1 class="sixlab-page-title">
                <span class="dashicons dashicons-admin-generic"></span>
                6Lab Tool Settings
            </h1>
            <p class="sixlab-page-subtitle">
                Configure plugin settings and preferences
            </p>
        </div>
        <div class="sixlab-header-actions">
            <button type="button" class="button" onclick="resetAllSettings()">
                <span class="dashicons dashicons-undo"></span>
                Reset to Defaults
            </button>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="sixlab-settings-layout">
        <div class="settings-nav">
            <ul class="settings-nav-list">
                <?php foreach ($settings_sections as $section_key => $section): ?>
                    <li class="settings-nav-item">
                        <a href="#<?php echo esc_attr($section_key); ?>" 
                           class="settings-nav-link <?php echo $section_key === 'general' ? 'active' : ''; ?>"
                           data-section="<?php echo esc_attr($section_key); ?>">
                            <?php 
                            $icons = array(
                                'general' => 'admin-settings',
                                'notifications' => 'email-alt',
                                'security' => 'shield-alt'
                            );
                            $icon = $icons[$section_key] ?? 'admin-generic';
                            ?>
                            <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
                            <?php echo esc_html($section['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            <form method="post" action="options.php" class="sixlab-settings-form">
                <?php foreach ($settings_sections as $section_key => $section): ?>
                    <div class="settings-section" 
                         id="<?php echo esc_attr($section_key); ?>"
                         style="<?php echo $section_key !== 'general' ? 'display: none;' : ''; ?>">
                        
                        <div class="settings-section-header">
                            <h2><?php echo esc_html($section['title']); ?></h2>
                            <?php if ($section_key === 'general'): ?>
                                <p class="section-description">
                                    Configure basic plugin functionality and default behaviors.
                                </p>
                            <?php elseif ($section_key === 'notifications'): ?>
                                <p class="section-description">
                                    Set up notifications for lab completions and system events.
                                </p>
                            <?php elseif ($section_key === 'security'): ?>
                                <p class="section-description">
                                    Configure security settings and access controls.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="settings-section-content">
                            <?php
                            $group_name = 'sixlab_' . $section_key . '_settings';
                            $options = get_option($group_name, array());
                            
                            settings_fields($group_name);
                            ?>
                            
                            <div class="settings-fields">
                                <?php foreach ($section['fields'] as $field_key => $field_config): ?>
                                    <div class="settings-field-group">
                                        <div class="field-header">
                                            <label for="<?php echo esc_attr($group_name . '_' . $field_key); ?>" 
                                                   class="field-label">
                                                <?php echo esc_html($field_config['label']); ?>
                                                <?php if (!empty($field_config['required'])): ?>
                                                    <span class="required">*</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        
                                        <div class="field-control">
                                            <?php
                                            $field_name = $group_name . '[' . $field_key . ']';
                                            $field_id = $group_name . '_' . $field_key;
                                            $field_value = $options[$field_key] ?? $field_config['default'] ?? '';
                                            
                                            $this->render_field_control($field_config, $field_name, $field_id, $field_value);
                                            ?>
                                        </div>
                                        
                                        <?php if (!empty($field_config['description'])): ?>
                                            <div class="field-description">
                                                <p><?php echo esc_html($field_config['description']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Save Button -->
                <div class="settings-save-section">
                    <?php submit_button('Save Settings', 'primary large', 'submit', false, array(
                        'id' => 'sixlab-save-settings'
                    )); ?>
                    
                    <div class="save-status" id="save-status" style="display: none;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        Settings saved successfully!
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Advanced Settings Modal -->
    <div id="advanced-settings-modal" class="sixlab-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Advanced Settings</h3>
                <button type="button" class="modal-close" onclick="closeAdvancedSettings()">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="advanced-setting">
                    <label>Debug Mode</label>
                    <input type="checkbox" id="debug-mode" name="debug_mode">
                    <p class="description">Enable detailed logging for troubleshooting.</p>
                </div>
                
                <div class="advanced-setting">
                    <label>Database Optimization</label>
                    <button type="button" class="button" onclick="optimizeDatabase()">
                        Optimize Database
                    </button>
                    <p class="description">Clean up and optimize database tables.</p>
                </div>
                
                <div class="advanced-setting">
                    <label>Export Configuration</label>
                    <button type="button" class="button" onclick="exportSettings()">
                        Export Settings
                    </button>
                    <p class="description">Download current plugin configuration.</p>
                </div>
                
                <div class="advanced-setting">
                    <label>Import Configuration</label>
                    <input type="file" id="import-file" accept=".json">
                    <button type="button" class="button" onclick="importSettings()">
                        Import Settings
                    </button>
                    <p class="description">Upload and apply configuration file.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button" onclick="closeAdvancedSettings()">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div id="reset-confirmation-modal" class="sixlab-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reset All Settings</h3>
            </div>
            <div class="modal-body">
                <p><strong>Warning:</strong> This will reset all plugin settings to their default values.</p>
                <p>This action cannot be undone. Are you sure you want to continue?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-primary" onclick="confirmResetSettings()">
                    Yes, Reset All Settings
                </button>
                <button type="button" class="button" onclick="closeResetConfirmation()">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Settings Layout */
.sixlab-settings-layout {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
}

.settings-nav {
    width: 250px;
    flex-shrink: 0;
}

.settings-nav-list {
    margin: 0;
    padding: 0;
    list-style: none;
    background: white;
    border: 1px solid var(--sixlab-gray-200);
    border-radius: var(--sixlab-border-radius);
    overflow: hidden;
}

.settings-nav-item {
    margin: 0;
}

.settings-nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    color: var(--sixlab-gray-700);
    text-decoration: none;
    border-bottom: 1px solid var(--sixlab-gray-100);
    transition: all 0.2s ease-in-out;
}

.settings-nav-link:hover {
    background-color: var(--sixlab-gray-50);
    color: var(--sixlab-primary-600);
}

.settings-nav-link.active {
    background-color: var(--sixlab-primary-50);
    color: var(--sixlab-primary-700);
    border-right: 3px solid var(--sixlab-primary-600);
}

.settings-nav-link .dashicons {
    font-size: 1.25rem;
}

.settings-content {
    flex: 1;
    background: white;
    border: 1px solid var(--sixlab-gray-200);
    border-radius: var(--sixlab-border-radius);
    box-shadow: var(--sixlab-shadow-sm);
}

.settings-section {
    padding: 2rem;
}

.settings-section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--sixlab-gray-200);
}

.settings-section-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--sixlab-gray-900);
}

.section-description {
    margin: 0;
    color: var(--sixlab-gray-600);
    font-size: 1rem;
}

.settings-fields {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.settings-field-group {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 1rem;
    align-items: start;
}

.field-label {
    font-weight: 600;
    color: var(--sixlab-gray-700);
    padding-top: 0.5rem;
}

.required {
    color: var(--sixlab-error);
    margin-left: 0.25rem;
}

.field-control {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.field-control input[type="text"],
.field-control input[type="number"],
.field-control input[type="url"],
.field-control input[type="email"],
.field-control select,
.field-control textarea {
    width: 100%;
    max-width: 400px;
    padding: 0.75rem;
    border: 1px solid var(--sixlab-gray-300);
    border-radius: var(--sixlab-border-radius);
    font-size: 0.875rem;
    transition: border-color 0.2s ease-in-out;
}

.field-control input:focus,
.field-control select:focus,
.field-control textarea:focus {
    outline: none;
    border-color: var(--sixlab-primary-500);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.field-control input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    margin: 0;
    accent-color: var(--sixlab-primary-600);
}

.field-description {
    grid-column: 2;
    margin-top: 0.5rem;
}

.field-description p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--sixlab-gray-500);
}

.settings-save-section {
    padding: 2rem;
    border-top: 1px solid var(--sixlab-gray-200);
    background-color: var(--sixlab-gray-50);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.save-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--sixlab-success);
    font-weight: 500;
}

/* Modal Styles */
.sixlab-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background: white;
    border-radius: var(--sixlab-border-radius);
    box-shadow: var(--sixlab-shadow-lg);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
    position: relative;
    z-index: 1;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--sixlab-gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--sixlab-gray-900);
}

.modal-close {
    background: none;
    border: none;
    color: var(--sixlab-gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.modal-close:hover {
    color: var(--sixlab-gray-700);
    background-color: var(--sixlab-gray-100);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--sixlab-gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.advanced-setting {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--sixlab-gray-100);
}

.advanced-setting:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.advanced-setting label {
    display: block;
    font-weight: 600;
    color: var(--sixlab-gray-700);
    margin-bottom: 0.5rem;
}

.advanced-setting .description {
    margin: 0.5rem 0 0 0;
    font-size: 0.875rem;
    color: var(--sixlab-gray-500);
}

/* Responsive Design */
@media (max-width: 768px) {
    .sixlab-settings-layout {
        flex-direction: column;
    }
    
    .settings-nav {
        width: 100%;
    }
    
    .settings-nav-list {
        display: flex;
        overflow-x: auto;
    }
    
    .settings-nav-item {
        flex-shrink: 0;
    }
    
    .settings-field-group {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .field-description {
        grid-column: 1;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Settings navigation
    $('.settings-nav-link').on('click', function(e) {
        e.preventDefault();
        
        const section = $(this).data('section');
        
        // Update navigation
        $('.settings-nav-link').removeClass('active');
        $(this).addClass('active');
        
        // Show section
        $('.settings-section').hide();
        $('#' + section).show();
        
        // Update URL hash
        window.location.hash = section;
    });
    
    // Initialize from hash
    const hash = window.location.hash.replace('#', '');
    if (hash && $('#' + hash).length) {
        $('.settings-nav-link[data-section="' + hash + '"]').click();
    }
    
    // Form submission
    $('.sixlab-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const currentSection = $('.settings-nav-link.active').data('section');
        
        // Show loading state
        $('#sixlab-save-settings').prop('disabled', true).text('Saving...');
        
        // Submit via AJAX
        $.post(ajaxurl, {
            action: 'sixlab_save_settings',
            section: currentSection,
            settings: formData,
            nonce: '<?php echo wp_create_nonce('sixlab_settings'); ?>'
        })
        .done(function(response) {
            if (response.success) {
                $('#save-status').show().fadeOut(3000);
            } else {
                alert('Error saving settings: ' + (response.data.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while saving settings.');
        })
        .always(function() {
            $('#sixlab-save-settings').prop('disabled', false).text('Save Settings');
        });
    });
});

function resetAllSettings() {
    $('#reset-confirmation-modal').show();
}

function closeResetConfirmation() {
    $('#reset-confirmation-modal').hide();
}

function confirmResetSettings() {
    jQuery.post(ajaxurl, {
        action: 'sixlab_reset_settings',
        nonce: '<?php echo wp_create_nonce('sixlab_reset'); ?>'
    })
    .done(function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error resetting settings: ' + (response.data.message || 'Unknown error'));
        }
    })
    .fail(function() {
        alert('Network error occurred while resetting settings.');
    })
    .always(function() {
        closeResetConfirmation();
    });
}

function showAdvancedSettings() {
    $('#advanced-settings-modal').show();
}

function closeAdvancedSettings() {
    $('#advanced-settings-modal').hide();
}

function optimizeDatabase() {
    if (!confirm('This will optimize database tables. Continue?')) return;
    
    jQuery.post(ajaxurl, {
        action: 'sixlab_optimize_database',
        nonce: '<?php echo wp_create_nonce('sixlab_optimize'); ?>'
    })
    .done(function(response) {
        alert(response.success ? 'Database optimized successfully!' : 'Error optimizing database.');
    });
}

function exportSettings() {
    window.location.href = ajaxurl + '?action=sixlab_export_settings&nonce=<?php echo wp_create_nonce('sixlab_export'); ?>';
}

function importSettings() {
    const fileInput = document.getElementById('import-file');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a file to import.');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'sixlab_import_settings');
    formData.append('nonce', '<?php echo wp_create_nonce('sixlab_import'); ?>');
    formData.append('settings_file', file);
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false
    })
    .done(function(response) {
        if (response.success) {
            alert('Settings imported successfully!');
            location.reload();
        } else {
            alert('Error importing settings: ' + (response.data.message || 'Unknown error'));
        }
    });
}
</script>

<?php
/**
 * Helper method to render field controls
 */
function render_field_control($field_config, $field_name, $field_id, $field_value) {
    switch ($field_config['type']) {
        case 'checkbox':
            printf(
                '<input type="checkbox" id="%s" name="%s" value="1" %s />',
                esc_attr($field_id),
                esc_attr($field_name),
                checked($field_value, 1, false)
            );
            break;
            
        case 'number':
            printf(
                '<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" />',
                esc_attr($field_id),
                esc_attr($field_name),
                esc_attr($field_value),
                esc_attr($field_config['min'] ?? ''),
                esc_attr($field_config['max'] ?? '')
            );
            break;
            
        case 'textarea':
            printf(
                '<textarea id="%s" name="%s" rows="4">%s</textarea>',
                esc_attr($field_id),
                esc_attr($field_name),
                esc_textarea($field_value)
            );
            break;
            
        case 'select':
            if (!empty($field_config['options'])) {
                printf('<select id="%s" name="%s">', esc_attr($field_id), esc_attr($field_name));
                foreach ($field_config['options'] as $option_value => $option_label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        selected($field_value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                echo '</select>';
            }
            break;
            
        case 'text':
        case 'url':
        case 'email':
        default:
            printf(
                '<input type="%s" id="%s" name="%s" value="%s" />',
                esc_attr($field_config['type']),
                esc_attr($field_id),
                esc_attr($field_name),
                esc_attr($field_value)
            );
            break;
    }
}
?>
