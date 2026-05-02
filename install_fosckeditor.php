<?php

// Script to install FOSCKEditor bundle
// This simulates composer require

echo "=== INSTALLING FOSCKEDITOR BUNDLE ===\n\n";

// Check if composer.json exists
if (!file_exists(__DIR__ . '/composer.json')) {
    echo "✗ composer.json not found\n";
    exit(1);
}

echo "✓ composer.json found\n";

// Read composer.json
$composerJson = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);

// Add FOSCKEditor to require
$composerJson['require']['friendsofsymfony/ckeditor-bundle'] = '^2.3';

// Write back to composer.json
file_put_contents(__DIR__ . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "✓ Added friendsofsymfony/ckeditor-bundle to composer.json\n";

// Create FOSCKEditor configuration
$configDir = __DIR__ . '/config/packages';
if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
}

$fosCkEditorConfig = <<<'YAML'
fos_ck_editor:
    configs:
        default:
            toolbar: ['document', 'forms', 'clipboard', 'editing', '/', 'basicstyles', 'paragraph', 'links', 'insert', 'styles', 'colors', 'tools']
            uiColor: '#ffffff'
            height: 360
YAML;

file_put_contents($configDir . '/fos_ck_editor.yaml', $fosCkEditorConfig);
echo "✓ Created config/packages/fos_ck_editor.yaml\n";

// Clear cache
$cacheDir = __DIR__ . '/var/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            $subFiles = glob($file . '/*');
            foreach ($subFiles as $subFile) {
                if (is_file($subFile)) {
                    unlink($subFile);
                }
            }
            rmdir($file);
        } elseif (is_file($file)) {
            unlink($file);
        }
    }
    echo "✓ Cleared cache\n";
}

echo "\n=== INSTALLATION COMPLETED ===\n";
echo "FOSCKEditor bundle has been configured!\n";
echo "Please run 'composer install' to actually download the package.\n";
echo "Then restart your Symfony server.\n";
?>
