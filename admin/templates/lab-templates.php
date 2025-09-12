<?php
/**
 * Lab Templates Admin Page Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$active_tab = $_GET['tab'] ?? 'overview';

// Load template data for editing
$template_data = null;
if ($active_tab === 'edit' && isset($_GET['template_id'])) {
    global $wpdb;
    $template_id = intval($_GET['template_id']);
    $template_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sixlab_lab_templates WHERE id = %d",
        $template_id
    ));
    
    if (!$template_data) {
        wp_die(__('Template not found.', 'sixlab-tool'));
    }
}
?>

<div class="wrap">
    <h1><?php _e('Lab Templates', 'sixlab-tool'); ?></h1>
    
    <?php settings_errors('sixlab_templates'); ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=sixlab-templates&tab=overview" 
           class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Overview', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-templates&tab=create" 
           class="nav-tab <?php echo $active_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Create Template', 'sixlab-tool'); ?>
        </a>
        <a href="?page=sixlab-templates&tab=import" 
           class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Import/Export', 'sixlab-tool'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($active_tab === 'overview'): ?>
            <div class="sixlab-templates-overview">
                <h2><?php _e('Lab Templates Management', 'sixlab-tool'); ?></h2>
                <p><?php _e('Create, edit, and manage lab templates for your students.', 'sixlab-tool'); ?></p>
                
                <div class="sixlab-templates-actions">
                    <a href="?page=sixlab-templates&tab=create" class="button button-primary">
                        <?php _e('Create New Template', 'sixlab-tool'); ?>
                    </a>
                    <a href="?page=sixlab-templates&tab=import" class="button">
                        <?php _e('Import Templates', 'sixlab-tool'); ?>
                    </a>
                </div>
                
                <div class="sixlab-templates-list">
                    <h3><?php _e('Existing Templates', 'sixlab-tool'); ?></h3>
                    
                    <?php if (!empty($templates)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Name', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Provider', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Difficulty', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Duration', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Usage', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Status', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Shortcode', 'sixlab-tool'); ?></th>
                                    <th><?php _e('Actions', 'sixlab-tool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($template->name); ?></strong>
                                            <?php if ($template->is_featured): ?>
                                                <span class="sixlab-featured-badge"><?php _e('Featured', 'sixlab-tool'); ?></span>
                                            <?php endif; ?>
                                            <br>
                                            <small><?php echo esc_html($template->description); ?></small>
                                        </td>
                                        <td><?php echo esc_html(ucfirst($template->provider_type)); ?></td>
                                        <td>
                                            <span class="sixlab-difficulty-badge difficulty-<?php echo esc_attr($template->difficulty_level); ?>">
                                                <?php echo esc_html(ucfirst($template->difficulty_level)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($template->estimated_duration): ?>
                                                <?php echo esc_html($template->estimated_duration); ?> <?php _e('minutes', 'sixlab-tool'); ?>
                                            <?php else: ?>
                                                <span class="dashicons dashicons-minus"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($template->usage_count); ?></td>
                                        <td>
                                            <?php if ($template->is_active): ?>
                                                <span class="sixlab-status-active"><?php _e('Active', 'sixlab-tool'); ?></span>
                                            <?php else: ?>
                                                <span class="sixlab-status-inactive"><?php _e('Inactive', 'sixlab-tool'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="sixlab-shortcode-container">
                                                <code class="sixlab-shortcode" 
                                                      data-shortcode="[sixlab_template template_id=&quot;<?php echo esc_attr($template->id); ?>&quot;]"
                                                      title="<?php esc_attr_e('Use this shortcode to embed the lab template in any page or post', 'sixlab-tool'); ?>">
                                                    [sixlab_template template_id="<?php echo esc_attr($template->id); ?>"]
                                                </code>
                                                <button type="button" class="button button-small sixlab-copy-shortcode" 
                                                        data-shortcode="[sixlab_template template_id=&quot;<?php echo esc_attr($template->id); ?>&quot;]"
                                                        title="<?php esc_attr_e('Copy shortcode to clipboard', 'sixlab-tool'); ?>">
                                                    <span class="dashicons dashicons-admin-page"></span>
                                                </button>
                                            </div>
                                            <small style="display: block; margin-top: 4px; color: #666;">
                                                <?php _e('Copy and paste this shortcode into any page or post', 'sixlab-tool'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(site_url('/sixlab-preview?template=' . $template->id)); ?>" 
                                               class="button button-small" target="_blank" 
                                               title="<?php esc_attr_e('Preview template', 'sixlab-tool'); ?>">
                                                <span class="dashicons dashicons-visibility"></span> <?php _e('Preview', 'sixlab-tool'); ?>
                                            </a>
                                            <a href="?page=sixlab-templates&tab=edit&template_id=<?php echo esc_attr($template->id); ?>" 
                                               class="button button-small">
                                                <?php _e('Edit', 'sixlab-tool'); ?>
                                            </a>
                                            <a href="#" class="button button-small sixlab-duplicate-template" 
                                               data-template-id="<?php echo esc_attr($template->id); ?>">
                                                <?php _e('Duplicate', 'sixlab-tool'); ?>
                                            </a>
                                            <?php if ($template->is_active): ?>
                                                <a href="#" class="button button-small sixlab-deactivate-template" 
                                                   data-template-id="<?php echo esc_attr($template->id); ?>">
                                                    <?php _e('Deactivate', 'sixlab-tool'); ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="button button-small sixlab-activate-template" 
                                                   data-template-id="<?php echo esc_attr($template->id); ?>">
                                                    <?php _e('Activate', 'sixlab-tool'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <a href="#" class="button button-small button-link-delete sixlab-delete-template" 
                                               data-template-id="<?php echo esc_attr($template->id); ?>"
                                               data-template-name="<?php echo esc_attr($template->name); ?>"
                                               title="<?php esc_attr_e('Delete template permanently', 'sixlab-tool'); ?>">
                                                <?php _e('Delete', 'sixlab-tool'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="sixlab-empty-state">
                            <h3><?php _e('No Lab Templates Found', 'sixlab-tool'); ?></h3>
                            <p><?php _e('Get started by creating your first lab template or importing existing ones.', 'sixlab-tool'); ?></p>
                            <a href="?page=sixlab-templates&tab=create" class="button button-primary">
                                <?php _e('Create Your First Template', 'sixlab-tool'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($active_tab === 'create' || $active_tab === 'edit'): ?>
            <div class="sixlab-template-form">
                <h2>
                    <?php echo $active_tab === 'edit' ? __('Edit Lab Template', 'sixlab-tool') : __('Create New Lab Template', 'sixlab-tool'); ?>
                </h2>
                
                <form method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('sixlab_templates_nonce'); ?>
                    <?php if ($active_tab === 'edit' && $template_data): ?>
                        <input type="hidden" name="template_id" value="<?php echo esc_attr($template_data->id); ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="template_name"><?php _e('Template Name', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="template_name" name="template_name" 
                                       class="regular-text" required
                                       value="<?php echo $template_data ? esc_attr($template_data->name) : ''; ?>">
                                <p class="description">
                                    <?php _e('Enter a descriptive name for this lab template.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template_description"><?php _e('Description', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="template_description" name="template_description" 
                                          rows="3" class="large-text"><?php echo $template_data ? esc_textarea($template_data->description) : ''; ?></textarea>
                                <p class="description">
                                    <?php _e('Brief description of what students will learn.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template_type"><?php _e('Template Type', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="template_type" name="template_type" required onchange="toggleTemplateTypeFields()">
                                    <option value=""><?php _e('Select Template Type', 'sixlab-tool'); ?></option>
                                    <option value="guided" <?php selected($template_data ? $template_data->template_type : '', 'guided'); ?>><?php _e('Guided Lab', 'sixlab-tool'); ?></option>
                                    <option value="non_guided" <?php selected($template_data ? $template_data->template_type : '', 'non_guided'); ?>><?php _e('Non-Guided Lab', 'sixlab-tool'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Choose the type of lab template. Guided labs provide step-by-step instructions with terminal commands. Non-guided labs provide open instructions with startup and verification scripts.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="provider_type"><?php _e('Provider Type', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="provider_type" name="provider_type" required>
                                    <option value=""><?php _e('Select Provider', 'sixlab-tool'); ?></option>
                                    <option value="gns3" <?php selected($template_data ? $template_data->provider_type : '', 'gns3'); ?>><?php _e('GNS3', 'sixlab-tool'); ?></option>
                                    <option value="guacamole" <?php selected($template_data ? $template_data->provider_type : '', 'guacamole'); ?>><?php _e('Apache Guacamole', 'sixlab-tool'); ?></option>
                                    <option value="eveng" <?php selected($template_data ? $template_data->provider_type : '', 'eveng'); ?>><?php _e('EVE-NG', 'sixlab-tool'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Choose the lab provider for this template.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="difficulty_level"><?php _e('Difficulty Level', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <select id="difficulty_level" name="difficulty_level">
                                    <option value="beginner" <?php selected($template_data ? $template_data->difficulty_level : '', 'beginner'); ?>><?php _e('Beginner', 'sixlab-tool'); ?></option>
                                    <option value="intermediate" <?php selected($template_data ? $template_data->difficulty_level : '', 'intermediate'); ?>><?php _e('Intermediate', 'sixlab-tool'); ?></option>
                                    <option value="advanced" <?php selected($template_data ? $template_data->difficulty_level : '', 'advanced'); ?>><?php _e('Advanced', 'sixlab-tool'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="estimated_duration"><?php _e('Estimated Duration', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <div class="duration-fields">
                                    <div class="duration-field">
                                        <label for="estimated_duration"><?php _e('Duration (minutes):', 'sixlab-tool'); ?></label>
                                        <input type="number" id="estimated_duration" name="estimated_duration" 
                                               min="1" max="1440" class="small-text"
                                               value="<?php echo $template_data ? esc_attr($template_data->estimated_duration) : ''; ?>"> 
                                        <?php _e('minutes', 'sixlab-tool'); ?>
                                    </div>
                                    
                                    <div class="duration-field">
                                        <label for="lab_start_date"><?php _e('Lab Start Date (optional):', 'sixlab-tool'); ?></label>
                                        <input type="date" id="lab_start_date" name="lab_start_date" 
                                               value="<?php echo $template_data ? esc_attr($template_data->lab_start_date ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="duration-field">
                                        <label for="lab_start_time"><?php _e('Lab Start Time (optional):', 'sixlab-tool'); ?></label>
                                        <input type="time" id="lab_start_time" name="lab_start_time" 
                                               value="<?php echo $template_data ? esc_attr($template_data->lab_start_time ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="duration-field">
                                        <label for="lab_end_date"><?php _e('Lab End Date (optional):', 'sixlab-tool'); ?></label>
                                        <input type="date" id="lab_end_date" name="lab_end_date" 
                                               value="<?php echo $template_data ? esc_attr($template_data->lab_end_date ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="duration-field">
                                        <label for="lab_end_time"><?php _e('Lab End Time (optional):', 'sixlab-tool'); ?></label>
                                        <input type="time" id="lab_end_time" name="lab_end_time" 
                                               value="<?php echo $template_data ? esc_attr($template_data->lab_end_time ?? '') : ''; ?>">
                                    </div>
                                </div>
                                <p class="description">
                                    <?php _e('Set the estimated duration in minutes and optionally specify start/end dates and times for scheduled labs.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="learning_objectives"><?php _e('Learning Objectives', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="learning_objectives" name="learning_objectives" 
                                          rows="5" class="large-text"><?php 
                                    if ($template_data && !empty($template_data->learning_objectives)) {
                                        $objectives = json_decode($template_data->learning_objectives, true);
                                        if (is_array($objectives)) {
                                            echo esc_textarea(implode("\n", $objectives));
                                        } else {
                                            echo esc_textarea($template_data->learning_objectives);
                                        }
                                    }
                                ?></textarea>
                                <p class="description">
                                    <?php _e('What will students learn from this lab? One objective per line.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="instructions"><?php _e('Lab Instructions', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="instructions" name="instructions" 
                                          rows="10" class="large-text"><?php echo $template_data ? esc_textarea($template_data->instructions) : ''; ?></textarea>
                                <p class="description">
                                    <?php _e('General instructions for the lab (visible for all template types).', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Guided Lab Specific Fields -->
                        <div id="guided_lab_fields" style="display: none;">
                            <tr class="guided-field">
                                <th scope="row">
                                    <label for="guided_steps"><?php _e('Guided Steps', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <div id="guided_steps_container">
                                        <div class="guided-step-item">
                                            <h4><?php _e('Step 1', 'sixlab-tool'); ?></h4>
                                            <label><?php _e('Step Title:', 'sixlab-tool'); ?></label>
                                            <input type="text" name="guided_steps[0][title]" class="regular-text" placeholder="<?php esc_attr_e('Enter step title', 'sixlab-tool'); ?>">
                                            
                                            <label><?php _e('Step Instructions:', 'sixlab-tool'); ?></label>
                                            <textarea name="guided_steps[0][instructions]" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter step instructions', 'sixlab-tool'); ?>"></textarea>
                                            
                                            <label><?php _e('Terminal Commands (one per line):', 'sixlab-tool'); ?></label>
                                            <textarea name="guided_steps[0][commands]" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter terminal commands for this step', 'sixlab-tool'); ?>"></textarea>
                                            
                                            <label><?php _e('Expected Output/Validation:', 'sixlab-tool'); ?></label>
                                            <textarea name="guided_steps[0][validation]" rows="2" class="large-text" placeholder="<?php esc_attr_e('Enter expected output or validation criteria', 'sixlab-tool'); ?>"></textarea>
                                            
                                            <button type="button" class="button remove-step" onclick="removeGuidedStep(this)"><?php _e('Remove Step', 'sixlab-tool'); ?></button>
                                        </div>
                                    </div>
                                    <button type="button" class="button" onclick="addGuidedStep()"><?php _e('Add Step', 'sixlab-tool'); ?></button>
                                    <p class="description">
                                        <?php _e('Define step-by-step instructions with terminal commands for guided labs.', 'sixlab-tool'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr class="guided-field">
                                <th scope="row">
                                    <label for="guided_delete_reset_script"><?php _e('Delete/Reset Script (Guided)', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <textarea id="guided_delete_reset_script" name="guided_delete_reset_script"
                                              rows="8" class="large-text" placeholder="#!/bin/bash&#10;# Delete/reset script for guided lab cleanup&#10;# This script will run when student clicks the reset button&#10;# Available variables: $wp_username, $session_id, $current_step"><?php echo $template_data ? esc_textarea($template_data->guided_delete_reset_script ?? '') : ''; ?></textarea>
                                    <p class="description">
                                        <?php _e('Script to clean up and reset the guided lab environment between steps. This will be executed when the student clicks the reset button.', 'sixlab-tool'); ?>
                                    </p>
                                    
                                    <label for="guided_delete_reset_script_file"><?php _e('Or upload delete/reset script:', 'sixlab-tool'); ?></label>
                                    <input type="file" id="guided_delete_reset_script_file" name="guided_delete_reset_script_file" accept=".sh,.py,.js,.pl">
                                </td>
                            </tr>
                        </div>
                        
                        <!-- Non-Guided Lab Specific Fields -->
                        <div id="non_guided_lab_fields" style="display: none;">
                            <tr class="non-guided-field">
                                <th scope="row">
                                    <label for="instructions_content"><?php _e('Detailed Instructions', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <?php 
                                    $content = $template_data ? $template_data->instructions_content : '';
                                    wp_editor($content, 'instructions_content', array(
                                        'textarea_name' => 'instructions_content',
                                        'media_buttons' => true,
                                        'textarea_rows' => 15,
                                        'teeny' => false,
                                        'quicktags' => true,
                                        'tinymce' => array(
                                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,undo,redo',
                                            'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media,|,fullscreen'
                                        )
                                    )); 
                                    ?>
                                    <p class="description">
                                        <?php _e('Rich text instructions for non-guided labs. Students will see these instructions during the lab.', 'sixlab-tool'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr class="non-guided-field">
                                <th scope="row">
                                    <label for="startup_script"><?php _e('Startup Script', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <textarea id="startup_script" name="startup_script" 
                                              rows="10" class="large-text" placeholder="#!/bin/bash&#10;# Startup script will be executed when student clicks Start button&#10;# Available variables: $wp_username, $session_id"><?php echo $template_data ? esc_textarea($template_data->startup_script) : ''; ?></textarea>
                                    <p class="description">
                                        <?php _e('Script that runs automatically when the user starts the lab. Available variables: $wp_username, $session_id', 'sixlab-tool'); ?>
                                    </p>
                                    
                                    <label for="startup_script_file"><?php _e('Or upload startup script:', 'sixlab-tool'); ?></label>
                                    <input type="file" id="startup_script_file" name="startup_script_file" accept=".sh,.py,.js,.pl">
                                </td>
                            </tr>
                            
                            <tr class="non-guided-field">
                                <th scope="row">
                                    <label for="verification_script"><?php _e('Verification Script', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <textarea id="verification_script" name="verification_script" 
                                              rows="10" class="large-text" placeholder="#!/bin/bash&#10;# Verification script for checking student's work&#10;# Should output JSON with status and feedback&#10;# Available variables: $wp_username, $session_id"><?php echo $template_data ? esc_textarea($template_data->verification_script) : ''; ?></textarea>
                                    <p class="description">
                                        <?php _e('Script to verify student work. Should output JSON with verification results and AI feedback.', 'sixlab-tool'); ?>
                                    </p>
                                    
                                    <label for="verification_script_file"><?php _e('Or upload verification script:', 'sixlab-tool'); ?></label>
                                    <input type="file" id="verification_script_file" name="verification_script_file" accept=".sh,.py,.js,.pl">
                                </td>
                            </tr>
                            
                            <tr class="non-guided-field">
                                <th scope="row">
                                    <label for="delete_reset_script"><?php _e('Delete/Reset Script (Non-Guided)', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <textarea id="delete_reset_script" name="delete_reset_script"
                                              rows="10" class="large-text" placeholder="#!/bin/bash&#10;# Delete/reset script for cleaning up lab environment&#10;# This script will run when student clicks the reset button&#10;# Available variables: $wp_username, $session_id"><?php echo $template_data ? esc_textarea($template_data->delete_reset_script ?? '') : ''; ?></textarea>
                                    <p class="description">
                                        <?php _e('Script to clean up and reset the entire lab environment. This will be executed when the student clicks the reset button.', 'sixlab-tool'); ?>
                                    </p>
                                    
                                    <label for="delete_reset_script_file"><?php _e('Or upload delete/reset script:', 'sixlab-tool'); ?></label>
                                    <input type="file" id="delete_reset_script_file" name="delete_reset_script_file" accept=".sh,.py,.js,.pl">
                                </td>
                            </tr>
                        </div>
                        
                        <tr>
                            <th scope="row">
                                <label for="tags"><?php _e('Tags', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="tags" name="tags" class="regular-text" 
                                       placeholder="networking, cisco, routing, switching"
                                       value="<?php echo $template_data ? esc_attr($template_data->tags) : ''; ?>">
                                <p class="description">
                                    <?php _e('Comma-separated tags for easy searching.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Options', 'sixlab-tool'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <?php _e('Active (available to students)', 'sixlab-tool'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="is_featured" value="1">
                                    <?php _e('Featured template', 'sixlab-tool'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button($active_tab === 'edit' ? __('Update Template', 'sixlab-tool') : __('Create Template', 'sixlab-tool')); ?>
                </form>
            </div>
            
        <?php elseif ($active_tab === 'import'): ?>
            <div class="sixlab-import-export">
                <h2><?php _e('Import/Export Lab Templates', 'sixlab-tool'); ?></h2>
                
                <div class="sixlab-import-section">
                    <h3><?php _e('Import Templates', 'sixlab-tool'); ?></h3>
                    <p><?php _e('Upload a JSON file containing lab templates to import.', 'sixlab-tool'); ?></p>
                    
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('sixlab_import_templates_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php _e('Template File', 'sixlab-tool'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="import_file" name="import_file" 
                                           accept=".json" required>
                                    <p class="description">
                                        <?php _e('Select a JSON file containing lab templates.', 'sixlab-tool'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Import Templates', 'sixlab-tool'), 'primary', 'import_templates'); ?>
                    </form>
                </div>
                
                <div class="sixlab-export-section">
                    <h3><?php _e('Export Templates', 'sixlab-tool'); ?></h3>
                    <p><?php _e('Download your lab templates as a JSON file for backup or sharing.', 'sixlab-tool'); ?></p>
                    
                    <form method="post">
                        <?php wp_nonce_field('sixlab_export_templates_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Export Options', 'sixlab-tool'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="export_active_only" value="1">
                                        <?php _e('Export only active templates', 'sixlab-tool'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="export_featured_only" value="1">
                                        <?php _e('Export only featured templates', 'sixlab-tool'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Export Templates', 'sixlab-tool'), 'secondary', 'export_templates'); ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.sixlab-featured-badge {
    background: #ff6b35;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 5px;
}

.sixlab-difficulty-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    color: white;
}

.difficulty-beginner { background: #28a745; }
.difficulty-intermediate { background: #ffc107; color: #333; }
.difficulty-advanced { background: #dc3545; }

.sixlab-status-active {
    color: #28a745;
    font-weight: bold;
}

.sixlab-status-inactive {
    color: #6c757d;
}

.sixlab-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border: 1px dashed #dee2e6;
    border-radius: 5px;
}

.sixlab-templates-actions {
    margin: 20px 0;
}

.sixlab-templates-actions .button {
    margin-right: 10px;
}

.sixlab-import-section,
.sixlab-export-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    border-radius: 5px;
}

/* Template Type Specific Styles */
.guided-step-item {
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
    background: #f9f9f9;
}

.guided-step-item h4 {
    margin-top: 0;
    color: #2271b1;
}

.guided-step-item label {
    display: block;
    margin: 10px 0 5px 0;
    font-weight: bold;
}

.guided-step-item .remove-step {
    margin-top: 10px;
    color: #d63638;
}

#guided_lab_fields,
#non_guided_lab_fields {
    width: 100%;
}

.template-type-section {
    border: 2px solid #2271b1;
    border-radius: 5px;
    padding: 20px;
    margin: 20px 0;
    background: #f0f6fc;
}
</style>

<script>
let stepCounter = 1;

function toggleTemplateTypeFields() {
    const templateType = document.getElementById('template_type').value;
    const guidedFields = document.getElementById('guided_lab_fields');
    const nonGuidedFields = document.getElementById('non_guided_lab_fields');
    
    if (templateType === 'guided') {
        guidedFields.style.display = 'table-row-group';
        nonGuidedFields.style.display = 'none';
        
        // Make guided fields required
        const guidedInputs = guidedFields.querySelectorAll('input, textarea');
        guidedInputs.forEach(input => {
            if (input.name.includes('[title]') || input.name.includes('[instructions]')) {
                input.setAttribute('required', 'required');
            }
        });
        
        // Remove required from non-guided fields
        const nonGuidedInputs = nonGuidedFields.querySelectorAll('input, textarea');
        nonGuidedInputs.forEach(input => {
            input.removeAttribute('required');
        });
        
    } else if (templateType === 'non_guided') {
        guidedFields.style.display = 'none';
        nonGuidedFields.style.display = 'table-row-group';
        
        // Remove required from guided fields
        const guidedInputs = guidedFields.querySelectorAll('input, textarea');
        guidedInputs.forEach(input => {
            input.removeAttribute('required');
        });
        
        // Make instructions_content required for non-guided
        const instructionsContent = document.getElementById('instructions_content');
        if (instructionsContent) {
            instructionsContent.setAttribute('required', 'required');
        }
        
    } else {
        guidedFields.style.display = 'none';
        nonGuidedFields.style.display = 'none';
        
        // Remove required from all type-specific fields
        const allInputs = document.querySelectorAll('#guided_lab_fields input, #guided_lab_fields textarea, #non_guided_lab_fields input, #non_guided_lab_fields textarea');
        allInputs.forEach(input => {
            input.removeAttribute('required');
        });
    }
}

function addGuidedStep() {
    const container = document.getElementById('guided_steps_container');
    const stepDiv = document.createElement('div');
    stepDiv.className = 'guided-step-item';
    stepDiv.innerHTML = `
        <h4><?php _e('Step', 'sixlab-tool'); ?> ${stepCounter + 1}</h4>
        <label><?php _e('Step Title:', 'sixlab-tool'); ?></label>
        <input type="text" name="guided_steps[${stepCounter}][title]" class="regular-text" placeholder="<?php esc_attr_e('Enter step title', 'sixlab-tool'); ?>" required>
        
        <label><?php _e('Step Instructions:', 'sixlab-tool'); ?></label>
        <textarea name="guided_steps[${stepCounter}][instructions]" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter step instructions', 'sixlab-tool'); ?>" required></textarea>
        
        <label><?php _e('Terminal Commands (one per line):', 'sixlab-tool'); ?></label>
        <textarea name="guided_steps[${stepCounter}][commands]" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter terminal commands for this step', 'sixlab-tool'); ?>"></textarea>
        
        <label><?php _e('Expected Output/Validation:', 'sixlab-tool'); ?></label>
        <textarea name="guided_steps[${stepCounter}][validation]" rows="2" class="large-text" placeholder="<?php esc_attr_e('Enter expected output or validation criteria', 'sixlab-tool'); ?>"></textarea>
        
        <button type="button" class="button remove-step" onclick="removeGuidedStep(this)"><?php _e('Remove Step', 'sixlab-tool'); ?></button>
    `;
    container.appendChild(stepDiv);
    stepCounter++;
}

function removeGuidedStep(button) {
    const stepDiv = button.closest('.guided-step-item');
    stepDiv.remove();
    
    // Renumber remaining steps
    const steps = document.querySelectorAll('.guided-step-item');
    steps.forEach((step, index) => {
        const title = step.querySelector('h4');
        title.textContent = '<?php _e('Step', 'sixlab-tool'); ?> ' + (index + 1);
        
        // Update input names
        const inputs = step.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            if (input.name.startsWith('guided_steps[')) {
                const fieldName = input.name.match(/\[([^\]]+)\]$/)[1];
                input.name = `guided_steps[${index}][${fieldName}]`;
            }
        });
    });
    
    stepCounter = steps.length;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleTemplateTypeFields();
    
    // Load existing guided steps if editing
    <?php if ($template_data && $template_data->guided_steps): ?>
    const existingSteps = <?php echo json_encode(json_decode($template_data->guided_steps, true) ?? []); ?>;
    if (existingSteps && existingSteps.length > 0) {
        const container = document.getElementById('guided_steps_container');
        container.innerHTML = '';
        
        existingSteps.forEach((step, index) => {
            const stepDiv = document.createElement('div');
            stepDiv.className = 'guided-step-item';
            stepDiv.innerHTML = `
                <h4><?php _e('Step', 'sixlab-tool'); ?> ${index + 1}</h4>
                <label><?php _e('Step Title:', 'sixlab-tool'); ?></label>
                <input type="text" name="guided_steps[${index}][title]" class="regular-text" value="${step.title || ''}" required>
                
                <label><?php _e('Step Instructions:', 'sixlab-tool'); ?></label>
                <textarea name="guided_steps[${index}][instructions]" rows="3" class="large-text" required>${step.instructions || ''}</textarea>
                
                <label><?php _e('Terminal Commands (one per line):', 'sixlab-tool'); ?></label>
                <textarea name="guided_steps[${index}][commands]" rows="3" class="large-text">${step.commands || ''}</textarea>
                
                <label><?php _e('Expected Output/Validation:', 'sixlab-tool'); ?></label>
                <textarea name="guided_steps[${index}][validation]" rows="2" class="large-text">${step.validation || ''}</textarea>
                
                <button type="button" class="button remove-step" onclick="removeGuidedStep(this)"><?php _e('Remove Step', 'sixlab-tool'); ?></button>
            `;
            container.appendChild(stepDiv);
        });
        
        stepCounter = existingSteps.length;
    }
    <?php endif; ?>
    
    // Date/time validation
    const startDateInput = document.getElementById('lab_start_date');
    const startTimeInput = document.getElementById('lab_start_time');
    const endDateInput = document.getElementById('lab_end_date');
    const endTimeInput = document.getElementById('lab_end_time');
    
    function validateDateTime() {
        if (!startDateInput.value || !endDateInput.value) {
            return true; // Optional fields
        }
        
        const startDate = new Date(startDateInput.value + ' ' + (startTimeInput.value || '00:00'));
        const endDate = new Date(endDateInput.value + ' ' + (endTimeInput.value || '23:59'));
        
        if (endDate <= startDate) {
            alert('<?php _e('End date/time must be after start date/time.', 'sixlab-tool'); ?>');
            return false;
        }
        
        return true;
    }
    
    // Add validation to date/time inputs
    [startDateInput, startTimeInput, endDateInput, endTimeInput].forEach(input => {
        if (input) {
            input.addEventListener('change', validateDateTime);
        }
    });
    
    // Add validation to form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateDateTime()) {
                e.preventDefault();
            }
        });
    }
    
    // Template deletion functionality
    const deleteButtons = document.querySelectorAll('.sixlab-delete-template');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const templateId = this.dataset.templateId;
            const templateName = this.dataset.templateName;
            
            if (confirm('<?php _e('Are you sure you want to permanently delete the template', 'sixlab-tool'); ?> "' + templateName + '"? <?php _e('This action cannot be undone.', 'sixlab-tool'); ?>')) {
                // Show loading state
                this.style.opacity = '0.5';
                this.textContent = '<?php _e('Deleting...', 'sixlab-tool'); ?>';
                
                // Make AJAX request
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'sixlab_delete_template',
                        template_id: templateId,
                        nonce: '<?php echo wp_create_nonce('sixlab_delete_template'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        this.closest('tr').remove();
                        
                        // Show success message
                        const notice = document.createElement('div');
                        notice.className = 'notice notice-success is-dismissible';
                        notice.innerHTML = '<p>' + data.data.message + '</p>';
                        document.querySelector('.wrap h1').after(notice);
                        
                        // Auto-dismiss notice after 3 seconds
                        setTimeout(() => {
                            notice.remove();
                        }, 3000);
                    } else {
                        alert('<?php _e('Error deleting template:', 'sixlab-tool'); ?> ' + data.data.message);
                        // Restore button state
                        this.style.opacity = '1';
                        this.textContent = '<?php _e('Delete', 'sixlab-tool'); ?>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('<?php _e('An error occurred while deleting the template.', 'sixlab-tool'); ?>');
                    // Restore button state
                    this.style.opacity = '1';
                    this.textContent = '<?php _e('Delete', 'sixlab-tool'); ?>';
                });
            }
        });
    });
});
</script>
