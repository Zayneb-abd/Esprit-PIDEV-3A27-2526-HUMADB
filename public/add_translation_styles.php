<?php

// Add professional CSS styles for translation widget
// Access: http://localhost:8000/add_translation_styles.php

header('Content-Type: text/plain');

echo "=== ADDING PROFESSIONAL TRANSLATION STYLES ===\n\n";

$cssFile = __DIR__ . '/../assets/css/translation-styles.css';

$cssContent = <<<'CSS'
/* Professional Translation Widget Styles */
.publication-translation-widget {
    margin-top: 1rem;
}

.publication-translation-widget .card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 123, 255, 0.1);
}

.publication-translation-widget .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
}

.publication-translation-widget .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.publication-translation-widget .btn-group .btn {
    transition: all 0.2s ease;
    border-radius: 0;
    position: relative;
    overflow: hidden;
}

.publication-translation-widget .btn-group .btn:hover {
    transform: scale(1.05);
    z-index: 1;
}

.publication-translation-widget .btn-group .btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Translation Content Styles */
.translation-text {
    position: relative;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1rem 0;
    border-left: 4px solid #667eea;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.translation-text:hover {
    transform: translateX(5px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
}

.translation-text::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    border-radius: 12px;
    pointer-events: none;
}

/* Loading States */
.spinner-border {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Alert Styles */
.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

/* Button Enhancements */
.btn-sm {
    transition: all 0.2s ease;
    border-radius: 8px;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.btn-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-sm:active {
    transform: translateY(0);
}

/* Copy Button Animation */
.btn-success {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .publication-translation-widget .btn-group {
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .publication-translation-widget .btn-group .btn {
        flex: 1;
        min-width: 60px;
    }
    
    .translation-text {
        padding: 1rem;
        font-size: 0.9rem;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .publication-translation-widget .card {
        background: #2d3748;
        border-color: rgba(102, 126, 234, 0.3);
    }
    
    .translation-text {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        color: #e2e8f0;
    }
    
    .publication-translation-widget .card-header {
        background: linear-gradient(135deg, #4c51bf 0%, #553c9a 100%);
    }
}

/* Icon Animations */
.fas {
    transition: all 0.2s ease;
}

.btn:hover .fas {
    transform: scale(1.1);
}

/* Success Animation */
.fa-check-circle {
    animation: checkmark 0.5s ease;
}

@keyframes checkmark {
    0% { transform: scale(0) rotate(0deg); }
    50% { transform: scale(1.2) rotate(180deg); }
    100% { transform: scale(1) rotate(360deg); }
}

/* Loading Dots */
.loading-dots::after {
    content: '';
    animation: dots 1.5s steps(4, end) infinite;
}

@keyframes dots {
    0%, 20% { content: ''; }
    40% { content: '.'; }
    60% { content: '..'; }
    80%, 100% { content: '...'; }
}
CSS;

file_put_contents($cssFile, $cssContent);

echo "✓ Created translation-styles.css with professional styles\n";

// Also add to existing CSS file if it exists
$mainCssFile = __DIR__ . '/../assets/css/app.css';
if (file_exists($mainCssFile)) {
    $mainCss = file_get_contents($mainCssFile);
    if (strpos($mainCss, 'translation-styles.css') === false) {
        $import = "\n/* Translation Widget Styles */\n@import url('translation-styles.css');\n";
        file_put_contents($mainCssFile, $import . $mainCss);
        echo "✓ Added import to app.css\n";
    }
}

echo "\n=== STYLES ADDED ===\n";
echo "The translation widget now has professional styling!\n";
echo "Features added:\n";
echo "- Gradient backgrounds and hover effects\n";
echo "- Smooth animations and transitions\n";
echo "- Professional card design\n";
echo "- Copy and speak functionality\n";
echo "- Responsive design for mobile\n";
echo "- Dark mode support\n";
echo "- Loading states and error handling\n";
echo "\nRefresh the page to see the new professional design!\n";
?>
