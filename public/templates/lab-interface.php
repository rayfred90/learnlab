<!DOCTYPE html>
<html lang="en" data-theme="dark_professional">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>6Lab Tool - Network Engineering Lab</title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>css/frontend-styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sixlab-lab-interface">
        <!-- Lab Header -->
        <header class="sixlab-lab-header">
            <div class="lab-title">
                <div class="lab-title-icon">
                    <i class="fas fa-network-wired"></i>
                </div>
                <span id="current-lab-title">Basic Router Configuration</span>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 65%"></div>
                </div>
                <div class="progress-text" style="margin-top: 8px; font-size: 0.9rem; color: var(--text-secondary);">
                    Step 3 of 5 completed
                </div>
            </div>
            
            <div class="header-stats">
                <div class="timer-display">
                    <i class="fas fa-clock" style="margin-right: 8px;"></i>
                    <span id="session-timer">00:24:31</span>
                </div>
                <div class="user-avatar" title="Network Engineer">
                    NE
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="sixlab-main-content">
            <!-- Task Panel -->
            <aside class="sixlab-task-panel" id="task-panel">
                <div class="task-panel-header">
                    <button class="task-panel-toggle" id="panel-toggle" title="Toggle Panel">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <div class="current-task">
                        <h3>Current Task</h3>
                        <p>Connect to router console and enter privileged mode with <code>enable</code></p>
                    </div>
                </div>
                
                <div class="task-instructions">
                    <h4>
                        <i class="fas fa-list-check"></i>
                        Progress Steps
                    </h4>
                    
                    <div class="instruction-step completed">
                        <div class="step-number completed">1</div>
                        <div class="step-content">
                            <strong>Connect to Router Console</strong>
                            <p>Access the router console via Telnet or SSH</p>
                        </div>
                    </div>
                    
                    <div class="instruction-step current">
                        <div class="step-number current">2</div>
                        <div class="step-content">
                            <strong>Enter Privileged Mode</strong>
                            <p>Use the <code>enable</code> command to access privileged EXEC mode</p>
                            <div class="hint-section" style="margin-top: 12px;">
                                <button class="hint-toggle" onclick="toggleHint('hint-1')">
                                    💡 Show Hint
                                </button>
                                <div id="hint-1" style="display: none; margin-top: 8px;">
                                    <small>Type <code>enable</code> and press Enter. You may be prompted for a password.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <strong>Enter Configuration Mode</strong>
                            <p>Use <code>configure terminal</code> to enter global configuration mode</p>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <strong>Configure Interface</strong>
                            <p>Configure GigabitEthernet0/0 with IP address 192.168.1.1/24</p>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <strong>Enable Interface</strong>
                            <p>Bring the interface up with <code>no shutdown</code></p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Lab Workspace -->
            <section class="sixlab-lab-workspace">
                <div class="workspace-header">
                    <h3 style="color: var(--text-primary); font-size: 1rem;">
                        <i class="fas fa-terminal" style="margin-right: 8px; color: var(--accent);"></i>
                        Router Console
                    </h3>
                    <div class="workspace-controls">
                        <button class="workspace-control-btn" id="fullscreen-btn" title="Fullscreen">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button class="workspace-control-btn" id="zoom-in-btn" title="Zoom In">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button class="workspace-control-btn" id="zoom-out-btn" title="Zoom Out">
                            <i class="fas fa-search-minus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="provider-iframe-container">
                    <!-- This would be replaced with the actual provider interface -->
                    <div style="background: #0d1117; color: #58a6ff; font-family: var(--font-monospace); padding: 20px; height: 100%; overflow-y: auto;">
                        <div style="margin-bottom: 16px;">
                            <span style="color: #7c3aed;">Router></span> <span style="color: #10b981;">enable</span>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <span style="color: #7c3aed;">Router#</span> <span class="blinking-cursor">_</span>
                        </div>
                        <div style="margin-top: 40px; color: #6b7280; font-size: 0.9rem;">
                            <p>✅ Successfully entered privileged EXEC mode</p>
                            <p style="margin-top: 8px;">💡 <strong>Hint:</strong> Privileged mode starts with Router#, user mode shows Router></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- AI Assistant -->
        <div class="sixlab-ai-assistant" id="ai-assistant">
            <div class="ai-assistant-header" onclick="toggleAIAssistant()">
                <div class="ai-assistant-title">
                    <i class="fas fa-robot" style="margin-right: 8px;"></i>
                    AI Assistant
                </div>
                <button class="ai-toggle-btn">
                    <i class="fas fa-chevron-down" id="ai-chevron"></i>
                </button>
            </div>
            
            <div class="ai-chat-interface">
                <div class="ai-messages" id="ai-messages">
                    <div class="ai-message assistant">
                        👋 Hi! I'm here to help with your lab. Need help with the current step?
                    </div>
                    <div class="ai-message user">
                        How do I enter privileged mode?
                    </div>
                    <div class="ai-message assistant">
                        Great question! To enter privileged mode on a Cisco router:
                        <br><br>
                        1. Type <code>enable</code> and press Enter<br>
                        2. You'll see the prompt change from Router> to Router#<br>
                        3. If prompted, enter the enable password
                        <br><br>
                        The # symbol indicates you're in privileged EXEC mode! 🎯
                    </div>
                </div>
                
                <div class="ai-input-area">
                    <input type="text" class="ai-input" id="ai-input" placeholder="Ask me anything about networking...">
                    <button class="ai-send-btn" id="ai-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Control Bar -->
        <footer class="sixlab-control-bar">
            <button class="control-button validate" id="validate-btn">
                <i class="fas fa-check-circle"></i>
                Validate Configuration
            </button>
            <button class="control-button reset" id="reset-btn">
                <i class="fas fa-undo"></i>
                Reset Lab
            </button>
            <button class="control-button" id="help-btn">
                <i class="fas fa-question-circle"></i>
                Get Help
            </button>
            <button class="control-button submit" id="submit-btn" disabled>
                <i class="fas fa-trophy"></i>
                Complete Lab
            </button>
        </footer>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo plugin_dir_url(__FILE__); ?>js/frontend-interface.js"></script>
    
    <style>
        .blinking-cursor {
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        code {
            background: rgba(88, 166, 255, 0.1);
            color: #58a6ff;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: var(--font-monospace);
            font-size: 0.85em;
        }
    </style>
</body>
</html>
