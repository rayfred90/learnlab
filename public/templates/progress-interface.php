<?php
/**
 * Progress Interface - Enhanced for WordPress pages and students
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from shortcode
// $atts - shortcode attributes
// $is_learndash - whether on LearnDash lesson

$current_user = wp_get_current_user();
$user_id = $atts['user_id'];

// Get user's lab progress
global $wpdb;
$sessions_table = $wpdb->prefix . 'sixlab_sessions';
$templates_table = $wpdb->prefix . 'sixlab_lab_templates';

// Get user progress data
$user_progress = $wpdb->get_results($wpdb->prepare("
    SELECT s.*, t.name as template_name, t.difficulty_level, t.estimated_duration, t.provider_type
    FROM {$sessions_table} s
    LEFT JOIN {$templates_table} t ON s.template_id = t.id
    WHERE s.user_id = %d
    ORDER BY s.created_at DESC
", $user_id));

// Get available templates
$templates_where = array('is_active = 1');
$templates_params = array();

if (!empty($atts['difficulty'])) {
    $templates_where[] = 'difficulty_level = %s';
    $templates_params[] = $atts['difficulty'];
}

if (!empty($atts['template_filter'])) {
    $templates_where[] = '(name LIKE %s OR tags LIKE %s)';
    $filter_term = '%' . $wpdb->esc_like($atts['template_filter']) . '%';
    $templates_params[] = $filter_term;
    $templates_params[] = $filter_term;
}

$templates_sql = "SELECT * FROM {$templates_table} WHERE " . implode(' AND ', $templates_where) . " ORDER BY is_featured DESC, name ASC";

if (!empty($templates_params)) {
    $templates_sql = $wpdb->prepare($templates_sql, $templates_params);
}

$available_templates = $wpdb->get_results($templates_sql);

// Calculate user stats
$completed_sessions = array_filter($user_progress, function($session) {
    return $session->status === 'completed';
});

$total_time = array_sum(array_map(function($session) {
    return $session->total_time ?? 0;
}, $user_progress));

$average_score = 0;
if (!empty($completed_sessions)) {
    $scores = array_filter(array_map(function($session) {
        return $session->final_score;
    }, $completed_sessions));
    
    if (!empty($scores)) {
        $average_score = array_sum($scores) / count($scores);
    }
}

// Get leaderboard data if requested
$leaderboard_data = array();
if ($atts['show_leaderboard'] === 'true') {
    $leaderboard_data = $wpdb->get_results("
        SELECT u.display_name, 
               COUNT(s.id) as completed_labs,
               AVG(s.final_score) as avg_score,
               SUM(s.total_time) as total_time
        FROM {$wpdb->users} u
        INNER JOIN {$sessions_table} s ON u.ID = s.user_id
        WHERE s.status = 'completed'
        GROUP BY u.ID
        ORDER BY avg_score DESC, completed_labs DESC
        LIMIT 10
    ");
}
?>

<div class="sixlab-progress-interface" data-view="<?php echo esc_attr($atts['view']); ?>">
    <?php if ($atts['view'] !== 'templates-only'): ?>
        <!-- User Progress Section -->
        <?php if ($atts['show_user_progress'] === 'true' && $user_id): ?>
            <div class="sixlab-user-progress">
                <div class="sixlab-progress-header">
                    <h2>
                        <i class="fas fa-chart-line"></i>
                        <?php 
                        if ($user_id === get_current_user_id()) {
                            _e('Your Learning Progress', 'sixlab-tool');
                        } else {
                            $user = get_userdata($user_id);
                            echo sprintf(__('%s\'s Progress', 'sixlab-tool'), esc_html($user->display_name));
                        }
                        ?>
                    </h2>
                    <?php if ($is_learndash): ?>
                        <span class="sixlab-context-badge">
                            <i class="fas fa-graduation-cap"></i>
                            LearnDash Lesson
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="sixlab-stats-grid">
                    <div class="sixlab-stat-card">
                        <div class="sixlab-stat-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="sixlab-stat-content">
                            <div class="sixlab-stat-number"><?php echo count($user_progress); ?></div>
                            <div class="sixlab-stat-label"><?php _e('Labs Started', 'sixlab-tool'); ?></div>
                        </div>
                    </div>
                    
                    <div class="sixlab-stat-card">
                        <div class="sixlab-stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="sixlab-stat-content">
                            <div class="sixlab-stat-number"><?php echo count($completed_sessions); ?></div>
                            <div class="sixlab-stat-label"><?php _e('Labs Completed', 'sixlab-tool'); ?></div>
                        </div>
                    </div>
                    
                    <div class="sixlab-stat-card">
                        <div class="sixlab-stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="sixlab-stat-content">
                            <div class="sixlab-stat-number"><?php echo gmdate('H:i', $total_time); ?></div>
                            <div class="sixlab-stat-label"><?php _e('Total Time', 'sixlab-tool'); ?></div>
                        </div>
                    </div>
                    
                    <div class="sixlab-stat-card">
                        <div class="sixlab-stat-icon score">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="sixlab-stat-content">
                            <div class="sixlab-stat-number"><?php echo round($average_score); ?>%</div>
                            <div class="sixlab-stat-label"><?php _e('Avg Score', 'sixlab-tool'); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($user_progress) && $atts['view'] === 'full'): ?>
                    <div class="sixlab-recent-sessions">
                        <h3><?php _e('Recent Lab Sessions', 'sixlab-tool'); ?></h3>
                        <div class="sixlab-sessions-list">
                            <?php foreach (array_slice($user_progress, 0, 5) as $session): ?>
                                <div class="sixlab-session-item">
                                    <div class="sixlab-session-info">
                                        <div class="sixlab-session-name">
                                            <?php echo esc_html($session->template_name ?? 'Unknown Lab'); ?>
                                        </div>
                                        <div class="sixlab-session-meta">
                                            <span class="sixlab-session-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date_i18n(get_option('date_format'), strtotime($session->created_at)); ?>
                                            </span>
                                            <span class="sixlab-session-duration">
                                                <i class="fas fa-clock"></i>
                                                <?php echo gmdate('H:i:s', $session->total_time ?? 0); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="sixlab-session-status">
                                        <span class="sixlab-status-badge status-<?php echo esc_attr($session->status); ?>">
                                            <?php echo esc_html(ucfirst($session->status ?? 'unknown')); ?>
                                        </span>
                                        <?php if ($session->final_score !== null): ?>
                                            <span class="sixlab-score-badge">
                                                <?php echo esc_html($session->final_score); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($atts['show_leaderboard'] === 'true' && !empty($leaderboard_data)): ?>
            <div class="sixlab-leaderboard">
                <h3>
                    <i class="fas fa-trophy"></i>
                    <?php _e('Leaderboard', 'sixlab-tool'); ?>
                </h3>
                <div class="sixlab-leaderboard-list">
                    <?php foreach ($leaderboard_data as $index => $entry): ?>
                        <div class="sixlab-leaderboard-entry">
                            <div class="sixlab-rank">
                                <?php if ($index < 3): ?>
                                    <i class="fas fa-medal rank-<?php echo $index + 1; ?>"></i>
                                <?php else: ?>
                                    <?php echo $index + 1; ?>
                                <?php endif; ?>
                            </div>
                            <div class="sixlab-user-info">
                                <div class="sixlab-user-name"><?php echo esc_html($entry->display_name); ?></div>
                                <div class="sixlab-user-stats">
                                    <?php echo $entry->completed_labs; ?> labs • <?php echo round($entry->avg_score); ?>% avg
                                </div>
                            </div>
                            <div class="sixlab-score"><?php echo round($entry->avg_score); ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Available Lab Templates -->
    <?php if ($atts['show_templates'] === 'true'): ?>
        <div class="sixlab-available-labs">
            <div class="sixlab-labs-header">
                <h2>
                    <i class="fas fa-flask"></i>
                    <?php _e('Available Lab Templates', 'sixlab-tool'); ?>
                </h2>
                
                <!-- Filters -->
                <div class="sixlab-labs-filters">
                    <div class="sixlab-filter-group">
                        <select class="sixlab-difficulty-filter" data-filter="difficulty">
                            <option value=""><?php _e('All Difficulty Levels', 'sixlab-tool'); ?></option>
                            <option value="beginner"><?php _e('Beginner', 'sixlab-tool'); ?></option>
                            <option value="intermediate"><?php _e('Intermediate', 'sixlab-tool'); ?></option>
                            <option value="advanced"><?php _e('Advanced', 'sixlab-tool'); ?></option>
                        </select>
                    </div>
                    
                    <div class="sixlab-filter-group">
                        <select class="sixlab-provider-filter" data-filter="provider">
                            <option value=""><?php _e('All Providers', 'sixlab-tool'); ?></option>
                            <option value="gns3"><?php _e('GNS3', 'sixlab-tool'); ?></option>
                            <option value="guacamole"><?php _e('Guacamole', 'sixlab-tool'); ?></option>
                            <option value="eveng"><?php _e('EVE-NG', 'sixlab-tool'); ?></option>
                        </select>
                    </div>
                    
                    <div class="sixlab-filter-group">
                        <input type="text" class="sixlab-search-filter" placeholder="<?php _e('Search labs...', 'sixlab-tool'); ?>" data-filter="search">
                    </div>
                </div>
            </div>
            
            <div class="sixlab-templates-grid" id="sixlab-templates-grid">
                <?php foreach ($available_templates as $template): ?>
                    <div class="sixlab-template-card" 
                         data-difficulty="<?php echo esc_attr($template->difficulty_level); ?>"
                         data-provider="<?php echo esc_attr($template->provider_type); ?>"
                         data-template-id="<?php echo esc_attr($template->id); ?>">
                        
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
                            <?php if ($template->is_featured): ?>
                                <div class="sixlab-featured-badge">
                                    <i class="fas fa-star"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sixlab-template-content">
                            <h3 class="sixlab-template-title"><?php echo esc_html($template->name); ?></h3>
                            <p class="sixlab-template-description"><?php echo esc_html(wp_trim_words($template->description, 20)); ?></p>
                            
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
                                <span class="sixlab-usage-count">
                                    <i class="fas fa-users"></i>
                                    <?php echo esc_html($template->usage_count); ?> uses
                                </span>
                            </div>
                        </div>
                        
                        <div class="sixlab-template-actions">
                            <button class="sixlab-btn sixlab-btn-secondary sixlab-preview-btn" 
                                    data-template-id="<?php echo esc_attr($template->id); ?>">
                                <i class="fas fa-eye"></i>
                                <?php _e('Preview', 'sixlab-tool'); ?>
                            </button>
                            <button class="sixlab-btn sixlab-btn-primary sixlab-start-btn" 
                                    data-template-id="<?php echo esc_attr($template->id); ?>"
                                    data-provider="<?php echo esc_attr($template->provider_type); ?>">
                                <i class="fas fa-play"></i>
                                <?php _e('Start Lab', 'sixlab-tool'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($available_templates)): ?>
                <div class="sixlab-no-templates">
                    <div class="sixlab-empty-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h3><?php _e('No Lab Templates Available', 'sixlab-tool'); ?></h3>
                    <p><?php _e('Check back later for new lab templates or contact your instructor.', 'sixlab-tool'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.sixlab-progress-interface {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.sixlab-user-progress {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sixlab-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.sixlab-progress-header h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: #1f2937;
}

.sixlab-context-badge {
    background: #3b82f6;
    color: white;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.sixlab-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.sixlab-stat-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.sixlab-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e2e8f0;
    color: #64748b;
}

.sixlab-stat-icon.completed {
    background: #dcfce7;
    color: #16a34a;
}

.sixlab-stat-icon.score {
    background: #fef3c7;
    color: #d97706;
}

.sixlab-stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

.sixlab-stat-label {
    font-size: 14px;
    color: #6b7280;
}

.sixlab-recent-sessions h3 {
    margin-bottom: 16px;
    color: #1f2937;
}

.sixlab-sessions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.sixlab-session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #e2e8f0;
}

.sixlab-session-name {
    font-weight: 500;
    color: #1f2937;
}

.sixlab-session-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.sixlab-session-status {
    display: flex;
    gap: 8px;
    align-items: center;
}

.sixlab-status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-completed {
    background: #dcfce7;
    color: #16a34a;
}

.status-in_progress {
    background: #fef3c7;
    color: #d97706;
}

.status-failed {
    background: #fee2e2;
    color: #dc2626;
}

.sixlab-score-badge {
    background: #e0f2fe;
    color: #0369a1;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

/* Leaderboard */
.sixlab-leaderboard {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sixlab-leaderboard h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    color: #1f2937;
}

.sixlab-leaderboard-entry {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    background: #f8fafc;
}

.sixlab-rank {
    width: 32px;
    text-align: center;
    font-weight: 600;
}

.rank-1 { color: #fbbf24; }
.rank-2 { color: #9ca3af; }
.rank-3 { color: #cd7c2f; }

.sixlab-user-info {
    flex: 1;
}

.sixlab-user-name {
    font-weight: 500;
    color: #1f2937;
}

.sixlab-user-stats {
    font-size: 12px;
    color: #6b7280;
}

.sixlab-score {
    font-weight: 600;
    color: #3b82f6;
}

/* Lab Templates Grid */
.sixlab-available-labs {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sixlab-labs-header {
    margin-bottom: 24px;
}

.sixlab-labs-header h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px 0;
    color: #1f2937;
}

.sixlab-labs-filters {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.sixlab-filter-group select,
.sixlab-filter-group input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    min-width: 150px;
}

.sixlab-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.sixlab-template-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.sixlab-template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.sixlab-template-header {
    position: relative;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    justify-content: center;
}

.sixlab-template-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.sixlab-template-icon i {
    font-size: 20px;
}

.sixlab-featured-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #fbbf24;
    color: #92400e;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.sixlab-template-content {
    padding: 20px;
}

.sixlab-template-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #1f2937;
}

.sixlab-template-description {
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 16px;
}

.sixlab-template-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.sixlab-difficulty-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.difficulty-beginner {
    background: #dcfce7;
    color: #16a34a;
}

.difficulty-intermediate {
    background: #fef3c7;
    color: #d97706;
}

.difficulty-advanced {
    background: #fee2e2;
    color: #dc2626;
}

.sixlab-duration,
.sixlab-usage-count {
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 4px;
}

.sixlab-template-actions {
    display: flex;
    gap: 8px;
    padding: 20px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.sixlab-btn {
    flex: 1;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.sixlab-btn-primary {
    background: #3b82f6;
    color: white;
}

.sixlab-btn-primary:hover {
    background: #2563eb;
}

.sixlab-btn-secondary {
    background: #6b7280;
    color: white;
}

.sixlab-btn-secondary:hover {
    background: #4b5563;
}

.sixlab-no-templates {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.sixlab-empty-icon i {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .sixlab-progress-interface {
        padding: 16px;
    }
    
    .sixlab-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .sixlab-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .sixlab-labs-filters {
        flex-direction: column;
    }
    
    .sixlab-filter-group select,
    .sixlab-filter-group input {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .sixlab-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .sixlab-session-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        initializeFilters();
        bindEvents();
    });
    
    function initializeFilters() {
        // Filter functionality
        $('.sixlab-difficulty-filter, .sixlab-provider-filter').on('change', function() {
            filterTemplates();
        });
        
        $('.sixlab-search-filter').on('input', debounce(function() {
            filterTemplates();
        }, 300));
    }
    
    function bindEvents() {
        // Preview buttons
        $('.sixlab-preview-btn').on('click', function() {
            const templateId = $(this).data('template-id');
            previewTemplate(templateId);
        });
        
        // Start lab buttons
        $('.sixlab-start-btn').on('click', function() {
            const templateId = $(this).data('template-id');
            const provider = $(this).data('provider');
            startLab(templateId, provider);
        });
    }
    
    function filterTemplates() {
        const difficulty = $('.sixlab-difficulty-filter').val();
        const provider = $('.sixlab-provider-filter').val();
        const search = $('.sixlab-search-filter').val().toLowerCase();
        
        $('.sixlab-template-card').each(function() {
            const $card = $(this);
            let show = true;
            
            // Difficulty filter
            if (difficulty && $card.data('difficulty') !== difficulty) {
                show = false;
            }
            
            // Provider filter
            if (provider && $card.data('provider') !== provider) {
                show = false;
            }
            
            // Search filter
            if (search) {
                const title = $card.find('.sixlab-template-title').text().toLowerCase();
                const description = $card.find('.sixlab-template-description').text().toLowerCase();
                
                if (!title.includes(search) && !description.includes(search)) {
                    show = false;
                }
            }
            
            $card.toggle(show);
        });
    }
    
    function previewTemplate(templateId) {
        // Open preview in modal or new window
        const previewUrl = sixlab_ajax.site_url + '/sixlab-preview?template=' + templateId;
        window.open(previewUrl, 'sixlab_preview', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    }
    
    function startLab(templateId, provider) {
        const $button = $('[data-template-id="' + templateId + '"].sixlab-start-btn');
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
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
                    window.location.href = sixlab_ajax.site_url + '/sixlab-workspace?session=' + response.data.session_id;
                } else {
                    alert('Failed to start lab: ' + response.data);
                    $button.prop('disabled', false).html('<i class="fas fa-play"></i> Start Lab');
                }
            },
            error: function() {
                alert('Failed to start lab. Please try again.');
                $button.prop('disabled', false).html('<i class="fas fa-play"></i> Start Lab');
            }
        });
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
})(jQuery);
</script>
