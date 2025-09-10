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
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="template_name"><?php _e('Template Name', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="template_name" name="template_name" 
                                       class="regular-text" required>
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
                                          rows="3" class="large-text"></textarea>
                                <p class="description">
                                    <?php _e('Brief description of what students will learn.', 'sixlab-tool'); ?>
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
                                    <option value="gns3"><?php _e('GNS3', 'sixlab-tool'); ?></option>
                                    <option value="guacamole"><?php _e('Apache Guacamole', 'sixlab-tool'); ?></option>
                                    <option value="eveng"><?php _e('EVE-NG', 'sixlab-tool'); ?></option>
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
                                    <option value="beginner"><?php _e('Beginner', 'sixlab-tool'); ?></option>
                                    <option value="intermediate"><?php _e('Intermediate', 'sixlab-tool'); ?></option>
                                    <option value="advanced"><?php _e('Advanced', 'sixlab-tool'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="estimated_duration"><?php _e('Estimated Duration', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="estimated_duration" name="estimated_duration" 
                                       min="1" max="480" class="small-text"> 
                                <?php _e('minutes', 'sixlab-tool'); ?>
                                <p class="description">
                                    <?php _e('How long should this lab take to complete?', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="learning_objectives"><?php _e('Learning Objectives', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="learning_objectives" name="learning_objectives" 
                                          rows="5" class="large-text"></textarea>
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
                                          rows="10" class="large-text"></textarea>
                                <p class="description">
                                    <?php _e('Step-by-step instructions for the lab.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template_data"><?php _e('Template Configuration', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <textarea id="template_data" name="template_data" 
                                          rows="10" class="large-text" placeholder='{"topology": {}, "devices": [], "configuration": {}}'></textarea>
                                <p class="description">
                                    <?php _e('JSON configuration for the lab template.', 'sixlab-tool'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="tags"><?php _e('Tags', 'sixlab-tool'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="tags" name="tags" class="regular-text" 
                                       placeholder="networking, cisco, routing, switching">
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
</style>
