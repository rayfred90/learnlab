<?php
/**
 * Template Interface for Individual Lab Templates
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from shortcode
// $template - template object
// $atts - shortcode attributes
?>

<div class="sixlab-template-interface" data-template-id="<?php echo esc_attr($template->id); ?>">
    <div class="sixlab-template-card">
        <div class="sixlab-template-header">
            <div class="sixlab-template-icon">
                <?php
                $icon_class = 'fas fa-network-wired';
                switch ($template->provider_type) {
                    case 'gns3':
                        $icon_class = 'fas fa-network-wired';
                        break;
                    case 'guacamole':
                        $icon_class = 'fas fa-desktop';
                        break;
                    case 'eveng':
                        $icon_class = 'fas fa-cloud';
                        break;
                }
                ?>
                <i class="<?php echo esc_attr($icon_class); ?>"></i>
            </div>
            <div class="sixlab-template-info">
                <h3 class="sixlab-template-title"><?php echo esc_html($template->name); ?></h3>
                <div class="sixlab-template-meta">
                    <span class="sixlab-difficulty-badge difficulty-<?php echo esc_attr($template->difficulty_level); ?>">
                        <?php echo esc_html(ucfirst($template->difficulty_level)); ?>
                    </span>
                    <?php if ($template->estimated_duration): ?>
                        <span class="sixlab-duration">
                            <i class="fas fa-clock"></i>
                            <?php echo esc_html($template->estimated_duration); ?> min
                        </span>
                    <?php endif; ?>
                    <span class="sixlab-provider">
                        <i class="fas fa-server"></i>
                        <?php echo esc_html(ucfirst($template->provider_type)); ?>
                    </span>
                    <?php if ($template->is_featured): ?>
                        <span class="sixlab-featured-badge">
                            <i class="fas fa-star"></i>
                            Featured
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="sixlab-template-body">
            <div class="sixlab-template-description">
                <?php echo wp_kses_post(wpautop($template->description)); ?>
            </div>
            
            <?php if (!empty($template->learning_objectives)): ?>
                <div class="sixlab-learning-objectives">
                    <h4><i class="fas fa-bullseye"></i> Learning Objectives</h4>
                    <div class="sixlab-objectives-list">
                        <?php
                        $objectives = explode("\n", $template->learning_objectives);
                        foreach ($objectives as $objective) {
                            $objective = trim($objective);
                            if (!empty($objective)) {
                                echo '<div class="sixlab-objective"><i class="fas fa-check-circle"></i>' . esc_html($objective) . '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($template->prerequisites)): ?>
                <div class="sixlab-prerequisites">
                    <h4><i class="fas fa-info-circle"></i> Prerequisites</h4>
                    <div class="sixlab-prerequisites-content">
                        <?php echo wp_kses_post(wpautop($template->prerequisites)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($template->tags)): ?>
                <div class="sixlab-template-tags">
                    <?php
                    $tags = explode(',', $template->tags);
                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (!empty($tag)) {
                            echo '<span class="sixlab-tag">' . esc_html($tag) . '</span>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sixlab-template-actions">
            <?php if ($atts['show_preview'] === 'true'): ?>
                <button class="sixlab-btn sixlab-btn-secondary sixlab-preview-btn" 
                        data-template-id="<?php echo esc_attr($template->id); ?>">
                    <i class="fas fa-eye"></i>
                    <?php echo esc_html($atts['preview_text']); ?>
                </button>
            <?php endif; ?>
            
            <button class="sixlab-btn sixlab-btn-primary sixlab-start-lab-btn" 
                    data-template-id="<?php echo esc_attr($template->id); ?>"
                    data-provider="<?php echo esc_attr($template->provider_type); ?>">
                <i class="fas fa-play"></i>
                <?php echo esc_html($atts['start_text']); ?>
            </button>
        </div>
    </div>
    
    <!-- Preview Modal (if enabled) -->
    <?php if ($atts['show_preview'] === 'true'): ?>
        <div class="sixlab-preview-modal" id="sixlab-preview-modal-<?php echo esc_attr($template->id); ?>" style="display: none;">
            <div class="sixlab-modal-overlay"></div>
            <div class="sixlab-modal-content">
                <div class="sixlab-modal-header">
                    <h3>Lab Preview: <?php echo esc_html($template->name); ?></h3>
                    <button class="sixlab-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="sixlab-modal-body">
                    <div class="sixlab-preview-interface" style="height: <?php echo esc_attr($atts['height']); ?>;">
                        <!-- This will be populated with the lab interface in preview mode -->
                        <div class="sixlab-preview-loader">
                            <i class="fas fa-spinner fa-spin"></i>
                            Loading lab preview...
                        </div>
                    </div>
                </div>
                <div class="sixlab-modal-footer">
                    <button class="sixlab-btn sixlab-btn-secondary sixlab-modal-close">
                        Close Preview
                    </button>
                    <button class="sixlab-btn sixlab-btn-primary sixlab-start-full-lab" 
                            data-template-id="<?php echo esc_attr($template->id); ?>"
                            data-provider="<?php echo esc_attr($template->provider_type); ?>">
                        <i class="fas fa-rocket"></i>
                        Start Full Lab
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.sixlab-template-interface {
    max-width: 800px;
    margin: 20px auto;
}

.sixlab-template-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.sixlab-template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.sixlab-template-header {
    display: flex;
    align-items: center;
    padding: 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.sixlab-template-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}

.sixlab-template-icon i {
    font-size: 24px;
}

.sixlab-template-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.sixlab-template-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.sixlab-difficulty-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.difficulty-beginner {
    background: #10b981;
    color: white;
}

.difficulty-intermediate {
    background: #f59e0b;
    color: white;
}

.difficulty-advanced {
    background: #ef4444;
    color: white;
}

.sixlab-duration, .sixlab-provider {
    font-size: 14px;
    opacity: 0.9;
}

.sixlab-featured-badge {
    background: #fbbf24;
    color: #92400e;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.sixlab-template-body {
    padding: 24px;
}

.sixlab-template-description {
    font-size: 16px;
    line-height: 1.6;
    color: #4b5563;
    margin-bottom: 24px;
}

.sixlab-learning-objectives,
.sixlab-prerequisites {
    margin-bottom: 24px;
}

.sixlab-learning-objectives h4,
.sixlab-prerequisites h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
}

.sixlab-objectives-list {
    display: grid;
    gap: 8px;
}

.sixlab-objective {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
}

.sixlab-objective i {
    color: #10b981;
    font-size: 14px;
}

.sixlab-template-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.sixlab-tag {
    background: #f3f4f6;
    color: #6b7280;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
}

.sixlab-template-actions {
    display: flex;
    gap: 12px;
    padding: 24px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.sixlab-btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.sixlab-btn-primary {
    background: #3b82f6;
    color: white;
}

.sixlab-btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.sixlab-btn-secondary {
    background: #6b7280;
    color: white;
}

.sixlab-btn-secondary:hover {
    background: #4b5563;
}

/* Preview Modal */
.sixlab-preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
}

.sixlab-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.sixlab-modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    margin: 40px auto;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.sixlab-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.sixlab-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6b7280;
}

.sixlab-modal-body {
    flex: 1;
    padding: 24px;
}

.sixlab-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
}

.sixlab-preview-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .sixlab-template-header {
        flex-direction: column;
        text-align: center;
    }
    
    .sixlab-template-icon {
        margin-right: 0;
        margin-bottom: 16px;
    }
    
    .sixlab-template-actions {
        flex-direction: column;
    }
    
    .sixlab-btn {
        justify-content: center;
    }
}
</style>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Preview button click
        $('.sixlab-preview-btn').on('click', function() {
            const templateId = $(this).data('template-id');
            const modal = $('#sixlab-preview-modal-' + templateId);
            modal.show();
            
            // Load preview content
            loadLabPreview(templateId);
        });
        
        // Modal close
        $('.sixlab-modal-close, .sixlab-modal-overlay').on('click', function() {
            $(this).closest('.sixlab-preview-modal').hide();
        });
        
        // Start lab button click
        $('.sixlab-start-lab-btn, .sixlab-start-full-lab').on('click', function() {
            const templateId = $(this).data('template-id');
            const provider = $(this).data('provider');
            
            startLabSession(templateId, provider);
        });
    });
    
    function loadLabPreview(templateId) {
        const previewContainer = $('#sixlab-preview-modal-' + templateId + ' .sixlab-preview-interface');
        
        $.ajax({
            url: sixlab_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sixlab_get_lab_preview',
                template_id: templateId,
                nonce: sixlab_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    previewContainer.html(response.data.html);
                } else {
                    previewContainer.html('<div class="sixlab-error">Failed to load preview: ' + response.data + '</div>');
                }
            },
            error: function() {
                previewContainer.html('<div class="sixlab-error">Failed to load preview.</div>');
            }
        });
    }
    
    function startLabSession(templateId, provider) {
        // Show loading state
        const button = $('[data-template-id="' + templateId + '"].sixlab-start-lab-btn, [data-template-id="' + templateId + '"].sixlab-start-full-lab');
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.ajax({
            url: sixlab_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sixlab_start_session',
                template_id: templateId,
                provider: provider,
                nonce: sixlab_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to lab interface or open in new window
                    window.location.href = sixlab_ajax.site_url + '/sixlab-workspace?session=' + response.data.session_id;
                } else {
                    alert('Failed to start lab: ' + response.data);
                    button.prop('disabled', false).html('<i class="fas fa-play"></i> Start Lab');
                }
            },
            error: function() {
                alert('Failed to start lab. Please try again.');
                button.prop('disabled', false).html('<i class="fas fa-play"></i> Start Lab');
            }
        });
    }
    
})(jQuery);
</script>
