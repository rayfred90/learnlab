/**
 * 6Lab Tool - Dashboard JavaScript
 * Handles dashboard interactions and animations
 */

class SixLabDashboard {
    constructor() {
        this.sidebarOpen = false;
        this.init();
    }
    
    init() {
        this.initMobileMenu();
        this.initLabCards();
        this.initAnimations();
        this.loadDashboardData();
    }
    
    /**
     * Initialize mobile menu toggle
     */
    initMobileMenu() {
        // Create mobile menu toggle if not exists
        if (!document.querySelector('.mobile-menu-toggle')) {
            const toggle = document.createElement('button');
            toggle.className = 'mobile-menu-toggle';
            toggle.innerHTML = '<i class="fas fa-bars"></i>';
            toggle.addEventListener('click', () => this.toggleSidebar());
            document.body.appendChild(toggle);
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.dashboard-sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (this.sidebarOpen && sidebar && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                this.closeSidebar();
            }
        });
    }
    
    /**
     * Toggle sidebar for mobile
     */
    toggleSidebar() {
        const sidebar = document.querySelector('.dashboard-sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (sidebar && toggle) {
            this.sidebarOpen = !this.sidebarOpen;
            
            if (this.sidebarOpen) {
                sidebar.classList.add('open');
                toggle.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                sidebar.classList.remove('open');
                toggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }
    
    /**
     * Close sidebar
     */
    closeSidebar() {
        const sidebar = document.querySelector('.dashboard-sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (sidebar && toggle) {
            this.sidebarOpen = false;
            sidebar.classList.remove('open');
            toggle.innerHTML = '<i class="fas fa-bars"></i>';
        }
    }
    
    /**
     * Initialize lab card interactions
     */
    initLabCards() {
        const labCards = document.querySelectorAll('.lab-card');
        
        labCards.forEach(card => {
            const startBtn = card.querySelector('.lab-start-btn');
            
            if (startBtn) {
                startBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const labTitle = card.querySelector('h3').textContent;
                    this.startLab(labTitle);
                });
            }
        });
        
        // Add hover effects
        labCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }
    
    /**
     * Start a lab session
     */
    async startLab(labTitle) {
        try {
            // Show loading state
            this.showNotification('Starting lab session...', 'info');
            
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_start_lab',
                    lab_title: labTitle,
                    nonce: sixlabAjax?.nonce || ''
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Redirect to lab interface
                window.location.href = data.data.lab_url;
            } else {
                this.showNotification('Failed to start lab: ' + data.data.message, 'error');
            }
            
        } catch (error) {
            this.showNotification('Error starting lab. Please try again.', 'error');
            console.error('Start lab error:', error);
        }
    }
    
    /**
     * Initialize entrance animations
     */
    initAnimations() {
        // Animate stats cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });
        
        // Animate lab cards
        const labCards = document.querySelectorAll('.lab-card');
        labCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 200 + (100 * index));
        });
        
        // Animate session items
        const sessionItems = document.querySelectorAll('.session-item');
        sessionItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.6s ease-out';
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 400 + (100 * index));
        });
    }
    
    /**
     * Load dashboard data
     */
    async loadDashboardData() {
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_get_dashboard_data',
                    nonce: sixlabAjax?.nonce || ''
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardStats(data.data.stats);
                this.updateRecentSessions(data.data.sessions);
            }
            
        } catch (error) {
            console.error('Dashboard data error:', error);
        }
    }
    
    /**
     * Update dashboard statistics
     */
    updateDashboardStats(stats) {
        const statCards = {
            'available-labs': stats.available_labs || 4,
            'completed': stats.completed || 1,
            'average-score': stats.average_score || 89,
            'study-hours': stats.study_hours || 24
        };
        
        Object.entries(statCards).forEach(([key, value]) => {
            const card = document.querySelector(`.stat-card.${key} .stat-value`);
            if (card) {
                this.animateCounter(card, parseInt(value));
            }
        });
    }
    
    /**
     * Animate counter numbers
     */
    animateCounter(element, targetValue) {
        const duration = 2000;
        const startValue = 0;
        const increment = targetValue / (duration / 16);
        let currentValue = startValue;
        
        const timer = setInterval(() => {
            currentValue += increment;
            
            if (currentValue >= targetValue) {
                element.textContent = targetValue;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(currentValue);
            }
        }, 16);
    }
    
    /**
     * Update recent sessions
     */
    updateRecentSessions(sessions) {
        const sessionsList = document.querySelector('.sessions-list');
        if (!sessionsList || !sessions.length) return;
        
        sessionsList.innerHTML = sessions.map(session => `
            <div class="session-item">
                <div class="session-info">
                    <h4>${session.title}</h4>
                    <div class="session-meta">
                        <span class="session-date">
                            <i class="fas fa-calendar"></i>
                            ${session.date}
                        </span>
                        <span class="session-duration">
                            <i class="fas fa-clock"></i>
                            ${session.duration}
                        </span>
                        <span class="session-status ${session.status}">
                            <i class="fas fa-${session.status === 'completed' ? 'check-circle' : 'spinner'}"></i>
                            ${session.status}
                        </span>
                    </div>
                </div>
                <div class="session-score">
                    <span class="score-value">${session.score}</span>
                    <span class="score-label">Score</span>
                </div>
                <div class="session-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${session.progress}%"></div>
                    </div>
                    <span class="progress-text">${session.steps_completed}/${session.total_steps} steps completed</span>
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.dashboard-notification');
        existingNotifications.forEach(n => n.remove());
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `dashboard-notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--surface);
            color: var(--text-primary);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--${type === 'success' ? 'success' : type === 'error' ? 'error' : 'accent'});
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
        
        // Close button
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    /**
     * Handle responsive behavior
     */
    handleResize() {
        if (window.innerWidth > 768 && this.sidebarOpen) {
            this.closeSidebar();
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.sixlabDashboard = new SixLabDashboard();
    
    // Handle window resize
    window.addEventListener('resize', () => {
        window.sixlabDashboard.handleResize();
    });
});

// Add CSS animations for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-content i {
        font-size: 1.1rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0;
        margin-left: auto;
        transition: color var(--transition-fast);
    }
    
    .notification-close:hover {
        color: var(--text-primary);
    }
`;
document.head.appendChild(notificationStyles);
