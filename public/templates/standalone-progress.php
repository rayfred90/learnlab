<?php
/**
 * Standalone Progress Interface Template
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get WordPress header
get_header();

// Get user data
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

if (!$user_id) {
    wp_die(__('You must be logged in to view your progress.', 'sixlab-tool'));
}

// Get user progress data
global $wpdb;
$sessions_table = $wpdb->prefix . 'sixlab_sessions';
$templates_table = $wpdb->prefix . 'sixlab_lab_templates';

// Get user statistics
$total_sessions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $sessions_table WHERE user_id = %d",
    $user_id
));

$completed_sessions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $sessions_table WHERE user_id = %d AND status = 'completed'",
    $user_id
));

$total_time = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) FROM $sessions_table 
     WHERE user_id = %d AND end_time IS NOT NULL",
    $user_id
));

// Get available templates
$templates = $wpdb->get_results("SELECT * FROM $templates_table ORDER BY created_at DESC");

// Get recent sessions
$recent_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, t.title as template_title, t.provider, t.difficulty 
     FROM $sessions_table s 
     LEFT JOIN $templates_table t ON s.template_id = t.id 
     WHERE s.user_id = %d 
     ORDER BY s.start_time DESC 
     LIMIT 10",
    $user_id
));
?>

<div class="sixlab-progress-standalone">
    <div class="container">
        <div class="progress-header">
            <h1><?php _e('SixLab Progress Dashboard', 'sixlab-tool'); ?></h1>
            <p><?php printf(__('Welcome back, %s! Here\'s your learning progress.', 'sixlab-tool'), esc_html($current_user->display_name)); ?></p>
        </div>
        
        <div class="progress-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo intval($total_sessions); ?></div>
                <div class="stat-label"><?php _e('Total Sessions', 'sixlab-tool'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo intval($completed_sessions); ?></div>
                <div class="stat-label"><?php _e('Completed Labs', 'sixlab-tool'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo intval($total_time ?: 0); ?></div>
                <div class="stat-label"><?php _e('Minutes Practiced', 'sixlab-tool'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_sessions > 0 ? round(($completed_sessions / $total_sessions) * 100) : 0; ?>%</div>
                <div class="stat-label"><?php _e('Success Rate', 'sixlab-tool'); ?></div>
            </div>
        </div>
        
        <div class="progress-content">
            <div class="content-section">
                <h2><?php _e('Available Lab Templates', 'sixlab-tool'); ?></h2>
                <div class="filter-controls">
                    <select id="difficulty-filter">
                        <option value=""><?php _e('All Difficulties', 'sixlab-tool'); ?></option>
                        <option value="beginner"><?php _e('Beginner', 'sixlab-tool'); ?></option>
                        <option value="intermediate"><?php _e('Intermediate', 'sixlab-tool'); ?></option>
                        <option value="advanced"><?php _e('Advanced', 'sixlab-tool'); ?></option>
                    </select>
                    <select id="provider-filter">
                        <option value=""><?php _e('All Providers', 'sixlab-tool'); ?></option>
                        <option value="gns3"><?php _e('GNS3', 'sixlab-tool'); ?></option>
                        <option value="guacamole"><?php _e('Guacamole', 'sixlab-tool'); ?></option>
                        <option value="eveng"><?php _e('EVE-NG', 'sixlab-tool'); ?></option>
                    </select>
                </div>
                
                <div class="templates-grid" id="templates-grid">
                    <?php if ($templates): ?>
                        <?php foreach ($templates as $template): ?>
                            <div class="template-card" data-difficulty="<?php echo esc_attr($template->difficulty_level); ?>" data-provider="<?php echo esc_attr($template->provider_type); ?>">
                                <div class="template-header">
                                    <h3><?php echo esc_html($template->name); ?></h3>
                                    <div class="template-meta">
                                        <span class="difficulty <?php echo esc_attr($template->difficulty_level); ?>">
                                            <?php echo esc_html(ucfirst($template->difficulty_level)); ?>
                                        </span>
                                        <span class="provider">
                                            <?php echo esc_html(ucfirst($template->provider_type)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="template-description">
                                    <?php echo wp_trim_words(wp_strip_all_tags($template->description), 20); ?>
                                </div>
                                <div class="template-actions">
                                    <a href="/sixlab-preview?template=<?php echo esc_attr($template->id); ?>" class="btn btn-preview">
                                        <?php _e('Preview', 'sixlab-tool'); ?>
                                    </a>
                                    <a href="#" class="btn btn-start" data-template-id="<?php echo esc_attr($template->id); ?>">
                                        <?php _e('Start Lab', 'sixlab-tool'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-templates"><?php _e('No lab templates available.', 'sixlab-tool'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="content-section">
                <h2><?php _e('Recent Sessions', 'sixlab-tool'); ?></h2>
                <div class="sessions-list">
                    <?php if ($recent_sessions): ?>
                        <?php foreach ($recent_sessions as $session): ?>
                            <div class="session-item">
                                <div class="session-info">
                                    <h4><?php echo esc_html($session->template_title ?: 'Unknown Template'); ?></h4>
                                    <div class="session-meta">
                                        <span class="status <?php echo esc_attr($session->status); ?>">
                                            <?php echo esc_html(ucfirst($session->status)); ?>
                                        </span>
                                        <span class="date">
                                            <?php echo date('M j, Y', strtotime($session->start_time)); ?>
                                        </span>
                                        <?php if ($session->provider): ?>
                                            <span class="provider">
                                                <?php echo esc_html(ucfirst($session->provider)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <?php if ($session->status === 'active'): ?>
                                        <a href="/sixlab-workspace?session=<?php echo esc_attr($session->id); ?>" class="btn btn-resume">
                                            <?php _e('Resume', 'sixlab-tool'); ?>
                                        </a>
                                    <?php elseif ($session->status === 'completed' && $session->score !== null): ?>
                                        <span class="score"><?php echo intval($session->score); ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-sessions"><?php _e('No sessions yet. Start your first lab!', 'sixlab-tool'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sixlab-progress-standalone {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 80vh;
}

.sixlab-progress-standalone .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.progress-header {
    text-align: center;
    margin-bottom: 40px;
}

.progress-header h1 {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 10px;
}

.progress-header p {
    font-size: 1.2em;
    color: #7f8c8d;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1.1em;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.content-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.content-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.8em;
}

.filter-controls {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.filter-controls select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: white;
    cursor: pointer;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.template-card {
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    padding: 20px;
    background: #fafafa;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.template-header h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.template-meta {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.template-meta span {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.difficulty.beginner { background: #27ae60; color: white; }
.difficulty.intermediate { background: #f39c12; color: white; }
.difficulty.advanced { background: #e74c3c; color: white; }

.provider {
    background: #3498db;
    color: white;
}

.template-description {
    color: #7f8c8d;
    margin-bottom: 15px;
    line-height: 1.5;
}

.template-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.btn-preview {
    background: #95a5a6;
    color: white;
}

.btn-preview:hover {
    background: #7f8c8d;
    color: white;
}

.btn-start {
    background: #3498db;
    color: white;
}

.btn-start:hover {
    background: #2980b9;
    color: white;
}

.btn-resume {
    background: #27ae60;
    color: white;
}

.btn-resume:hover {
    background: #229954;
    color: white;
}

.sessions-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    background: #fafafa;
}

.session-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.session-meta {
    display: flex;
    gap: 10px;
    align-items: center;
}

.session-meta span {
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
}

.status.completed { background: #27ae60; color: white; }
.status.active { background: #f39c12; color: white; }
.status.failed { background: #e74c3c; color: white; }

.date {
    background: #ecf0f1;
    color: #7f8c8d;
}

.score {
    font-weight: bold;
    color: #27ae60;
    font-size: 16px;
}

.no-templates, .no-sessions {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 40px;
}

@media (max-width: 768px) {
    .progress-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .session-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .filter-controls {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const difficultyFilter = document.getElementById('difficulty-filter');
    const providerFilter = document.getElementById('provider-filter');
    const templatesGrid = document.getElementById('templates-grid');
    
    // Filter functionality
    function filterTemplates() {
        const difficultyValue = difficultyFilter.value;
        const providerValue = providerFilter.value;
        const templateCards = templatesGrid.querySelectorAll('.template-card');
        
        templateCards.forEach(card => {
            const cardDifficulty = card.dataset.difficulty;
            const cardProvider = card.dataset.provider;
            
            const showCard = 
                (difficultyValue === '' || cardDifficulty === difficultyValue) &&
                (providerValue === '' || cardProvider === providerValue);
            
            card.style.display = showCard ? 'block' : 'none';
        });
    }
    
    difficultyFilter.addEventListener('change', filterTemplates);
    providerFilter.addEventListener('change', filterTemplates);
    
    // Start lab functionality
    const startButtons = document.querySelectorAll('.btn-start');
    
    startButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const templateId = this.dataset.templateId;
            
            // Get template info for provider
            const templateCard = this.closest('.template-card');
            const provider = templateCard.dataset.provider;
            
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
                    provider: provider
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to workspace
                    window.location.href = '/sixlab-workspace?session=' + data.data.session_id;
                } else {
                    alert('Error starting lab: ' + data.data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to start lab session');
            });
        });
    });
});
</script>

<?php
// Get WordPress footer
get_footer();
?>
