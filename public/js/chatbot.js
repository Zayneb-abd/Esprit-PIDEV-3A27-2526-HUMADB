/**
 * HumaBot - AI Support Chatbot
 * Vanilla JavaScript implementation for cyberpunk-themed employee support
 */

class HumaChatbot {
    constructor() {
        this.sessionId = localStorage.getItem('chatbot_session') || null;
        this.isOpen = false;
        this.isTyping = false;
        this.messageHistory = [];
        
        // DOM elements
        this.elements = {};
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.loadSession();
        this.displayWelcomeMessage();
    }

    cacheElements() {
        this.elements = {
            toggle: document.getElementById('chatbot-toggle'),
            window: document.getElementById('chatbot-window'),
            close: document.getElementById('chatbot-close'),
            clear: document.getElementById('chatbot-clear'),
            input: document.getElementById('chatbot-input'),
            send: document.getElementById('chatbot-send'),
            messages: document.getElementById('chatbot-messages'),
            typing: document.getElementById('chatbot-typing'),
            badge: document.getElementById('chatbot-badge'),
            quickBtns: document.querySelectorAll('.quick-btn')
        };
    }

    bindEvents() {
        // Toggle chat window
        this.elements.toggle?.addEventListener('click', () => this.toggle());
        
        // Close button
        this.elements.close?.addEventListener('click', () => this.close());
        
        // Clear session
        this.elements.clear?.addEventListener('click', () => this.clearChat());
        
        // Send message
        this.elements.send?.addEventListener('click', () => {
            const message = this.elements.input.value.trim();
            this.sendMessage(message);
        });
        
        // Enter key to send
        this.elements.input?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const message = this.elements.input.value.trim();
                this.sendMessage(message);
            }
        });
        
        // Quick question buttons
        this.elements.quickBtns?.forEach(btn => {
            btn.addEventListener('click', () => {
                const message = btn.getAttribute('data-msg');
                if (message) {
                    this.elements.input.value = message;
                    this.sendMessage(message);
                }
            });
        });
        
        // Auto-resize input
        this.elements.input?.addEventListener('input', () => this.resizeInput());
        
        // Focus input when window opens
        this.elements.window?.addEventListener('transitionend', () => {
            if (this.isOpen) {
                this.elements.input?.focus();
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.elements.window?.classList.remove('hidden');
        this.hideBadge();
        this.scrollToBottom();
    }

    close() {
        this.isOpen = false;
        this.elements.window?.classList.add('hidden');
    }

    async sendMessage() {
        const input = this.elements.input;
        const message = input?.value?.trim();
        
        if (!message || this.isTyping) {
            return;
        }

        // Display user message
        this.displayMessage('user', message);
        
        // Clear input
        input.value = '';
        this.resizeInput();
        
        // Disable send button
        this.setSendButtonState(false);
        
        // Show typing indicator
        this.showTyping();
        
        try {
            const response = await fetch('/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            // Update session ID
            if (data.session_id) {
                this.sessionId = data.session_id;
                this.saveSession();
            }

            // Display assistant response
            this.displayMessage('assistant', data.reply);
            
        } catch (error) {
            console.error('Chatbot error:', error);
            console.error('Error details:', error.message, error.stack);
            
            // Display error message
            let errorMessage = 'Connection lost. Please try again.';
            
            if (error.message.includes('503')) {
                errorMessage = 'HumaBot is temporarily unavailable.';
            } else if (error.message.includes('429')) {
                errorMessage = 'Too many requests. Please wait a moment.';
            } else if (error.message.includes('403')) {
                errorMessage = 'Access denied. Please login again.';
            } else if (error.message.includes('404')) {
                errorMessage = 'Service not found. Please contact admin.';
            } else {
                errorMessage = `Error: ${error.message}`;
            }
            
            this.displayMessage('error', `\u26a0 ${errorMessage}`);
        } finally {
            // Hide typing indicator
            this.hideTyping();
            
            // Re-enable send button
            this.setSendButtonState(true);
            
            // Scroll to bottom
            this.scrollToBottom();
        }
    }

    displayMessage(role, content) {
        const messagesContainer = this.elements.messages;
        if (!messagesContainer) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `message-${role}`;
        
        // Format message content
        const formattedContent = this.formatMessage(content);
        messageDiv.innerHTML = formattedContent;
        
        messagesContainer.appendChild(messageDiv);
        
        // Add to history
        this.messageHistory.push({ role, content, timestamp: new Date() });
        
        // Scroll to bottom
        this.scrollToBottom();
    }

    async sendMessage(message) {
        // Guard against empty input
        const userInput = message.trim();
        if (!userInput || this.isTyping) {
            return;
        }
        
        // Clear input
        this.elements.input.value = '';
        this.resizeInput();
        
        // Disable send button
        this.setSendButtonState(false);
        
        // Show typing indicator
        this.showTyping();
        
        try {
            const response = await fetch('/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message: userInput,
                    session_id: this.sessionId || null
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server error response:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const data = await response.json();
                
            if (data.error) {
                throw new Error(data.error);
            }

            // Update session ID
            if (data.session_id) {
                this.sessionId = data.session_id;
                this.saveSession();
            }

            // Display assistant response
            this.displayMessage('assistant', data.reply);
                
        } catch (error) {
            console.error('Chatbot error:', error);
            console.error('Error details:', error.message, error.stack);
                
            // Display error message
            let errorMessage = 'Connection lost. Please try again.';
                
            if (error.message.includes('503')) {
                errorMessage = 'HumaBot is temporarily unavailable.';
            } else if (error.message.includes('429')) {
                errorMessage = 'Too many requests. Please wait a moment.';
            } else if (error.message.includes('403')) {
                errorMessage = 'Access denied. Please login again.';
            } else if (error.message.includes('404')) {
                errorMessage = 'Service not found. Please contact admin.';
            } else if (error.message.includes('400')) {
                errorMessage = 'Invalid request. Please try again.';
            } else {
                errorMessage = `Error: ${error.message}`;
            }
                
            this.displayMessage('error', `\u26a0 ${errorMessage}`);
        } finally {
            // Hide typing indicator
            this.hideTyping();
                
            // Re-enable send button
            this.setSendButtonState(true);
                
            // Scroll to bottom
            this.scrollToBottom();
        }
    }

    hideTyping() {
        this.isTyping = false;
        this.elements.typing?.classList.add('hidden');
    }

    setSendButtonState(enabled) {
        if (this.elements.send) {
            this.elements.send.disabled = !enabled;
            this.elements.send.textContent = enabled ? '\u27e4' : '\u23f3';
        }
    }

    async clearChat() {
        if (!this.sessionId) {
            // Just clear the UI if no session
            this.clearUI();
            return;
        }

        try {
            const response = await fetch('/chatbot/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    session_id: this.sessionId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                // Clear session
                this.sessionId = null;
                this.saveSession();
                
                // Clear UI
                this.clearUI();
                
                // Show confirmation
                this.displayMessage('assistant', 'Chat history cleared. How can I help you?');
            } else {
                throw new Error(data.error || 'Failed to clear session');
            }

        } catch (error) {
            console.error('Clear chat error:', error);
            this.displayMessage('error', '\u26a0 Failed to clear chat history');
        }
    }

    clearUI() {
        const messagesContainer = this.elements.messages;
        if (!messagesContainer) return;

        // Keep only welcome message
        while (messagesContainer.children.length > 1) {
            messagesContainer.removeChild(messagesContainer.lastChild);
        }
        
        // Clear history
        this.messageHistory = [];
    }

    displayWelcomeMessage() {
        const welcomeText = 'Welcome to HumaBot! I\'m here to help you with feedback, reputation scores, and platform navigation. Ask me anything!';
        this.displayMessage('assistant', welcomeText);
    }

    resizeInput() {
        const input = this.elements.input;
        if (!input) return;

        // Reset height to auto to get the natural height
        input.style.height = 'auto';
        
        // Set height to scrollHeight (content height) but limit it
        const maxHeight = 120; // Max 5 lines approximately
        input.style.height = Math.min(input.scrollHeight, maxHeight) + 'px';
    }

    scrollToBottom() {
        const messagesContainer = this.elements.messages;
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    // Format message content (handle newlines, links, etc.)
    formatMessage(content) {
        // Convert newlines to <br>
        content = content.replace(/\n/g, '<br>');
        
        // Convert URLs to links
        content = content.replace(
            /(https?:\/\/[^\s]+)/g,
            '<a href="$1" target="_blank" style="color: #00f5ff;">$1</a>'
        );
        
        // Bold text between ** **
        content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Italic text between * *
        content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        return content;
    }

    showTyping() {
        this.isTyping = true;
        this.elements.typing?.classList.remove('hidden');
        this.scrollToBottom();
    }

    saveSession() {
        if (this.sessionId) {
            localStorage.setItem('chatbot_session', this.sessionId);
        } else {
            localStorage.removeItem('chatbot_session');
        }
    }

    loadSession() {
        this.sessionId = localStorage.getItem('chatbot_session');
    }

    showBadge(count = 1) {
        if (this.elements.badge) {
            this.elements.badge.textContent = count;
            this.elements.badge.style.display = 'flex';
        }
    }

    hideBadge() {
        if (this.elements.badge) {
            this.elements.badge.style.display = 'none';
        }
    }

    // Public API methods
    openWithMessage(message) {
        this.open();
        this.elements.input.value = message;
        this.sendMessage();
    }

    // Handle visibility change to reset badge when user returns
    handleVisibilityChange() {
        if (!document.hidden && this.isOpen) {
            this.hideBadge();
        }
    }

    // Cleanup method
    destroy() {
        // Remove event listeners
        this.elements.toggle?.removeEventListener('click', this.toggle);
        this.elements.close?.removeEventListener('click', this.close);
        this.elements.clear?.removeEventListener('click', this.clearChat);
        this.elements.send?.removeEventListener('click', this.sendMessage);
        this.elements.input?.removeEventListener('keypress', this.sendMessage);
        
        // Remove visibility change listener
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
    }
}

// Auto-initialize when script loads
let humaChatbot = null;

// Initialize chatbot
document.addEventListener('DOMContentLoaded', () => {
    humaChatbot = new HumaChatbot();
    
    // Handle visibility changes
    document.addEventListener('visibilitychange', () => {
        if (humaChatbot) {
            humaChatbot.handleVisibilityChange();
        }
    });
    
    // Make chatbot globally accessible
    window.HumaChatbot = humaChatbot;
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HumaChatbot;
}
