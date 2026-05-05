<?php

// Test translation API
// Access: http://localhost:8000/test_translation.php

header('Content-Type: text/plain');

echo "=== TRANSLATION API TEST ===\n\n";

// Test API key from .env
$groqApiKey = "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";

echo "Testing Groq API with key: " . substr($groqApiKey, 0, 10) . "...\n\n";

$url = 'https://api.groq.com/openai/v1/chat/completions';

$payload = [
    'model' => 'llama-3.1-8b-instant',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Traduis "Bonjour le monde" en anglais. Réponds uniquement avec la traduction.'
        ]
    ],
    'temperature' => 0.7,
    'max_tokens' => 1000
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $groqApiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo "CURL Error: " . ($curlError ?: 'None') . "\n\n";

if ($curlError) {
    echo "✗ Connection failed: " . $curlError . "\n";
    echo "Possible solutions:\n";
    echo "1. Check internet connection\n";
    echo "2. Verify API key is valid\n";
    echo "3. Check if Groq API is accessible\n";
} elseif ($httpCode !== 200) {
    echo "✗ API Error: HTTP " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    if ($httpCode === 401) {
        echo "API key is invalid or expired\n";
    } elseif ($httpCode === 429) {
        echo "Rate limit exceeded. Try again later\n";
    }
} else {
    echo "✓ API Response received\n";
    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        echo "Translation: " . trim($data['choices'][0]['message']['content']) . "\n";
    } else {
        echo "Invalid response format\n";
        echo "Raw response: " . $response . "\n";
    }
}

echo "\n=== TEST COMPLETED ===\n";
?>
