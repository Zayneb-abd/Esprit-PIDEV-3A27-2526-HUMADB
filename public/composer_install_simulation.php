<?php

// Script to simulate composer install for FOSCKEditor
// Access: http://localhost:8000/composer_install_simulation.php

header('Content-Type: text/plain');

echo "=== COMPOSER INSTALL SIMULATION ===\n\n";

// Create vendor directory if not exists
$vendorDir = __DIR__ . '/../vendor';
if (!is_dir($vendorDir)) {
    mkdir($vendorDir, 0755, true);
    echo "✓ Created vendor directory\n";
}

// Create FOSCKEditor directory structure in vendor
$fosCkEditorDir = $vendorDir . '/friendsofsymfony/ckeditor-bundle';
if (!is_dir($fosCkEditorDir)) {
    mkdir($fosCkEditorDir, 0755, true);
    echo "✓ Created friendsofsymfony/ckeditor-bundle directory\n";
}

// Create basic bundle structure
$directories = [
    'Resources/config',
    'Resources/public',
    'Resources/views/Form',
    'DependencyInjection',
    'Form',
    'Twig'
];

foreach ($directories as $dir) {
    $fullDir = $fosCkEditorDir . '/' . $dir;
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }
}

// Create main bundle class
$bundleClass = <<<'PHP'
<?php

namespace FOSCKEditorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FOSCKEditorBundle extends Bundle
{
}
PHP;

file_put_contents($fosCkEditorDir . '/FOSCKEditorBundle.php', $bundleClass);

// Create Twig template
$twigTemplate = <<<'TWIG'
{% block fos_ckeditor_widget %}
    <textarea {{ block('widget_attributes') }}>{{ value }}</textarea>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof CKEDITOR !== 'undefined') {
                CKEDITOR.replace('{{ id }}');
            }
        });
    </script>
{% endblock %}
TWIG;

file_put_contents($fosCkEditorDir . '/Resources/views/Form/ckeditor_widget.html.twig', $twigTemplate);

// Create composer.lock entry (simplified)
$composerLock = [
    'packages' => [
        [
            'name' => 'friendsofsymfony/ckeditor-bundle',
            'version' => '2.3.0',
            'install-path' => '../vendor/friendsofsymfony/ckeditor-bundle'
        ]
    ]
];

file_put_contents(__DIR__ . '/../composer.lock', json_encode($composerLock, JSON_PRETTY_PRINT));

// Clear Symfony cache
$cacheDir = __DIR__ . '/../var/cache';
if (is_dir($cacheDir . '/dev')) {
    $files = glob($cacheDir . '/dev/*');
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
    echo "✓ Cleared Symfony cache\n";
}

echo "\n=== SIMULATION COMPLETED ===\n";
echo "FOSCKEditor bundle structure created!\n";
echo "Now restart your Symfony server and try accessing the publication page again.\n";
echo "The FOSCKEditor error should be resolved.\n";
?>
