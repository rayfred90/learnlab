/**
 * 6Lab Tool - Frontend Interface JavaScript
 * Handles all interactive elements and behaviors
 */

class SixLabInterface {
    constructor() {
        this.aiAssistantCollapsed = false;
        this.taskPanelCollapsed = false;
        this.fullscreenMode = false;
        this.sessionTimer = null;
        this.sessionStartTime = Date.now();
        
        this.init();
    }
    
    init() {
        this.initEventListeners();
        this.initKeyboardShortcuts();
        this.initTouchGestures();
        this.startSessionTimer();
        this.initVoiceCommands();
        this.loadUserPreferences();
    }
    
    /**
     * Initialize all event listeners
     */
    initEventListeners() {
        // Task Panel Toggle
        const panelToggle = document.getElementById('panel-toggle');
        if (panelToggle) {
            panelToggle.addEventListener('click', () => this.toggleTaskPanel());
        }
        
        // AI Assistant Toggle
        const aiAssistant = document.getElementById('ai-assistant');
        if (aiAssistant) {
            const header = aiAssistant.querySelector('.ai-assistant-header');
            header.addEventListener('click', () => this.toggleAIAssistant());
        }
        
        // AI Input Handling
        const aiInput = document.getElementById('ai-input');
        const aiSend = document.getElementById('ai-send');
        
        if (aiInput && aiSend) {
            aiInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendAIMessage();
                }
            });
            aiSend.addEventListener('click', () => this.sendAIMessage());
        }
        
        // Workspace Controls
        const fullscreenBtn = document.getElementById('fullscreen-btn');
        const zoomInBtn = document.getElementById('zoom-in-btn');
        const zoomOutBtn = document.getElementById('zoom-out-btn');
        
        if (fullscreenBtn) fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        if (zoomInBtn) zoomInBtn.addEventListener('click', () => this.adjustZoom(1.1));
        if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => this.adjustZoom(0.9));
        
        // Control Bar Buttons
        const validateBtn = document.getElementById('validate-btn');
        const resetBtn = document.getElementById('reset-btn');
        const helpBtn = document.getElementById('help-btn');
        const submitBtn = document.getElementById('submit-btn');
        
        if (validateBtn) validateBtn.addEventListener('click', () => this.validateConfiguration());
        if (resetBtn) resetBtn.addEventListener('click', () => this.resetLab());
        if (helpBtn) helpBtn.addEventListener('click', () => this.showHelp());
        if (submitBtn) submitBtn.addEventListener('click', () => this.submitLab());
        
        // Theme Toggle (if implemented)
        this.initThemeToggle();
        
        // Window Resize Handler
        window.addEventListener('resize', () => this.handleResize());
    }
    
    /**
     * Initialize keyboard shortcuts
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+Enter: Validate Configuration
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.validateConfiguration();
            }
            
            // Ctrl+H: Toggle AI Help
            else if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                this.toggleAIAssistant();
            }
            
            // Ctrl+R: Reset Lab
            else if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.resetLab();
            }
            
            // F11: Toggle Fullscreen
            else if (e.key === 'F11') {
                e.preventDefault();
                this.toggleFullscreen();
            }
            
            // Ctrl+/: Show Shortcuts
            else if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                this.showKeyboardShortcuts();
            }
            
            // Escape: Exit fullscreen or close modals
            else if (e.key === 'Escape') {
                if (this.fullscreenMode) {
                    this.toggleFullscreen();
                }
            }
        });
    }
    
    /**
     * Initialize touch gestures for mobile
     */
    initTouchGestures() {
        let touchStartX = 0;
        let touchStartY = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        });
        
        document.addEventListener('touchmove', (e) => {
            if (!touchStartX || !touchStartY) return;
            
            const touchEndX = e.touches[0].clientX;
            const touchEndY = e.touches[0].clientY;
            
            const diffX = touchStartX - touchEndX;
            const diffY = touchStartY - touchEndY;
            
            // Swipe detection
            if (Math.abs(diffX) > Math.abs(diffY)) {
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        // Swipe left - next step
                        this.nextStep();
                    } else {
                        // Swipe right - previous step
                        this.previousStep();
                    }
                }
            }
            
            touchStartX = 0;
            touchStartY = 0;
        });
        
        // Pinch zoom for provider interface
        this.initPinchZoom();
    }
    
    /**
     * Initialize pinch zoom
     */
    initPinchZoom() {
        const workspace = document.querySelector('.provider-iframe-container');
        if (!workspace) return;
        
        let initialDistance = 0;
        let scale = 1;
        
        workspace.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                initialDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
            }
        });
        
        workspace.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();
                const currentDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
                const scaleChange = currentDistance / initialDistance;
                scale = Math.max(0.5, Math.min(3, scale * scaleChange));
                workspace.style.transform = `scale(${scale})`;
                initialDistance = currentDistance;
            }
        });
    }
    
    /**
     * Get distance between two touch points
     */
    getTouchDistance(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    /**
     * Initialize voice commands
     */
    initVoiceCommands() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.log('Speech recognition not supported');
            return;
        }
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        this.recognition.continuous = false;
        this.recognition.interimResults = false;
        this.recognition.lang = 'en-US';
        
        this.recognition.onresult = (event) => {
            const command = event.results[0][0].transcript.toLowerCase();
            this.processVoiceCommand(command);
        };
        
        // Wake phrase detection
        this.recognition.onend = () => {
            // Restart listening for wake phrase
            setTimeout(() => {
                try {
                    this.recognition.start();
                } catch (e) {
                    // Recognition already started
                }
            }, 1000);
        };
        
        // Start listening
        try {
            this.recognition.start();
        } catch (e) {
            console.log('Could not start voice recognition:', e);
        }
    }
    
    /**
     * Process voice commands
     */
    processVoiceCommand(command) {
        console.log('Voice command:', command);
        
        if (command.includes('hey lab assistant')) {
            this.handleWakePhrase(command.replace('hey lab assistant', '').trim());
        }
    }
    
    /**
     * Handle wake phrase and subsequent command
     */
    handleWakePhrase(command) {
        if (command.includes('help with current step')) {
            this.showCurrentStepHelp();
        } else if (command.includes('validate my configuration')) {
            this.validateConfiguration();
        } else if (command.includes('explain this error')) {
            this.explainLastError();
        } else if (command.includes('show me a hint')) {
            this.showCurrentHint();
        }
    }
    
    /**
     * Toggle task panel
     */
    toggleTaskPanel() {
        const panel = document.getElementById('task-panel');
        const toggle = document.getElementById('panel-toggle');
        
        if (panel && toggle) {
            this.taskPanelCollapsed = !this.taskPanelCollapsed;
            
            if (this.taskPanelCollapsed) {
                panel.classList.add('collapsed');
                toggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                panel.classList.remove('collapsed');
                toggle.innerHTML = '<i class="fas fa-chevron-left"></i>';
            }
            
            this.saveUserPreference('taskPanelCollapsed', this.taskPanelCollapsed);
        }
    }
    
    /**
     * Toggle AI assistant
     */
    toggleAIAssistant() {
        const assistant = document.getElementById('ai-assistant');
        const chevron = document.getElementById('ai-chevron');
        
        if (assistant && chevron) {
            this.aiAssistantCollapsed = !this.aiAssistantCollapsed;
            
            if (this.aiAssistantCollapsed) {
                assistant.classList.add('collapsed');
                chevron.className = 'fas fa-chevron-up';
            } else {
                assistant.classList.remove('collapsed');
                chevron.className = 'fas fa-chevron-down';
            }
            
            this.saveUserPreference('aiAssistantCollapsed', this.aiAssistantCollapsed);
        }
    }
    
    /**
     * Send AI message
     */
    async sendAIMessage() {
        const input = document.getElementById('ai-input');
        const messages = document.getElementById('ai-messages');
        
        if (!input || !messages || !input.value.trim()) return;
        
        const userMessage = input.value.trim();
        input.value = '';
        
        // Add user message
        this.addAIMessage(userMessage, 'user');
        
        // Show typing indicator
        const typingId = this.showTypingIndicator();
        
        try {
            // Send to AI provider
            const response = await this.sendToAIProvider(userMessage);
            
            // Remove typing indicator
            this.removeTypingIndicator(typingId);
            
            // Add AI response
            this.addAIMessage(response, 'assistant');
            
        } catch (error) {
            this.removeTypingIndicator(typingId);
            this.addAIMessage('Sorry, I encountered an error. Please try again.', 'assistant');
            console.error('AI Error:', error);
        }
    }
    
    /**
     * Add message to AI chat
     */
    addAIMessage(text, sender) {
        const messages = document.getElementById('ai-messages');
        if (!messages) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${sender}`;
        messageDiv.innerHTML = text;
        
        messages.appendChild(messageDiv);
        messages.scrollTop = messages.scrollHeight;
        
        // Add fade-in animation
        messageDiv.classList.add('fade-in');
    }
    
    /**
     * Show typing indicator
     */
    showTypingIndicator() {
        const messages = document.getElementById('ai-messages');
        if (!messages) return null;
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'ai-message assistant typing-indicator';
        typingDiv.innerHTML = '<div class="loading-spinner"></div> Thinking...';
        typingDiv.id = 'typing-' + Date.now();
        
        messages.appendChild(typingDiv);
        messages.scrollTop = messages.scrollHeight;
        
        return typingDiv.id;
    }
    
    /**
     * Remove typing indicator
     */
    removeTypingIndicator(id) {
        const typing = document.getElementById(id);
        if (typing) {
            typing.remove();
        }
    }
    
    /**
     * Send message to AI provider
     */
    async sendToAIProvider(message) {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sixlab_ai_chat',
                message: message,
                context: this.getCurrentLabContext(),
                nonce: sixlabAjax.nonce
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data.data.response;
        } else {
            throw new Error(data.data.message || 'AI request failed');
        }
    }
    
    /**
     * Get current lab context for AI
     */
    getCurrentLabContext() {
        return {
            currentStep: this.getCurrentStep(),
            labType: 'basic_router_configuration',
            progress: this.getProgress(),
            lastError: this.getLastError()
        };
    }
    
    /**
     * Toggle fullscreen mode
     */
    toggleFullscreen() {
        const workspace = document.querySelector('.sixlab-lab-workspace');
        if (!workspace) return;
        
        this.fullscreenMode = !this.fullscreenMode;
        
        if (this.fullscreenMode) {
            workspace.classList.add('fullscreen-overlay');
            document.body.style.overflow = 'hidden';
        } else {
            workspace.classList.remove('fullscreen-overlay');
            document.body.style.overflow = '';
        }
    }
    
    /**
     * Adjust zoom level
     */
    adjustZoom(factor) {
        const iframe = document.querySelector('.provider-iframe');
        if (!iframe) return;
        
        const currentScale = parseFloat(iframe.style.transform.replace('scale(', '').replace(')', '')) || 1;
        const newScale = Math.max(0.5, Math.min(2, currentScale * factor));
        
        iframe.style.transform = `scale(${newScale})`;
        iframe.style.transformOrigin = 'top left';
    }
    
    /**
     * Validate configuration
     */
    async validateConfiguration() {
        const button = document.getElementById('validate-btn');
        if (!button) return;
        
        // Show loading state
        const originalText = button.innerHTML;
        button.innerHTML = '<div class="loading-spinner"></div> Validating...';
        button.disabled = true;
        
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_validate_step',
                    session_id: this.getSessionId(),
                    step_data: JSON.stringify(this.getCurrentStepData()),
                    nonce: sixlabAjax.nonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.handleValidationSuccess(data.data);
            } else {
                this.handleValidationError(data.data);
            }
            
        } catch (error) {
            this.handleValidationError({ message: 'Validation failed. Please try again.' });
            console.error('Validation error:', error);
        } finally {
            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    /**
     * Handle validation success
     */
    handleValidationSuccess(data) {
        this.showNotification('✅ Configuration validated successfully!', 'success');
        this.updateProgress(data.progress);
        this.markStepComplete(data.step);
        
        if (data.nextStep) {
            this.advanceToNextStep(data.nextStep);
        }
    }
    
    /**
     * Handle validation error
     */
    handleValidationError(data) {
        this.showNotification('❌ ' + (data.message || 'Validation failed'), 'error');
        
        if (data.feedback) {
            this.showDetailedFeedback(data.feedback);
        }
    }
    
    /**
     * Reset lab
     */
    async resetLab() {
        if (!confirm('Are you sure you want to reset the lab? All progress will be lost.')) {
            return;
        }
        
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sixlab_reset_lab',
                    session_id: this.getSessionId(),
                    nonce: sixlabAjax.nonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                this.showNotification('Failed to reset lab: ' + data.data.message, 'error');
            }
            
        } catch (error) {
            this.showNotification('Error resetting lab. Please try again.', 'error');
            console.error('Reset error:', error);
        }
    }
    
    /**
     * Show help
     */
    showHelp() {
        this.toggleAIAssistant();
        
        // Add help request to AI chat
        this.addAIMessage('I need help with the current step', 'user');
        this.addAIMessage(this.getCurrentStepHelp(), 'assistant');
    }
    
    /**
     * Submit lab
     */
    async submitLab() {
        // Implementation for lab submission
        this.showNotification('🎉 Lab completed successfully!', 'success');
    }
    
    /**
     * Start session timer
     */
    startSessionTimer() {
        this.sessionTimer = setInterval(() => {
            const elapsed = Date.now() - this.sessionStartTime;
            const hours = Math.floor(elapsed / 3600000);
            const minutes = Math.floor((elapsed % 3600000) / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            
            const timerDisplay = document.getElementById('session-timer');
            if (timerDisplay) {
                timerDisplay.textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                ${message}
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
            padding: 1rem;
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
                notification.remove();
            }
        }, 5000);
        
        // Close button
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => notification.remove());
    }
    
    /**
     * Handle window resize
     */
    handleResize() {
        // Responsive adjustments
        const width = window.innerWidth;
        
        if (width <= 768) {
            // Mobile adjustments
            this.adjustForMobile();
        } else if (width <= 1024) {
            // Tablet adjustments
            this.adjustForTablet();
        } else {
            // Desktop adjustments
            this.adjustForDesktop();
        }
    }
    
    /**
     * Load user preferences
     */
    loadUserPreferences() {
        const prefs = localStorage.getItem('sixlab-preferences');
        if (prefs) {
            const preferences = JSON.parse(prefs);
            
            if (preferences.taskPanelCollapsed) {
                this.toggleTaskPanel();
            }
            
            if (preferences.aiAssistantCollapsed) {
                this.toggleAIAssistant();
            }
            
            if (preferences.theme) {
                this.setTheme(preferences.theme);
            }
        }
    }
    
    /**
     * Save user preference
     */
    saveUserPreference(key, value) {
        const prefs = JSON.parse(localStorage.getItem('sixlab-preferences') || '{}');
        prefs[key] = value;
        localStorage.setItem('sixlab-preferences', JSON.stringify(prefs));
    }
    
    // Utility methods
    getSessionId() {
        return document.body.dataset.sessionId || 'default';
    }
    
    getCurrentStep() {
        return 2; // Current step from the interface
    }
    
    getProgress() {
        return 65; // Current progress percentage
    }
    
    getCurrentStepData() {
        return {
            step: this.getCurrentStep(),
            commands: [], // Would capture actual commands
            configuration: {} // Would capture current config
        };
    }
    
    getCurrentStepHelp() {
        return 'To enter privileged mode, use the <code>enable</code> command. This will change your prompt from Router> to Router# indicating you have administrative access.';
    }
}

// Global functions for HTML onclick handlers
function toggleHint(id) {
    const hint = document.getElementById(id);
    if (hint) {
        hint.style.display = hint.style.display === 'none' ? 'block' : 'none';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.sixlabInterface = new SixLabInterface();
});

// Make some functions globally available
window.toggleAIAssistant = () => window.sixlabInterface?.toggleAIAssistant();
window.toggleTaskPanel = () => window.sixlabInterface?.toggleTaskPanel();
