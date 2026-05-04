<?php

// Enhanced JavaScript for professional reaction buttons
// Access: http://localhost:8000/enhance_reaction_javascript.php

header('Content-Type: text/plain');

echo "=== ENHANCING REACTION JAVASCRIPT ===\n\n";

$reactionFile = __DIR__ . '/../templates/components/reaction_buttons.html.twig';
$currentContent = file_get_contents($reactionFile);

$enhancedJavaScript = <<<'JS'
// Enhanced Reaction System with Professional UX
document.addEventListener('DOMContentLoaded', function() {
    // Initialize reaction system
    initReactionSystem();
    
    // Add hover effects and micro-interactions
    addMicroInteractions();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Add keyboard shortcuts
    addKeyboardShortcuts();
});

function initReactionSystem() {
    const reactionButtons = document.querySelectorAll('.reaction-btn');
    
    reactionButtons.forEach(button => {
        // Enhanced click handler
        button.addEventListener('click', handleReactionClick);
        
        // Enhanced hover effects
        button.addEventListener('mouseenter', handleReactionHover);
        button.addEventListener('mouseleave', handleReactionLeave);
        
        // Add ripple effect
        addRippleEffect(button);
    });
}

function handleReactionClick(e) {
    e.preventDefault();
    
    const button = e.currentTarget;
    const publicationId = button.closest('.reaction-buttons').dataset.publicationId;
    const type = button.dataset.type;
    const isCurrentlyActive = button.classList.contains('active');
    
    // Add haptic feedback (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Short vibration
    }
    
    // Show loading state with enhanced animation
    showEnhancedLoading(button);
    
    // Disable all reaction buttons temporarily
    const allButtons = button.closest('.reaction-container').querySelectorAll('.reaction-btn');
    allButtons.forEach(btn => btn.disabled = true);
    
    // Send reaction request
    fetch(`/reaction/add/${publicationId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'type': type
        })
    })
    .then(response => response.json())
    .then(data => {
        // Re-enable all buttons
        allButtons.forEach(btn => btn.disabled = false);
        
        hideEnhancedLoading(button);
        
        if (data.success) {
            // Update UI with enhanced animations
            updateReactionUIEnhanced(publicationId, data.counts, data.userReaction, isCurrentlyActive);
            
            // Show success notification with animation
            showEnhancedNotification(data.message || 'Réaction enregistrée!', 'success');
            
            // Add celebration effect for first-time reactions
            if (!isCurrentlyActive) {
                addCelebrationEffect(button);
            }
        } else {
            // Show error with enhanced styling
            showEnhancedNotification(data.error || 'Erreur lors de la réaction', 'error');
        }
    })
    .catch(error => {
        // Re-enable all buttons
        allButtons.forEach(btn => btn.disabled = false);
        
        hideEnhancedLoading(button);
        console.error('Reaction error:', error);
        showEnhancedNotification('Erreur de connexion au serveur', 'error');
    });
}

function handleReactionHover(e) {
    const button = e.currentTarget;
    const type = button.dataset.type;
    
    // Add hover sound effect (optional)
    playHoverSound();
    
    // Show preview tooltip with enhanced content
    showEnhancedTooltip(button, type);
    
    // Add subtle scale animation
    button.style.transform = 'scale(1.05)';
}

function handleReactionLeave(e) {
    const button = e.currentTarget;
    
    // Hide tooltip
    hideEnhancedTooltip();
    
    // Reset scale
    button.style.transform = 'scale(1)';
}

function showEnhancedLoading(button) {
    button.classList.add('loading');
    
    // Create enhanced loading indicator
    const loadingContent = `
        <div class="loading-enhanced">
            <div class="loading-spinner"></div>
            <div class="loading-text">En cours...</div>
        </div>
    `;
    
    // Store original content
    const originalContent = button.innerHTML;
    button.dataset.originalContent = originalContent;
    
    // Replace with loading content
    button.innerHTML = loadingContent;
}

function hideEnhancedLoading(button) {
    button.classList.remove('loading');
    
    // Restore original content
    if (button.dataset.originalContent) {
        button.innerHTML = button.dataset.originalContent;
        delete button.dataset.originalContent;
    }
}

function updateReactionUIEnhanced(publicationId, counts, userReaction, wasAlreadyActive) {
    const container = document.querySelector(`.reaction-buttons[data-publication-id="${publicationId}"]`);
    if (!container) return;
    
    const likeBtn = container.querySelector('.like-btn');
    const dislikeBtn = container.querySelector('.dislike-btn');
    const statusElement = container.querySelector('.reaction-status');
    
    // Update counts with animation
    updateCountWithAnimation(likeBtn, counts.like || 0);
    updateCountWithAnimation(dislikeBtn, counts.dislike || 0);
    
    // Update button states with enhanced effects
    updateButtonStatesEnhanced(likeBtn, dislikeBtn, userReaction);
    
    // Update status with enhanced message
    updateStatusEnhanced(statusElement, userReaction, wasAlreadyActive);
}

function updateCountWithAnimation(button, newCount) {
    const countElement = button.querySelector('.reaction-count');
    if (!countElement) return;
    
    const currentCount = parseInt(countElement.textContent) || 0;
    
    if (newCount > currentCount) {
        // Count increased - show celebration
        countElement.classList.add('count-increased');
        setTimeout(() => {
            countElement.classList.remove('count-increased');
        }, 600);
    } else if (newCount < currentCount) {
        // Count decreased - show subtle animation
        countElement.classList.add('count-decreased');
        setTimeout(() => {
            countElement.classList.remove('count-decreased');
        }, 600);
    }
    
    // Animate number change
    animateNumber(countElement, currentCount, newCount);
}

function animateNumber(element, from, to) {
    const duration = 300;
    const startTime = Date.now();
    
    function update() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const currentValue = Math.round(from + (to - from) * progress);
        element.textContent = currentValue;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

function updateButtonStatesEnhanced(likeBtn, dislikeBtn, userReaction) {
    // Remove all active states
    [likeBtn, dislikeBtn].forEach(btn => {
        btn.classList.remove('active');
        btn.classList.remove('just-activated');
    });
    
    if (userReaction) {
        if (userReaction.type === 'like' && likeBtn) {
            likeBtn.classList.add('active', 'just-activated');
            addActivationEffect(likeBtn, 'like');
        } else if (userReaction.type === 'dislike' && dislikeBtn) {
            dislikeBtn.classList.add('active', 'just-activated');
            addActivationEffect(dislikeBtn, 'dislike');
        }
    }
}

function addActivationEffect(button, type) {
    // Add activation particles
    const particles = button.querySelector('.reaction-particles');
    if (particles) {
        particles.innerHTML = createActivationParticles(type);
    }
}

function createActivationParticles(type) {
    const colors = {
        like: ['#28a745', '#20c997', '#3498db'],
        dislike: ['#dc3545', '#fd7e14', '#f8d7da']
    };
    
    const particleColors = colors[type] || colors.like;
    let particlesHTML = '';
    
    for (let i = 0; i < 6; i++) {
        const color = particleColors[i % particleColors.length];
        const x = (Math.random() - 0.5) * 40;
        const y = (Math.random() - 0.5) * 40;
        const size = Math.random() * 4 + 2;
        
        particlesHTML += `
            <div class="activation-particle" style="
                background: ${color};
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                animation-delay: ${Math.random() * 0.3}s;
            "></div>
        `;
    }
    
    return particlesHTML;
}

function updateStatusEnhanced(statusElement, userReaction, wasAlreadyActive) {
    if (!statusElement) return;
    
    if (userReaction) {
        const message = wasAlreadyActive 
            ? `Réaction modifiée : ${userReaction.emoji} ${userReaction.type}`
            : `Vous avez réagi : ${userReaction.emoji} ${userReaction.type}`;
        
        statusElement.innerHTML = `
            <div class="status-enhanced status-active">
                <div class="status-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="status-text">
                    <strong>${message}</strong>
                </div>
                <div class="status-time">
                    ${new Date().toLocaleTimeString('fr-FR')}
                </div>
            </div>
        `;
    } else {
        statusElement.innerHTML = `
            <div class="status-enhanced status-inactive">
                <div class="status-icon">
                    <i class="fas fa-hand-pointer"></i>
                </div>
                <div class="status-text">
                    <span>Réagissez à cette publication</span>
                    <small>Cliquez pour donner votre avis</small>
                </div>
            </div>
        `;
    }
}

function addCelebrationEffect(button) {
    // Add celebration animation
    button.style.animation = 'celebration 0.6s ease';
    setTimeout(() => {
        button.style.animation = '';
    }, 600);
}

function addRippleEffect(button) {
    button.addEventListener('click', function(e) {
        const rect = button.getBoundingClientRect();
        const ripple = document.createElement('span');
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
            transform: scale(0);
            animation: ripple 0.6s linear;
        `;
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.style.transform = 'scale(4)';
            ripple.style.opacity = '0';
        }, 10);
        
        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    });
}

function showEnhancedTooltip(button, type) {
    // Create enhanced tooltip
    const tooltip = document.createElement('div');
    tooltip.className = 'enhanced-tooltip';
    tooltip.innerHTML = `
        <div class="tooltip-content">
            <div class="tooltip-title">${type === 'like' ? 'J\'aime' : 'Je n\'aime pas'}</div>
            <div class="tooltip-description">
                ${type === 'like' 
                    ? 'Cliquez pour montrer votre appréciation' 
                    : 'Cliquez pour exprimer votre désaccord'}
            </div>
        </div>
    `;
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = button.getBoundingClientRect();
    tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    // Show with animation
    setTimeout(() => tooltip.classList.add('show'), 10);
}

function hideEnhancedTooltip() {
    const tooltip = document.querySelector('.enhanced-tooltip');
    if (tooltip) {
        tooltip.classList.remove('show');
        setTimeout(() => {
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        }, 200);
    }
}

function showEnhancedNotification(message, type = 'info') {
    // Create enhanced notification
    const notification = document.createElement('div');
    notification.className = `enhanced-notification enhanced-notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            </div>
            <div class="notification-text">${message}</div>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

function playHoverSound() {
    // Optional: Add subtle sound effects
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEARKwAAI');
        audio.volume = 0.1;
        audio.play().catch(() => {}); // Ignore errors
    } catch (e) {
        // Ignore if audio not supported
    }
}

function initializeTooltips() {
    // Add tooltips to all reaction buttons
    const buttons = document.querySelectorAll('.reaction-btn');
    buttons.forEach(button => {
        const type = button.dataset.type;
        button.title = type === 'like' ? 'J\'aime cette publication' : 'Je n\'aime pas cette publication';
    });
}

function addKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // L for like, D for dislike
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        const activeElement = document.activeElement;
        const reactionContainer = activeElement?.closest('.reaction-buttons');
        
        if (reactionContainer) {
            if (e.key === 'l' || e.key === 'L') {
                e.preventDefault();
                reactionContainer.querySelector('.like-btn')?.click();
            } else if (e.key === 'd' || e.key === 'D') {
                e.preventDefault();
                reactionContainer.querySelector('.dislike-btn')?.click();
            }
        }
    });
}

// Add enhanced CSS
const enhancedCSS = `
/* Enhanced Reaction System Styles */
.enhanced-tooltip {
    position: absolute;
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    opacity: 0;
    transform: translateY(5px);
    transition: all 0.3s ease;
    pointer-events: none;
}

.enhanced-tooltip.show {
    opacity: 1;
    transform: translateY(0);
}

.enhanced-tooltip .tooltip-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.enhanced-tooltip .tooltip-title {
    font-weight: 600;
    color: #fff;
}

.enhanced-tooltip .tooltip-description {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.8);
}

.enhanced-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.enhanced-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.enhanced-notification-success {
    border-left: 4px solid #28a745;
}

.enhanced-notification-error {
    border-left: 4px solid #dc3545;
}

.enhanced-notification .notification-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
}

.enhanced-notification .notification-icon {
    font-size: 18px;
    color: #28a745;
}

.enhanced-notification-error .notification-icon {
    color: #dc3545;
}

.enhanced-notification .notification-text {
    flex: 1;
    font-weight: 500;
}

.enhanced-notification .notification-close {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.enhanced-notification .notification-close:hover {
    background: #f8f9fa;
    color: #495057;
}

.loading-enhanced {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loading-text {
    font-size: 12px;
    font-weight: 500;
    opacity: 0.8;
}

.count-increased {
    animation: countIncrease 0.6s ease;
}

.count-decreased {
    animation: countDecrease 0.6s ease;
}

.status-enhanced {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-active {
    color: #28a745;
}

.status-inactive {
    color: #6c757d;
}

.status-icon {
    font-size: 16px;
    margin-bottom: 4px;
}

.status-text {
    font-weight: 500;
    line-height: 1.4;
}

.status-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 4px;
}

.activation-particle {
    position: absolute;
    border-radius: 50%;
    animation: particleFade 0.8s ease-out;
}

@keyframes ripple {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

@keyframes particleFade {
    0% {
        opacity: 1;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(0.5);
    }
}

@keyframes countIncrease {
    0% { transform: scale(1); color: #28a745; }
    50% { transform: scale(1.2); color: #20c997; }
    100% { transform: scale(1); color: #495057; }
}

@keyframes countDecrease {
    0% { transform: scale(1); color: #dc3545; }
    50% { transform: scale(1.2); color: #fd7e14; }
    100% { transform: scale(1); color: #495057; }
}

@keyframes celebration {
    0% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.1) rotate(5deg); }
    100% { transform: scale(1) rotate(0deg); }
}

/* Hide enhanced notification after animation */
.enhanced-notification.hide {
    opacity: 0;
    transform: translateX(100%);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .enhanced-notification {
        right: 10px;
        left: 10px;
        max-width: calc(100vw - 20px);
    }
    
    .enhanced-tooltip {
        font-size: 11px;
        padding: 6px 10px;
    }
}
`;

// Add enhanced styles to page
const styleElement = document.createElement('style');
styleElement.textContent = enhancedCSS;
document.head.appendChild(styleElement);

echo "✓ Enhanced reaction JavaScript created\n";
echo "✓ Added professional animations and interactions\n";
echo "✓ Added keyboard shortcuts (L for like, D for dislike)\n";
echo "✓ Added ripple effects and celebrations\n";
echo "✓ Added enhanced tooltips and notifications\n";
echo "\n=== ENHANCEMENT COMPLETED ===\n";
echo "The reaction system now has professional UX!\n";
echo "Features added:\n";
echo "- Smooth animations and transitions\n";
echo "- Ripple effects on click\n";
echo "- Enhanced tooltips with descriptions\n";
echo "- Celebration effects for reactions\n";
echo "- Keyboard shortcuts support\n";
echo "- Professional notifications\n";
echo "- Loading states and micro-interactions\n";
echo "- Mobile-optimized design\n";
echo "\nRefresh the page to see the enhanced reaction system!\n";
JS;

file_put_contents(__DIR__ . '/../templates/components/reaction_buttons_enhanced.html.twig', $enhancedJavaScript);

echo "✓ Created enhanced reaction template\n";
?>
