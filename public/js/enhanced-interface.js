/**
 * Enhanced Lab Interface JavaScript
 * Real terminal connection and provider integration
 */

class SixLabEnhancedInterface {
    constructor() {
        this.config = window.sixlabConfig;
        this.terminal = null;
        this.websocket = null;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 3;
        this.sessionTimer = null;
        this.sessionStartTime = Date.now();
        this.isConnected = false;
        this.currentStep = 1;
        this.totalSteps = 0;
        
        this.init();
    }
    
    init() {
        this.initializeUI();
        this.initializeTimer();
        this.bindEvents();
        this.initializeTerminal();
        this.attemptConnection();
    }
    
    initializeUI() {
        // Initialize tabs
        document.querySelectorAll('.sixlab-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
        
        // Initialize sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        
        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
        
        // Count total steps from objectives
        this.totalSteps = document.querySelectorAll('.sixlab-objective').length;
        
        // Mobile sidebar handling
        if (window.innerWidth <= 768) {
            sidebar?.classList.add('collapsed');
        }
    }
    
    initializeTimer() {
        this.sessionTimer = setInterval(() => {
            const elapsed = Date.now() - this.sessionStartTime;
            const hours = Math.floor(elapsed / (1000 * 60 * 60));
            const minutes = Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((elapsed % (1000 * 60)) / 1000);
            
            const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('session-timer').textContent = timeString;
            document.getElementById('time-spent').textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        }, 1000);
    }
    
    bindEvents() {
        // Header action buttons
        document.getElementById('connect-lab')?.addEventListener('click', () => this.attemptConnection());
        document.getElementById('take-screenshot')?.addEventListener('click', () => this.takeScreenshot());
        document.getElementById('save-progress')?.addEventListener('click', () => this.saveProgress());
        document.getElementById('reset-lab')?.addEventListener('click', () => this.resetLab());
        document.getElementById('end-session')?.addEventListener('click', () => this.endSession());
        
        // Terminal controls
        document.getElementById('clear-terminal')?.addEventListener('click', () => this.clearTerminal());
        document.getElementById('copy-terminal')?.addEventListener('click', () => this.copyTerminalContent());
        document.getElementById('paste-terminal')?.addEventListener('click', () => this.pasteToTerminal());
        document.getElementById('fullscreen-terminal')?.addEventListener('click', () => this.toggleTerminalFullscreen());
        
        // GUI controls
        document.getElementById('refresh-gui')?.addEventListener('click', () => this.refreshGUI());
        document.getElementById('fullscreen-gui')?.addEventListener('click', () => this.toggleGUIFullscreen());
        
        // Validation
        document.getElementById('validate-configuration')?.addEventListener('click', () => this.validateCurrentStep());
        
        // AI Chat
        document.getElementById('send-chat')?.addEventListener('click', () => this.sendChatMessage());
        document.getElementById('chat-input')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendChatMessage();
            }
        });
        
        // Chat suggestions
        document.querySelectorAll('.suggestion-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const message = e.target.dataset.message;
                document.getElementById('chat-input').value = message;
                this.sendChatMessage();
            });
        });
        
        // Error state retry
        document.getElementById('retry-connection')?.addEventListener('click', () => this.attemptConnection());
        document.getElementById('get-help')?.addEventListener('click', () => this.getHelp());
        
        // Window resize handling
        window.addEventListener('resize', () => {
            this.terminal?.fit();
        });
    }
    
    initializeTerminal() {
        if (typeof Terminal === 'undefined') {
            console.error('XTerm.js not loaded');
            return;
        }
        
        this.terminal = new Terminal({
            cursorBlink: true,
            theme: {
                background: '#000000',
                foreground: '#ffffff',
                cursor: '#ffffff',
                selection: '#3b82f6',
                black: '#000000',
                red: '#ef4444',
                green: '#10b981',
                yellow: '#f59e0b',
                blue: '#3b82f6',
                magenta: '#8b5cf6',
                cyan: '#06b6d4',
                white: '#ffffff',
                brightBlack: '#6b7280',
                brightRed: '#f87171',
                brightGreen: '#34d399',
                brightYellow: '#fbbf24',
                brightBlue: '#60a5fa',
                brightMagenta: '#a78bfa',
                brightCyan: '#22d3ee',
                brightWhite: '#f9fafb'
            },
            fontSize: 14,
            fontFamily: '"Fira Code", "Courier New", monospace',
            allowTransparency: true
        });
        
        // Load addons
        if (typeof FitAddon !== 'undefined') {
            const fitAddon = new FitAddon.FitAddon();
            this.terminal.loadAddon(fitAddon);
            this.fitAddon = fitAddon;
        }
        
        if (typeof WebLinksAddon !== 'undefined') {
            this.terminal.loadAddon(new WebLinksAddon.WebLinksAddon());
        }
        
        // Open terminal in container
        const terminalElement = document.getElementById('terminal');
        if (terminalElement) {
            this.terminal.open(terminalElement);
            
            // Fit terminal to container
            setTimeout(() => {
                this.fitAddon?.fit();
            }, 100);
        }
        
        // Handle terminal input
        this.terminal.onData((data) => {
            this.sendTerminalData(data);
        });
        
        // Initial welcome message
        this.terminal.writeln('\\x1b[1;32m6Lab Tool - Enhanced Lab Interface\\x1b[0m');
        this.terminal.writeln('Connecting to lab environment...');
        this.terminal.writeln('');
    }
    
    attemptConnection() {
        this.connectionAttempts++;
        this.updateConnectionStatus('connecting', this.config.strings.connecting);
        this.showLoadingState();
        
        // Reset UI state
        document.getElementById('connection-panel').style.display = 'block';
        document.getElementById('terminal-container').style.display = 'none';
        document.getElementById('gui-container').style.display = 'none';
        document.getElementById('error-state').style.display = 'none';
        
        // Update loading progress
        this.updateLoadingProgress(20, 'Establishing connection...');
        
        // Try WebSocket connection first
        this.connectWebSocket()
            .then(() => {
                this.updateLoadingProgress(60, 'Authenticating...');
                return this.authenticateSession();
            })
            .then(() => {
                this.updateLoadingProgress(80, 'Initializing lab environment...');
                return this.initializeLab();
            })
            .then(() => {
                this.updateLoadingProgress(100, 'Connected successfully!');
                setTimeout(() => {
                    this.onConnectionSuccess();
                }, 1000);
            })
            .catch((error) => {
                console.error('Connection failed:', error);
                this.onConnectionError(error);
            });
    }
    
    connectWebSocket() {
        return new Promise((resolve, reject) => {
            try {
                // Use provider-specific connection logic
                let wsUrl = this.config.websocketUrl;
                
                // Modify URL based on provider type
                switch (this.config.providerType) {
                    case 'gns3':
                        wsUrl += `/gns3/${this.config.sessionId}`;
                        break;
                    case 'guacamole':
                        wsUrl += `/guacamole/${this.config.sessionId}`;
                        break;
                    case 'eveng':
                        wsUrl += `/eveng/${this.config.sessionId}`;
                        break;
                    default:
                        wsUrl += `/generic/${this.config.sessionId}`;
                }
                
                this.websocket = new WebSocket(wsUrl);
                
                this.websocket.onopen = () => {
                    console.log('WebSocket connected');
                    this.isConnected = true;
                    resolve();
                };
                
                this.websocket.onmessage = (event) => {
                    this.handleWebSocketMessage(event);
                };
                
                this.websocket.onclose = () => {
                    console.log('WebSocket disconnected');
                    this.isConnected = false;
                    this.updateConnectionStatus('disconnected', this.config.strings.disconnected);
                    
                    // Attempt reconnection if auto-reconnect is enabled
                    if (document.getElementById('auto-reconnect')?.checked && this.connectionAttempts < this.maxConnectionAttempts) {
                        setTimeout(() => {
                            this.attemptConnection();
                        }, 5000);
                    }
                };
                
                this.websocket.onerror = (error) => {
                    console.error('WebSocket error:', error);
                    reject(error);
                };
                
                // Connection timeout
                setTimeout(() => {
                    if (this.websocket.readyState === WebSocket.CONNECTING) {
                        this.websocket.close();
                        reject(new Error('Connection timeout'));
                    }
                }, 10000);
                
            } catch (error) {
                reject(error);
            }
        });
    }
    
    authenticateSession() {
        return new Promise((resolve, reject) => {
            const authData = {
                type: 'auth',
                sessionId: this.config.sessionId,
                userId: this.config.userId,
                templateId: this.config.templateId,
                provider: this.config.providerType,
                connectionInfo: this.config.connectionInfo
            };
            
            this.websocket.send(JSON.stringify(authData));
            
            // Wait for auth response
            const authTimeout = setTimeout(() => {
                reject(new Error('Authentication timeout'));
            }, 5000);
            
            const originalOnMessage = this.websocket.onmessage;
            this.websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'auth_response') {
                    clearTimeout(authTimeout);
                    this.websocket.onmessage = originalOnMessage;
                    
                    if (data.success) {
                        resolve(data);
                    } else {
                        reject(new Error(data.error || 'Authentication failed'));
                    }
                }
            };
        });
    }
    
    initializeLab() {
        return new Promise((resolve, reject) => {
            // Send lab initialization request
            const initData = {
                type: 'init_lab',
                labConfig: this.config.labConfig,
                providerType: this.config.providerType
            };
            
            this.websocket.send(JSON.stringify(initData));
            
            // Wait for initialization response
            const initTimeout = setTimeout(() => {
                reject(new Error('Lab initialization timeout'));
            }, 15000);
            
            const originalOnMessage = this.websocket.onmessage;
            this.websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'init_response') {
                    clearTimeout(initTimeout);
                    this.websocket.onmessage = originalOnMessage;
                    
                    if (data.success) {
                        this.labInfo = data.labInfo;
                        resolve(data);
                    } else {
                        reject(new Error(data.error || 'Lab initialization failed'));
                    }
                }
            };
        });
    }
    
    onConnectionSuccess() {
        this.updateConnectionStatus('connected', this.config.strings.connected);
        this.hideLoadingState();
        
        // Show appropriate interface based on provider type
        if (this.config.providerType === 'guacamole') {
            this.showGUIInterface();
        } else {
            this.showTerminalInterface();
        }
        
        // Hide connection panel
        document.getElementById('connection-panel').style.display = 'none';
        
        // Send welcome message to terminal
        if (this.terminal) {
            this.terminal.clear();
            this.terminal.writeln('\\x1b[1;32m✓ Connected to lab environment\\x1b[0m');
            this.terminal.writeln('\\x1b[1;36mProvider: ' + this.config.providerType.toUpperCase() + '\\x1b[0m');
            this.terminal.writeln('\\x1b[1;36mSession: ' + this.config.sessionId + '\\x1b[0m');
            this.terminal.writeln('');
            
            if (this.labInfo && this.labInfo.welcomeMessage) {
                this.terminal.writeln(this.labInfo.welcomeMessage);
                this.terminal.writeln('');
            }
        }
        
        // Reset connection attempts
        this.connectionAttempts = 0;
        
        // Show success notification
        this.showNotification('Successfully connected to lab environment!', 'success');
    }
    
    onConnectionError(error) {
        this.updateConnectionStatus('disconnected', this.config.strings.error);
        this.hideLoadingState();
        this.showErrorState(error.message);
        
        if (this.connectionAttempts >= this.maxConnectionAttempts) {
            this.showNotification('Failed to connect after ' + this.maxConnectionAttempts + ' attempts', 'error');
        }
    }
    
    handleWebSocketMessage(event) {
        try {
            const data = JSON.parse(event.data);
            
            switch (data.type) {
                case 'terminal_output':
                    if (this.terminal) {
                        this.terminal.write(data.content);
                    }
                    break;
                    
                case 'gui_update':
                    this.updateGUIFrame(data.url);
                    break;
                    
                case 'validation_result':
                    this.handleValidationResult(data);
                    break;
                    
                case 'step_completed':
                    this.markStepCompleted(data.step);
                    break;
                    
                case 'notification':
                    this.showNotification(data.message, data.level || 'info');
                    break;
                    
                case 'lab_reset':
                    this.handleLabReset();
                    break;
                    
                case 'session_ended':
                    this.handleSessionEnded();
                    break;
                    
                default:
                    console.log('Unknown message type:', data.type);
            }
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }
    
    sendTerminalData(data) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'terminal_input',
                data: data
            }));
        }
    }
    
    showTerminalInterface() {
        document.getElementById('terminal-container').style.display = 'flex';
        document.getElementById('gui-container').style.display = 'none';
        
        // Fit terminal to new size
        setTimeout(() => {
            this.fitAddon?.fit();
        }, 100);
    }
    
    showGUIInterface() {
        document.getElementById('gui-container').style.display = 'flex';
        document.getElementById('terminal-container').style.display = 'none';
        
        // Load GUI URL if available
        if (this.labInfo && this.labInfo.guiUrl) {
            this.updateGUIFrame(this.labInfo.guiUrl);
        }
    }
    
    updateGUIFrame(url) {
        const iframe = document.getElementById('gui-frame');
        if (iframe) {
            iframe.src = url;
        }
    }
    
    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.sixlab-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.sixlab-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${tabName}-tab`).classList.add('active');
    }
    
    updateConnectionStatus(status, text) {
        const indicator = document.getElementById('status-indicator');
        const statusText = document.getElementById('status-text');
        
        indicator.className = `status-indicator ${status}`;
        statusText.textContent = text;
    }
    
    showLoadingState() {
        document.getElementById('loading-state').style.display = 'flex';
    }
    
    hideLoadingState() {
        document.getElementById('loading-state').style.display = 'none';
    }
    
    showErrorState(message) {
        document.getElementById('error-message').textContent = message;
        document.getElementById('error-state').style.display = 'flex';
    }
    
    updateLoadingProgress(percentage, text) {
        document.getElementById('loading-progress').style.width = percentage + '%';
        document.getElementById('loading-text').textContent = text;
    }
    
    validateCurrentStep() {
        const validateBtn = document.getElementById('validate-configuration');
        validateBtn.disabled = true;
        validateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validating...';
        
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'validate_step',
                step: this.currentStep,
                sessionId: this.config.sessionId
            }));
        } else {
            // Fallback to AJAX
            this.validateStepAjax();
        }
    }
    
    validateStepAjax() {
        fetch(this.config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sixlab_validate_step',
                session_id: this.config.sessionId,
                step: this.currentStep,
                nonce: this.config.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            this.handleValidationResult(data);
        })
        .catch(error => {
            console.error('Validation error:', error);
            this.showValidationResult(false, 'Validation failed due to connection error');
        })
        .finally(() => {
            const validateBtn = document.getElementById('validate-configuration');
            validateBtn.disabled = false;
            validateBtn.innerHTML = '<i class="fas fa-play"></i> Validate Current Step';
        });
    }
    
    handleValidationResult(data) {
        const success = data.success || data.data?.success;
        const message = data.message || data.data?.message || (success ? 'Validation passed!' : 'Validation failed');
        
        this.showValidationResult(success, message);
        
        if (success) {
            this.markStepCompleted(this.currentStep);
            this.currentStep++;
            this.updateProgress();
        }
        
        const validateBtn = document.getElementById('validate-configuration');
        validateBtn.disabled = false;
        validateBtn.innerHTML = '<i class="fas fa-play"></i> Validate Current Step';
    }
    
    showValidationResult(success, message) {
        const resultsDiv = document.getElementById('validation-results');
        resultsDiv.className = `validation-results ${success ? 'success' : 'error'}`;
        resultsDiv.textContent = message;
        resultsDiv.style.display = 'block';
        
        // Hide after 5 seconds
        setTimeout(() => {
            resultsDiv.style.display = 'none';
        }, 5000);
    }
    
    markStepCompleted(step) {
        const objective = document.getElementById(`objective-${step}`);
        if (objective) {
            objective.className = 'objective-status completed';
            objective.innerHTML = '<i class="fas fa-check"></i>';
        }
    }
    
    updateProgress() {
        const completedTasks = this.currentStep - 1;
        const percentage = Math.round((completedTasks / this.totalSteps) * 100);
        
        document.getElementById('completed-tasks').textContent = completedTasks;
        document.getElementById('completion-percentage').textContent = percentage + '%';
    }
    
    sendChatMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        
        if (!message) return;
        
        // Add user message to chat
        this.addChatMessage(message, 'user');
        input.value = '';
        
        // Send to AI via WebSocket or AJAX
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'ai_chat',
                message: message,
                context: {
                    currentStep: this.currentStep,
                    sessionId: this.config.sessionId,
                    providerType: this.config.providerType
                }
            }));
        } else {
            this.sendChatAjax(message);
        }
    }
    
    sendChatAjax(message) {
        fetch(this.config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sixlab_ai_chat',
                message: message,
                session_id: this.config.sessionId,
                context: JSON.stringify({
                    currentStep: this.currentStep,
                    providerType: this.config.providerType
                }),
                nonce: this.config.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.addChatMessage(data.data.response, 'assistant');
            } else {
                this.addChatMessage('Sorry, I encountered an error. Please try again.', 'assistant');
            }
        })
        .catch(error => {
            console.error('Chat error:', error);
            this.addChatMessage('Sorry, I cannot respond right now. Please check your connection.', 'assistant');
        });
    }
    
    addChatMessage(message, sender) {
        const messagesContainer = document.getElementById('chat-messages');
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${sender}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = sender === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
        
        const content = document.createElement('div');
        content.className = 'message-content';
        content.innerHTML = `<p>${this.escapeHtml(message)}</p>`;
        
        messageElement.appendChild(avatar);
        messageElement.appendChild(content);
        messagesContainer.appendChild(messageElement);
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    takeScreenshot() {
        // Implementation depends on provider
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'take_screenshot'
            }));
        }
        
        this.showNotification(this.config.strings.screenshotTaken, 'success');
    }
    
    saveProgress() {
        fetch(this.config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sixlab_save_progress',
                session_id: this.config.sessionId,
                current_step: this.currentStep,
                progress_data: JSON.stringify({
                    completedSteps: this.currentStep - 1,
                    totalSteps: this.totalSteps,
                    timeSpent: Date.now() - this.sessionStartTime
                }),
                nonce: this.config.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(this.config.strings.progressSaved, 'success');
            } else {
                this.showNotification('Failed to save progress', 'error');
            }
        })
        .catch(error => {
            console.error('Save progress error:', error);
            this.showNotification('Failed to save progress', 'error');
        });
    }
    
    resetLab() {
        if (!confirm('Are you sure you want to reset the lab? All progress will be lost.')) {
            return;
        }
        
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'reset_lab'
            }));
        }
    }
    
    endSession() {
        if (!confirm(this.config.strings.confirmEnd)) {
            return;
        }
        
        // Clean up
        if (this.websocket) {
            this.websocket.close();
        }
        
        if (this.sessionTimer) {
            clearInterval(this.sessionTimer);
        }
        
        // Redirect or show completion screen
        window.location.href = this.config.baseUrl || '/';
    }
    
    clearTerminal() {
        this.terminal?.clear();
    }
    
    copyTerminalContent() {
        if (this.terminal) {
            const content = this.terminal.getSelection();
            navigator.clipboard.writeText(content).then(() => {
                this.showNotification('Terminal content copied', 'success');
            });
        }
    }
    
    pasteToTerminal() {
        navigator.clipboard.readText().then(text => {
            if (this.terminal) {
                this.terminal.paste(text);
            }
        });
    }
    
    toggleTerminalFullscreen() {
        const container = document.getElementById('terminal-container');
        container.classList.toggle('terminal-fullscreen');
        
        setTimeout(() => {
            this.fitAddon?.fit();
        }, 100);
    }
    
    refreshGUI() {
        const iframe = document.getElementById('gui-frame');
        if (iframe && iframe.src) {
            iframe.src = iframe.src;
        }
    }
    
    toggleGUIFullscreen() {
        const container = document.getElementById('gui-container');
        container.classList.toggle('gui-fullscreen');
    }
    
    getHelp() {
        this.switchTab('assistant');
        const input = document.getElementById('chat-input');
        input.value = 'I need help with the current lab step';
        this.sendChatMessage();
    }
    
    handleLabReset() {
        this.currentStep = 1;
        this.updateProgress();
        
        // Reset all objective markers
        document.querySelectorAll('.objective-status').forEach(status => {
            status.className = 'objective-status pending';
            status.innerHTML = '<i class="fas fa-circle"></i>';
        });
        
        this.showNotification('Lab has been reset', 'info');
    }
    
    handleSessionEnded() {
        this.showNotification(this.config.strings.sessionEnded, 'info');
        setTimeout(() => {
            window.location.href = this.config.baseUrl || '/';
        }, 3000);
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `sixlab-notification ${type}`;
        notification.textContent = message;
        
        // Add to document
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 5000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.sixlabInterface = new SixLabEnhancedInterface();
});

// Add notification styles
const notificationStyles = `
.sixlab-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 300px;
}

.sixlab-notification.show {
    transform: translateX(0);
}

.sixlab-notification.success {
    background: #10b981;
}

.sixlab-notification.error {
    background: #ef4444;
}

.sixlab-notification.warning {
    background: #f59e0b;
}

.sixlab-notification.info {
    background: #3b82f6;
}
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);
