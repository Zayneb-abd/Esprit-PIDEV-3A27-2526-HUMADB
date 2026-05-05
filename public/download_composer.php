<?php

// Script to download composer.phar
// Access: http://localhost:8000/download_composer.php

header('Content-Type: text/plain');

echo "=== DOWNLOADING COMPOSER ===\n\n";

// Download composer.phar
$composerUrl = 'https://getcomposer.org/composer-stable.phar';
$composerFile = __DIR__ . '/../composer.phar';

echo "Downloading composer.phar...\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 30
    ]
]);

$fileContent = file_get_contents($composerUrl, false, $context);

if ($fileContent === false) {
    echo "✗ Failed to download composer.phar\n";
    echo "Please download it manually from: https://getcomposer.org/download/\n";
    exit(1);
}

file_put_contents($composerFile, $fileContent);
echo "✓ composer.phar downloaded successfully\n";

// Make it executable (not needed on Windows, but good practice)
chmod($composerFile, 0755);

echo "\n=== COMPOSER READY ===\n";
echo "Now run this command in your terminal:\n";
echo "D:\\xamp1\\php\\php.exe composer.phar install\n";
echo "\nThis will install FOSCKEditor bundle.\n";
?>
