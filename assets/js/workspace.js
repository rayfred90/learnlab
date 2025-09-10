/**
 * 6Lab Tool Workspace JavaScript
 * 
 * @package SixLab_Tool
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Main workspace object
    window.SixLabWorkspace = {
        
        // Configuration
        config: {
            sessionTimer: null,
            sessionStartTime: null,
            heartbeatInterval: null,
            chatScrollTimer: null,
            reconnectAttempts: 0,
            maxReconnectAttempts: 5,
            reconnectDelay: 5000
        },
        
        // Initialize workspace
        init: function() {
            this.initializeComponents();
            this.bindEvents();
            this.startSession();
            this.initializeTimer();
            this.initializeHeartbeat();
            
            // Initialize AI chat if enabled
            if (sixlab_workspace.ai_enabled && sixlab_workspace.ai_provider) {
                this.initializeAIChat();
            }
            
            // Load progress checklist
            this.loadProgressChecklist();
        },
        
        // Initialize all components
        initializeComponents: function() {
            this.adjustLayout();
            this.initializePanels();
            
            // Set initial connection status
            this.updateConnectionStatus('connecting');
            
            // Initialize modals
            this.initializeModals();
        },
        
        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Window resize
            $(window).on('resize', function() {
                self.adjustLayout();
            });
            
            // Panel toggles
            $('.sixlab-panel-toggle').on('click', function() {
                self.togglePanel($(this).data('panel'));
            });
            
            // Session controls
            $('#sixlab-end-session').on('click', function() {
                self.endSession();
            });
            
            $('#sixlab-save-progress').on('click', function() {
                self.showProgressModal();
            });
            
            $('#sixlab-take-screenshot').on('click', function() {
                self.takeScreenshot();
            });
            
            $('#sixlab-download-config').on('click', function() {
                self.downloadConfiguration();
            });
            
            $('#sixlab-retry-connection').on('click', function() {
                self.retryConnection();
            });
            
            // Progress form
            $('#sixlab-progress-form').on('submit', function(e) {
                e.preventDefault();
                self.saveProgress();
            });
            
            // Modal close
            $('.sixlab-modal-close').on('click', function() {
                $(this).closest('.sixlab-modal').hide();
            });
            
            // Click outside modal to close
            $('.sixlab-modal').on('click', function(e) {
                if ($(e.target).hasClass('sixlab-modal')) {
                    $(this).hide();
                }
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch (e.which) {
                        case 83: // Ctrl+S - Save progress
                            e.preventDefault();
                            self.showProgressModal();
                            break;
                        case 69: // Ctrl+E - End session
                            e.preventDefault();
                            self.endSession();
                            break;
                    }
                }
            });
        },
        
        // Start lab session
        startSession: function() {
            var self = this;
            
            this.showLoading(sixlab_workspace.strings.connecting);
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_start_session',
                    session_id: sixlab_workspace.session_id,
                    provider_type: sixlab_workspace.provider_type,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.connectToLab(response.data.lab_url);
                        self.updateConnectionStatus('connected');
                        self.config.reconnectAttempts = 0;
                    } else {
                        self.showError(response.data.message || 'Failed to start session');
                    }
                },
                error: function() {
                    self.showError('Connection error. Please try again.');
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // Connect to lab environment
        connectToLab: function(labUrl) {
            var iframe = $('#sixlab-lab-iframe');
            var container = $('#sixlab-lab-iframe-container');
            var loading = $('#sixlab-lab-loading');
            
            iframe.attr('src', labUrl);
            
            iframe.on('load', function() {
                loading.hide();
                container.show();
                
                // Try to communicate with iframe
                try {
                    iframe[0].contentWindow.postMessage({
                        type: 'sixlab_init',
                        session_id: sixlab_workspace.session_id
                    }, '*');
                } catch (e) {
                    console.log('Cross-origin communication not available');
                }
            });
            
            iframe.on('error', function() {
                SixLabWorkspace.showError('Failed to load lab environment');
            });
        },
        
        // Update connection status
        updateConnectionStatus: function(status) {
            var statusElement = $('#sixlab-connection-status');
            var statusText = $('.status-text', statusElement);
            
            statusElement.removeClass('connected disconnected connecting').addClass(status);
            
            switch (status) {
                case 'connected':
                    statusText.text(sixlab_workspace.strings.connected);
                    break;
                case 'disconnected':
                    statusText.text(sixlab_workspace.strings.disconnected);
                    break;
                case 'connecting':
                    statusText.text(sixlab_workspace.strings.connecting);
                    break;
            }
        },
        
        // Initialize session timer
        initializeTimer: function() {
            this.config.sessionStartTime = Date.now();
            this.updateTimer();
            
            this.config.sessionTimer = setInterval(function() {
                SixLabWorkspace.updateTimer();
            }, 1000);
        },
        
        // Update timer display
        updateTimer: function() {
            var elapsed = Math.floor((Date.now() - this.config.sessionStartTime) / 1000);
            var hours = Math.floor(elapsed / 3600);
            var minutes = Math.floor((elapsed % 3600) / 60);
            var seconds = elapsed % 60;
            
            var timeString = 
                (hours < 10 ? '0' : '') + hours + ':' +
                (minutes < 10 ? '0' : '') + minutes + ':' +
                (seconds < 10 ? '0' : '') + seconds;
            
            $('#sixlab-session-timer').text(timeString);
        },
        
        // Initialize heartbeat
        initializeHeartbeat: function() {
            this.config.heartbeatInterval = setInterval(function() {
                SixLabWorkspace.sendHeartbeat();
            }, 30000); // Every 30 seconds
        },
        
        // Send heartbeat to server
        sendHeartbeat: function() {
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_heartbeat',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        SixLabWorkspace.updateConnectionStatus('disconnected');
                        if (SixLabWorkspace.config.reconnectAttempts < SixLabWorkspace.config.maxReconnectAttempts) {
                            setTimeout(function() {
                                SixLabWorkspace.retryConnection();
                            }, SixLabWorkspace.config.reconnectDelay);
                        }
                    }
                },
                error: function() {
                    SixLabWorkspace.updateConnectionStatus('disconnected');
                }
            });
        },
        
        // Retry connection
        retryConnection: function() {
            this.config.reconnectAttempts++;
            this.updateConnectionStatus('connecting');
            this.startSession();
        },
        
        // Initialize panels
        initializePanels: function() {
            // Set initial panel states
            $('.sixlab-panel').each(function() {
                var panel = $(this);
                var isCollapsed = localStorage.getItem('sixlab_panel_' + panel.attr('id') + '_collapsed') === 'true';
                
                if (isCollapsed) {
                    SixLabWorkspace.togglePanel(panel.attr('id').replace('sixlab-', '').replace('-panel', ''));
                }
            });
        },
        
        // Toggle panel collapse
        togglePanel: function(panelName) {
            var panel = $('#sixlab-' + panelName + '-panel');
            var content = panel.find('.sixlab-panel-content');
            var toggle = panel.find('.sixlab-panel-toggle');
            var icon = toggle.find('.dashicons');
            
            if (content.is(':visible')) {
                content.slideUp(200);
                icon.removeClass('dashicons-minus').addClass('dashicons-plus');
                localStorage.setItem('sixlab_panel_' + panel.attr('id') + '_collapsed', 'true');
            } else {
                content.slideDown(200);
                icon.removeClass('dashicons-plus').addClass('dashicons-minus');
                localStorage.setItem('sixlab_panel_' + panel.attr('id') + '_collapsed', 'false');
            }
        },
        
        // Adjust layout for responsive design
        adjustLayout: function() {
            var windowWidth = $(window).width();
            var content = $('.sixlab-content');
            
            if (windowWidth <= 968) {
                if (!content.hasClass('mobile-layout')) {
                    content.addClass('mobile-layout');
                }
            } else {
                content.removeClass('mobile-layout');
            }
        },
        
        // Initialize modals
        initializeModals: function() {
            // Close modal on Escape key
            $(document).on('keydown', function(e) {
                if (e.which === 27) { // Escape key
                    $('.sixlab-modal:visible').hide();
                }
            });
        },
        
        // Show progress modal
        showProgressModal: function() {
            $('#sixlab-progress-modal').show();
            $('#progress-notes').focus();
        },
        
        // Save progress
        saveProgress: function() {
            var self = this;
            var notes = $('#progress-notes').val();
            var includeScreenshot = $('#include-screenshot').is(':checked');
            
            this.showLoading('Saving progress...');
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_save_progress',
                    session_id: sixlab_workspace.session_id,
                    notes: notes,
                    include_screenshot: includeScreenshot,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#sixlab-progress-modal').hide();
                        self.showNotification('Progress saved successfully', 'success');
                        $('#progress-notes').val('');
                    } else {
                        self.showNotification(response.data.message || 'Failed to save progress', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error saving progress', 'error');
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // Take screenshot
        takeScreenshot: function() {
            var self = this;
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_take_screenshot',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Screenshot saved', 'success');
                    } else {
                        self.showNotification(response.data.message || 'Failed to take screenshot', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error taking screenshot', 'error');
                }
            });
        },
        
        // Download configuration
        downloadConfiguration: function() {
            var self = this;
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_download_config',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        link.click();
                    } else {
                        self.showNotification(response.data.message || 'Failed to download configuration', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error downloading configuration', 'error');
                }
            });
        },
        
        // End session
        endSession: function() {
            if (!confirm(sixlab_workspace.strings.confirm_end)) {
                return;
            }
            
            var self = this;
            
            this.showLoading('Ending session...');
            
            // Clear timers
            if (this.config.sessionTimer) {
                clearInterval(this.config.sessionTimer);
            }
            if (this.config.heartbeatInterval) {
                clearInterval(this.config.heartbeatInterval);
            }
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_end_session',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to course or dashboard
                        window.location.href = response.data.redirect_url || '/';
                    } else {
                        self.showNotification(response.data.message || 'Failed to end session', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error ending session', 'error');
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // Load progress checklist
        loadProgressChecklist: function() {
            var self = this;
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_get_progress',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success && response.data.checklist) {
                        self.renderProgressChecklist(response.data.checklist);
                    }
                }
            });
        },
        
        // Render progress checklist
        renderProgressChecklist: function(checklist) {
            var container = $('#sixlab-progress-checklist');
            container.empty();
            
            if (!checklist || checklist.length === 0) {
                container.append('<p>No progress items defined for this lab.</p>');
                return;
            }
            
            checklist.forEach(function(item, index) {
                var itemHtml = 
                    '<div class="sixlab-checklist-item' + (item.completed ? ' completed' : '') + '">' +
                        '<input type="checkbox" class="sixlab-checklist-checkbox" ' +
                               'data-item="' + index + '" ' + (item.completed ? 'checked' : '') + '>' +
                        '<span class="sixlab-checklist-text">' + item.text + '</span>' +
                    '</div>';
                
                container.append(itemHtml);
            });
            
            // Bind checkbox events
            container.find('.sixlab-checklist-checkbox').on('change', function() {
                var checkbox = $(this);
                var itemIndex = checkbox.data('item');
                var completed = checkbox.is(':checked');
                
                SixLabWorkspace.updateProgressItem(itemIndex, completed);
                
                var item = checkbox.closest('.sixlab-checklist-item');
                if (completed) {
                    item.addClass('completed');
                } else {
                    item.removeClass('completed');
                }
            });
        },
        
        // Update progress item
        updateProgressItem: function(itemIndex, completed) {
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_update_progress',
                    session_id: sixlab_workspace.session_id,
                    item_index: itemIndex,
                    completed: completed,
                    nonce: sixlab_workspace.nonce
                }
            });
        },
        
        // AI Chat functionality
        initializeAIChat: function() {
            this.bindAIChatEvents();
            this.loadChatHistory();
        },
        
        // Bind AI chat events
        bindAIChatEvents: function() {
            var self = this;
            
            // Chat input
            $('#sixlab-chat-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendAIMessage();
                }
            });
            
            // Auto-resize textarea
            $('#sixlab-chat-input').on('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
            
            // Send button
            $('#sixlab-send-message').on('click', function() {
                self.sendAIMessage();
            });
            
            // Clear chat
            $('#sixlab-clear-chat').on('click', function() {
                self.clearAIChat();
            });
            
            // Minimize/maximize chat
            $('#sixlab-minimize-chat').on('click', function() {
                self.minimizeAIChat();
            });
            
            $('#sixlab-maximize-chat').on('click', function() {
                self.maximizeAIChat();
            });
            
            // Suggestion buttons
            $(document).on('click', '.sixlab-suggestion-btn', function() {
                var message = $(this).data('message');
                $('#sixlab-chat-input').val(message);
                self.sendAIMessage();
            });
        },
        
        // Send AI message
        sendAIMessage: function() {
            var input = $('#sixlab-chat-input');
            var message = input.val().trim();
            
            if (!message) return;
            
            var self = this;
            var messagesContainer = $('#sixlab-chat-messages');
            
            // Clear input and disable send button
            input.val('').trigger('input');
            $('#sixlab-send-message').prop('disabled', true);
            
            // Add user message to chat
            this.addChatMessage(message, 'user');
            
            // Show typing indicator
            var typingIndicator = this.addTypingIndicator();
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_ai_chat',
                    session_id: sixlab_workspace.session_id,
                    message: message,
                    provider: sixlab_workspace.ai_provider,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.addChatMessage(response.data.response, 'ai');
                        
                        // Update usage stats if provided
                        if (response.data.usage) {
                            self.updateAIUsageStats(response.data.usage);
                        }
                    } else {
                        self.addChatMessage('Sorry, I encountered an error: ' + response.data.message, 'ai', true);
                    }
                },
                error: function() {
                    self.addChatMessage('Sorry, I\'m having trouble connecting right now. Please try again.', 'ai', true);
                },
                complete: function() {
                    typingIndicator.remove();
                    $('#sixlab-send-message').prop('disabled', false);
                    input.focus();
                }
            });
        },
        
        // Add message to chat
        addChatMessage: function(message, type, isError) {
            var messagesContainer = $('#sixlab-chat-messages');
            var time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            var avatar = type === 'user' ? 'dashicons-admin-users' : 'dashicons-admin-generic';
            var messageClass = 'sixlab-' + type + '-message';
            
            if (isError) {
                messageClass += ' sixlab-error-message';
            }
            
            var messageHtml = 
                '<div class="sixlab-chat-message ' + messageClass + '">' +
                    '<div class="sixlab-message-avatar">' +
                        '<span class="dashicons ' + avatar + '"></span>' +
                    '</div>' +
                    '<div class="sixlab-message-content">' +
                        '<p>' + this.formatAIMessage(message) + '</p>' +
                    '</div>' +
                    '<div class="sixlab-message-time">' + time + '</div>' +
                '</div>';
            
            messagesContainer.append(messageHtml);
            this.scrollChatToBottom();
            
            // Show unread indicator if chat is minimized
            if ($('#sixlab-ai-chat').hasClass('minimized')) {
                $('#sixlab-unread-indicator').show().text('!');
            }
        },
        
        // Add typing indicator
        addTypingIndicator: function() {
            var messagesContainer = $('#sixlab-chat-messages');
            var typingHtml = 
                '<div class="sixlab-chat-message sixlab-ai-message sixlab-typing-indicator">' +
                    '<div class="sixlab-message-avatar">' +
                        '<span class="dashicons dashicons-admin-generic"></span>' +
                    '</div>' +
                    '<div class="sixlab-message-content">' +
                        '<div class="sixlab-typing-dots">' +
                            '<span></span><span></span><span></span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            
            var indicator = $(typingHtml);
            messagesContainer.append(indicator);
            this.scrollChatToBottom();
            
            return indicator;
        },
        
        // Format AI message (basic markdown support)
        formatAIMessage: function(message) {
            // Basic formatting
            return message
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br>');
        },
        
        // Scroll chat to bottom
        scrollChatToBottom: function() {
            var container = $('#sixlab-chat-messages');
            
            // Clear existing timer
            if (this.config.chatScrollTimer) {
                clearTimeout(this.config.chatScrollTimer);
            }
            
            // Smooth scroll to bottom
            this.config.chatScrollTimer = setTimeout(function() {
                container.animate({
                    scrollTop: container[0].scrollHeight
                }, 200);
            }, 100);
        },
        
        // Clear AI chat
        clearAIChat: function() {
            if (!confirm('Are you sure you want to clear the chat history?')) {
                return;
            }
            
            var messagesContainer = $('#sixlab-chat-messages');
            messagesContainer.empty();
            
            // Add welcome message back
            this.addChatMessage('Hello! I\'m your AI assistant. How can I help you with this lab?', 'ai');
            
            // Clear server-side history
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_clear_ai_chat',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                }
            });
        },
        
        // Minimize AI chat
        minimizeAIChat: function() {
            $('#sixlab-ai-chat').addClass('minimized');
            $('.sixlab-chat-toggle').show();
            localStorage.setItem('sixlab_ai_chat_minimized', 'true');
        },
        
        // Maximize AI chat
        maximizeAIChat: function() {
            $('#sixlab-ai-chat').removeClass('minimized');
            $('.sixlab-chat-toggle').hide();
            $('#sixlab-unread-indicator').hide();
            localStorage.setItem('sixlab_ai_chat_minimized', 'false');
        },
        
        // Load chat history
        loadChatHistory: function() {
            var self = this;
            
            $.ajax({
                url: sixlab_workspace.ajax_url,
                type: 'POST',
                data: {
                    action: 'sixlab_get_ai_chat_history',
                    session_id: sixlab_workspace.session_id,
                    nonce: sixlab_workspace.nonce
                },
                success: function(response) {
                    if (response.success && response.data.messages) {
                        var messagesContainer = $('#sixlab-chat-messages');
                        messagesContainer.empty();
                        
                        response.data.messages.forEach(function(msg) {
                            self.addChatMessage(msg.content, msg.role === 'user' ? 'user' : 'ai');
                        });
                        
                        // Add welcome message if no history
                        if (response.data.messages.length === 0) {
                            self.addChatMessage('Hello! I\'m your AI assistant. How can I help you with this lab?', 'ai');
                        }
                    }
                }
            });
            
            // Restore chat state
            var isMinimized = localStorage.getItem('sixlab_ai_chat_minimized') === 'true';
            if (isMinimized) {
                this.minimizeAIChat();
            }
        },
        
        // Update AI usage stats
        updateAIUsageStats: function(usage) {
            // This could update a usage display in the UI
            console.log('AI Usage:', usage);
        },
        
        // Utility functions
        showLoading: function(message) {
            $('#sixlab-loading-text').text(message || sixlab_workspace.strings.loading);
            $('#sixlab-loading-overlay').show();
        },
        
        hideLoading: function() {
            $('#sixlab-loading-overlay').hide();
        },
        
        showError: function(message) {
            $('#sixlab-error-text').text(message);
            $('#sixlab-lab-loading').hide();
            $('#sixlab-lab-error').show();
            this.updateConnectionStatus('disconnected');
        },
        
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Create notification element
            var notification = $('<div class="sixlab-notification sixlab-notification-' + type + '">' + message + '</div>');
            
            // Add to page
            $('body').append(notification);
            
            // Show with animation
            setTimeout(function() {
                notification.addClass('show');
            }, 100);
            
            // Remove after delay
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };

})(jQuery);

// Additional CSS for notifications (injected via JavaScript)
jQuery(document).ready(function($) {
    var notificationStyles = 
        '<style>' +
        '.sixlab-notification {' +
            'position: fixed;' +
            'top: 20px;' +
            'right: 20px;' +
            'padding: 12px 20px;' +
            'border-radius: 4px;' +
            'color: #fff;' +
            'font-weight: 500;' +
            'z-index: 10002;' +
            'transform: translateX(100%);' +
            'transition: transform 0.3s ease;' +
            'max-width: 300px;' +
            'word-wrap: break-word;' +
        '}' +
        '.sixlab-notification.show {' +
            'transform: translateX(0);' +
        '}' +
        '.sixlab-notification-success {' +
            'background: #00a32a;' +
        '}' +
        '.sixlab-notification-error {' +
            'background: #d63638;' +
        '}' +
        '.sixlab-notification-info {' +
            'background: #007cba;' +
        '}' +
        '.sixlab-typing-dots {' +
            'display: flex;' +
            'gap: 4px;' +
            'padding: 8px 0;' +
        '}' +
        '.sixlab-typing-dots span {' +
            'width: 6px;' +
            'height: 6px;' +
            'border-radius: 50%;' +
            'background: #646970;' +
            'animation: typing 1.4s infinite ease-in-out;' +
        '}' +
        '.sixlab-typing-dots span:nth-child(2) {' +
            'animation-delay: 0.2s;' +
        '}' +
        '.sixlab-typing-dots span:nth-child(3) {' +
            'animation-delay: 0.4s;' +
        '}' +
        '@keyframes typing {' +
            '0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }' +
            '30% { opacity: 1; transform: scale(1); }' +
        '}' +
        '</style>';
    
    $('head').append(notificationStyles);
});
