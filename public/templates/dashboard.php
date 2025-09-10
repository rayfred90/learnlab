<!DOCTYPE html>
<html lang="en" data-theme="dark_professional">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>6Lab Tool - Network Engineering Lab Dashboard</title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>css/frontend-styles.css">
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>css/dashboard-styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sixlab-dashboard">
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="logo-text">
                        <h2>6Lab Tool</h2>
                        <span>Network Engineering Lab</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-title">TRAINING PLATFORM</span>
                    <ul class="nav-menu">
                        <li class="nav-item active">
                            <a href="#" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Lab Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fas fa-play"></i>
                                <span>Active Lab</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fas fa-chart-line"></i>
                                <span>Progress</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar">NE</div>
                    <div class="user-info">
                        <span class="user-name">Network Engineer</span>
                        <span class="user-level">Level 2 Certification</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-content">
                    <div class="page-title">
                        <h1>Network Engineering Lab</h1>
                        <p>Master networking concepts through hands-on practice</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-primary">
                            <i class="fas fa-play"></i>
                            Start Lab Session
                        </button>
                    </div>
                </div>
            </header>

            <!-- Statistics Cards -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card available-labs">
                        <div class="stat-icon">
                            <i class="fas fa-books"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">4</div>
                            <div class="stat-label">Available Labs</div>
                        </div>
                    </div>
                    
                    <div class="stat-card completed">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">1</div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    
                    <div class="stat-card average-score">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">89</div>
                            <div class="stat-label">Average Score</div>
                        </div>
                    </div>
                    
                    <div class="stat-card study-hours">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">24</div>
                            <div class="stat-label">Study Hours</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main Content Grid -->
            <section class="content-grid">
                <!-- Available Labs -->
                <div class="content-section available-labs-section">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-flask"></i>
                            Available Labs
                        </h2>
                    </div>
                    
                    <div class="labs-grid">
                        <div class="lab-card">
                            <div class="lab-header">
                                <h3>Basic Router Configuration</h3>
                                <span class="lab-difficulty beginner">Beginner</span>
                            </div>
                            <p class="lab-description">Learn fundamental router configuration commands and basic routing concepts.</p>
                            <div class="lab-meta">
                                <div class="lab-duration">
                                    <i class="fas fa-clock"></i>
                                    <span>60 min</span>
                                </div>
                                <div class="lab-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.8 rating</span>
                                </div>
                            </div>
                            <button class="lab-start-btn">
                                <i class="fas fa-play"></i>
                                Start
                            </button>
                        </div>
                        
                        <div class="lab-card">
                            <div class="lab-header">
                                <h3>VLAN Configuration</h3>
                                <span class="lab-difficulty intermediate">Intermediate</span>
                            </div>
                            <p class="lab-description">Configure VLANs, trunk ports, and inter-VLAN routing on switches.</p>
                            <div class="lab-meta">
                                <div class="lab-duration">
                                    <i class="fas fa-clock"></i>
                                    <span>90 min</span>
                                </div>
                                <div class="lab-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.6 rating</span>
                                </div>
                            </div>
                            <button class="lab-start-btn">
                                <i class="fas fa-play"></i>
                                Start
                            </button>
                        </div>
                        
                        <div class="lab-card">
                            <div class="lab-header">
                                <h3>OSPF Dynamic Routing</h3>
                                <span class="lab-difficulty advanced">Advanced</span>
                            </div>
                            <p class="lab-description">Implement OSPF routing protocol across multiple routers.</p>
                            <div class="lab-meta">
                                <div class="lab-duration">
                                    <i class="fas fa-clock"></i>
                                    <span>120 min</span>
                                </div>
                                <div class="lab-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.7 rating</span>
                                </div>
                            </div>
                            <button class="lab-start-btn">
                                <i class="fas fa-play"></i>
                                Start
                            </button>
                        </div>
                        
                        <div class="lab-card">
                            <div class="lab-header">
                                <h3>Basic Network Security</h3>
                                <span class="lab-difficulty intermediate">Intermediate</span>
                            </div>
                            <p class="lab-description">Configure access control lists (ACLs) and basic security features.</p>
                            <div class="lab-meta">
                                <div class="lab-duration">
                                    <i class="fas fa-clock"></i>
                                    <span>75 min</span>
                                </div>
                                <div class="lab-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.5 rating</span>
                                </div>
                            </div>
                            <button class="lab-start-btn">
                                <i class="fas fa-play"></i>
                                Start
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Sessions -->
                <div class="content-section recent-sessions">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-history"></i>
                            Recent Sessions
                        </h2>
                        <a href="#" class="view-all-link">View All Sessions</a>
                    </div>
                    
                    <div class="sessions-list">
                        <div class="session-item">
                            <div class="session-info">
                                <h4>Basic Router Configuration</h4>
                                <div class="session-meta">
                                    <span class="session-date">
                                        <i class="fas fa-calendar"></i>
                                        15/01/2024
                                    </span>
                                    <span class="session-duration">
                                        <i class="fas fa-clock"></i>
                                        45 minutes
                                    </span>
                                    <span class="session-status completed">
                                        <i class="fas fa-check-circle"></i>
                                        completed
                                    </span>
                                </div>
                            </div>
                            <div class="session-score">
                                <span class="score-value">85</span>
                                <span class="score-label">Score</span>
                            </div>
                            <div class="session-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <span class="progress-text">5/5 steps completed</span>
                            </div>
                        </div>
                        
                        <div class="session-item">
                            <div class="session-info">
                                <h4>VLAN Configuration</h4>
                                <div class="session-meta">
                                    <span class="session-date">
                                        <i class="fas fa-calendar"></i>
                                        14/01/2024
                                    </span>
                                    <span class="session-duration">
                                        <i class="fas fa-clock"></i>
                                        75 minutes
                                    </span>
                                    <span class="session-status completed">
                                        <i class="fas fa-check-circle"></i>
                                        completed
                                    </span>
                                </div>
                            </div>
                            <div class="session-score">
                                <span class="score-value">92</span>
                                <span class="score-label">Score</span>
                            </div>
                            <div class="session-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <span class="progress-text">5/5 steps completed</span>
                            </div>
                        </div>
                        
                        <div class="session-item">
                            <div class="session-info">
                                <h4>OSPF Dynamic Routing</h4>
                                <div class="session-meta">
                                    <span class="session-date">
                                        <i class="fas fa-calendar"></i>
                                        10/01/2024
                                    </span>
                                    <span class="session-duration">
                                        <i class="fas fa-clock"></i>
                                        105 minutes
                                    </span>
                                    <span class="session-status in-progress">
                                        <i class="fas fa-spinner"></i>
                                        in progress
                                    </span>
                                </div>
                            </div>
                            <div class="session-score">
                                <span class="score-value">78</span>
                                <span class="score-label">Score</span>
                            </div>
                            <div class="session-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 70%"></div>
                                </div>
                                <span class="progress-text">7/10 steps completed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="<?php echo plugin_dir_url(__FILE__); ?>js/dashboard.js"></script>
</body>
</html>
