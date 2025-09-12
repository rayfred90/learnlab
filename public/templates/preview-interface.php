<?php
/**
 * Preview Interface Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$template_id = get_query_var('template');

// Fallback to $_GET if query var is not working
if (!$template_id && isset($_GET['template'])) {
    $template_id = sanitize_text_field($_GET['template']);
}

$template = null;

if ($template_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sixlab_lab_templates';
    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $template_id));
}

if (!$template) {
    wp_die(__('Template not found.', 'sixlab-tool'));
}

// Get WordPress header
get_header();
?>

<div class="sixlab-preview-page">
    <div class="container">
        <div class="preview-header">
            <h1><?php echo esc_html($template->name); ?></h1>
            <div class="preview-meta">
                <span class="difficulty"><?php echo esc_html($template->difficulty_level); ?></span>
                <span class="provider"><?php echo esc_html(ucfirst($template->provider_type)); ?></span>
                <span class="duration"><?php echo esc_html($template->estimated_duration); ?> min</span>
            </div>
        </div>
        
        <div class="preview-content">
            <div class="preview-description">
                <?php echo wp_kses_post($template->description); ?>
            </div>
            
            <div class="preview-objectives">
                <h3><?php _e('Learning Objectives', 'sixlab-tool'); ?></h3>
                <?php
                $objectives = json_decode($template->learning_objectives, true);
                if ($objectives && is_array($objectives)) {
                    echo '<ul>';
                    foreach ($objectives as $objective) {
                        echo '<li>' . esc_html($objective) . '</li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
            
            <div class="preview-actions">
                <a href="#" class="btn btn-primary start-lab-btn" data-template-id="<?php echo esc_attr($template->id); ?>">
                    <?php _e('Start Lab', 'sixlab-tool'); ?>
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <?php _e('Go Back', 'sixlab-tool'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.sixlab-preview-page {
    padding: 40px 0;
    min-height: 60vh;
}

.sixlab-preview-page .container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.preview-header {
    text-align: center;
    margin-bottom: 40px;
}

.preview-header h1 {
    font-size: 2.5em;
    margin-bottom: 20px;
    color: #2c3e50;
}

.preview-meta {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.preview-meta span {
    background: #ecf0f1;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.preview-meta .difficulty {
    background: #3498db;
    color: white;
}

.preview-meta .provider {
    background: #27ae60;
    color: white;
}

.preview-meta .duration {
    background: #e74c3c;
    color: white;
}

.preview-content {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.preview-description {
    margin-bottom: 30px;
    line-height: 1.6;
    font-size: 16px;
}

.preview-objectives h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.preview-objectives ul {
    list-style-type: disc;
    padding-left: 20px;
    margin-bottom: 30px;
}

.preview-objectives li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.preview-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #ecf0f1;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    margin: 0 10px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    color: white;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.querySelector('.start-lab-btn');
    
    if (startBtn) {
        startBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const templateId = this.dataset.templateId;
            
            // Start lab session via AJAX
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_start_session',
                    nonce: '<?php echo wp_create_nonce('sixlab_nonce'); ?>',
                    template_id: templateId,
                    provider: '<?php echo $template && isset($template->provider_type) ? esc_js($template->provider_type) : ''; ?>'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Session start response:', data);
                if (data.success) {
                    // Redirect to workspace
                    window.location.href = '/sixlab-workspace?session=' + data.data.session_id;
                } else {
                    alert('Error starting lab: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to start lab session: ' + error.message);
            });
        });
    }
});
</script>

<?php
// Get WordPress footer
get_footer();
?>
