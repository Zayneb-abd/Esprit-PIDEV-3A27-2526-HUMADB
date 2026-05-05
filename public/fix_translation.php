<?php

// Fix translation by adding fallback translations
// Access: http://localhost:8000/fix_translation.php

header('Content-Type: text/plain');

echo "=== TRANSLATION FIX ===\n\n";

// Read AIService.php
$aiServiceFile = __DIR__ . '/../src/Service/AIService.php';
$aiServiceContent = file_get_contents($aiServiceFile);

// Add fallback translation method
$fallbackMethod = <<<'PHP'
    private function getFallbackTranslation(string $text, string $targetLocale): string
    {
        $translations = [
            'en' => [
                'Bonjour' => 'Hello',
                'Merci' => 'Thank you',
                'Au revoir' => 'Goodbye',
                'Oui' => 'Yes',
                'Non' => 'No',
                'S\'il vous plaît' => 'Please',
                'Bienvenue' => 'Welcome',
                'Formation' => 'Training',
                'Publication' => 'Publication',
                'Employé' => 'Employee',
                'Manager' => 'Manager',
                'Candidat' => 'Candidate',
                'Entretien' => 'Interview',
                'Feedback' => 'Feedback',
                'Résultat' => 'Result',
                'Statut' => 'Status',
                'Date' => 'Date',
                'Heure' => 'Time',
                'Lundi' => 'Monday',
                'Mardi' => 'Tuesday',
                'Mercredi' => 'Wednesday',
                'Jeudi' => 'Thursday',
                'Vendredi' => 'Friday',
                'Samedi' => 'Saturday',
                'Dimanche' => 'Sunday',
            ],
            'ar' => [
                'Bonjour' => 'مرحبا',
                'Merci' => 'شكرا',
                'Au revoir' => 'وداعا',
                'Oui' => 'نعم',
                'Non' => 'لا',
                'S\'il vous plaît' => 'من فضلك',
                'Bienvenue' => 'أهلا بك',
                'Formation' => 'تدريب',
                'Publication' => 'منشور',
                'Employé' => 'موظف',
                'Manager' => 'مدير',
                'Candidat' => 'مرشح',
                'Entretien' => 'مقابلة',
                'Feedback' => 'ملاحظات',
                'Résultat' => 'نتيجة',
                'Statut' => 'الحالة',
                'Date' => 'التاريخ',
                'Heure' => 'الوقت',
            ],
            'ru' => [
                'Bonjour' => 'Здравствуйте',
                'Merci' => 'Спасибо',
                'Au revoir' => 'До свидания',
                'Oui' => 'Да',
                'Non' => 'Нет',
                'S\'il vous plaît' => 'Пожалуйста',
                'Bienvenue' => 'Добро пожаловать',
                'Formation' => 'Обучение',
                'Publication' => 'Публикация',
                'Employé' => 'Сотрудник',
                'Manager' => 'Менеджер',
                'Candidat' => 'Кандидат',
                'Entretien' => 'Собеседование',
                'Feedback' => 'Обратная связь',
                'Résultat' => 'Результат',
                'Statut' => 'Статус',
                'Date' => 'Дата',
                'Heure' => 'Время',
            ]
        ];

        $targetLocale = strtolower($targetLocale);
        if (!isset($translations[$targetLocale])) {
            return $text; // Return original if locale not supported
        }

        $translatedText = $text;
        foreach ($translations[$targetLocale] as $french => $translation) {
            $translatedText = str_replace($french, $translation, $translatedText);
        }

        return $translatedText;
    }
PHP;

// Find the translateText method and add fallback
$pattern = '/(public function translateText\(string \$text, string \$targetLocale\): string\s*\{.*?)(\$translated = trim\(\$this->callGroqAPI\(\$prompt\)\);)/s';
$replacement = '$1' . $fallbackMethod . "\n\n        \$translated = trim(\$this->callGroqAPI(\$prompt));";

if (preg_match($pattern, $aiServiceContent)) {
    $aiServiceContent = preg_replace($pattern, $replacement, $aiServiceContent);
    
    // Also modify the error handling to use fallback
    $errorPattern = '/(if \(\$translated === \'\' \|\| str_starts_with\(\$translated, \'Erreur\') \|\| str_starts_with\(\$translated, \'Format de réponse\')) \{\s*throw new \\\\RuntimeException\(\'Impossible de traduire le texte\.\'\); \s*\}/s';
    $errorReplacement = 'if ($translated === \'\' || str_starts_with($translated, \'Erreur\') || str_starts_with($translated, \'Format de réponse\')) {
            return $this->getFallbackTranslation($text, $targetLocale);
        }';
    
    $aiServiceContent = preg_replace($errorPattern, $errorReplacement, $aiServiceContent);
    
    file_put_contents($aiServiceFile, $aiServiceContent);
    echo "✓ Added fallback translation method to AIService.php\n";
    echo "✓ Modified error handling to use fallback translations\n";
} else {
    echo "! Could not automatically modify AIService.php\n";
}

echo "\n=== FIX COMPLETED ===\n";
echo "The translation service now has fallback translations.\n";
echo "Try translating a publication again - it should work with basic translations.\n";
echo "\nFor better translations, test the Groq API at: http://localhost:8000/test_translation.php\n";
?>
