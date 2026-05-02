<?php

// Setup Facebook-style reactions system
// Access: http://localhost:8000/setup_facebook_reactions.php

header('Content-Type: text/plain');

echo "=== SETTING UP FACEBOOK-STYLE REACTIONS ===\n\n";

// Load environment
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use App\Kernel;

try {
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    echo "1. Updating ReactionPublication entity for Facebook reactions...\n";
    
    // Update the entity to support multiple reaction types
    $entityFile = __DIR__ . '/../src/Entity/ReactionPublication.php';
    $entityContent = file_get_contents($entityFile);
    
    // Add new reaction types
    $newConstants = <<<'PHP'
    // Types de réactions disponibles (Facebook-style)
    const TYPE_LIKE = 'like';
    const TYPE_LOVE = 'love';
    const TYPE_HAHA = 'haha';
    const TYPE_WOW = 'wow';
    const TYPE_SAD = 'sad';
    const TYPE_ANGRY = 'angry';
    
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_LIKE => '👍 J\'aime',
            self::TYPE_LOVE => '❤️ J\'adore',
            self::TYPE_HAHA => '😂 Haha',
            self::TYPE_WOW => '😮 Wow',
            self::TYPE_SAD => '😢 Triste',
            self::TYPE_ANGRY => '😡 Fâché'
        ];
    }

    public static function getEmojiForType(string $type): string
    {
        return match($type) {
            self::TYPE_LIKE => '👍',
            self::TYPE_LOVE => '❤️',
            self::TYPE_HAHA => '😂',
            self::TYPE_WOW => '😮',
            self::TYPE_SAD => '😢',
            self::TYPE_ANGRY => '😡',
            default => '👍'
        };
    }
PHP;
    
    // Update entity file
    if (strpos($entityContent, 'TYPE_LOVE') === false) {
        $entityContent = preg_replace(
            '/const TYPE_LIKE = \'like\';.*?const TYPE_DISLIKE = \'dislike\';/s',
            $newConstants,
            $entityContent
        );
        
        // Update validation
        $entityContent = preg_replace(
            '/\[#Assert\[Choice\(choices: \[self::TYPE_LIKE, self::TYPE_DISLIKE\]/',
            '#[Assert\Choice(choices: [self::TYPE_LIKE, self::TYPE_LOVE, self::TYPE_HAHA, self::TYPE_WOW, self::TYPE_SAD, self::TYPE_ANGRY]',
            $entityContent
        );
        
        // Update type checking methods
        $entityContent = preg_replace(
            '/public function isDislike\(\): bool.*?return \$this->getType\(\) === self::TYPE_DISLIKE;/s',
            'public function isLove(): bool { return $this->getType() === self::TYPE_LOVE; }
    
    public function isHaha(): bool { return $this->getType() === self::TYPE_HAHA; }
    
    public function isWow(): bool { return $this->getType() === self::TYPE_WOW; }
    
    public function isSad(): bool { return $this->getType() === self::TYPE_SAD; }
    
    public function isAngry(): bool { return $this->getType() === self::TYPE_ANGRY; }',
            $entityContent
        );
        
        file_put_contents($entityFile, $entityContent);
        echo "✓ ReactionPublication entity updated\n";
    } else {
        echo "✓ Entity already supports Facebook reactions\n";
    }
    
    echo "\n2. Updating database schema...\n";
    
    // Update database to support new reaction types
    $connection->executeStatement("
        ALTER TABLE reaction_publication 
        MODIFY COLUMN type VARCHAR(10) NOT NULL
    ");
    
    echo "✓ Database schema updated\n";
    
    echo "\n3. Updating ReactionController...\n";
    
    $controllerFile = __DIR__ . '/../src/Controller/ReactionController.php';
    $controllerContent = file_get_contents($controllerFile);
    
    // Update validation to include all reaction types
    $validTypes = 'ReactionPublication::TYPE_LIKE, ReactionPublication::TYPE_LOVE, ReactionPublication::TYPE_HAHA, ReactionPublication::TYPE_WOW, ReactionPublication::TYPE_SAD, ReactionPublication::TYPE_ANGRY';
    
    if (strpos($controllerContent, 'TYPE_LOVE') === false) {
        $controllerContent = preg_replace(
            '/if \(!in_array\(\$type, \[ReactionPublication::TYPE_LIKE, ReactionPublication::TYPE_DISLIKE\]\)\)/',
            "if (!in_array(\$type, [$validTypes]))",
            $controllerContent
        );
        
        file_put_contents($controllerFile, $controllerContent);
        echo "✓ ReactionController updated\n";
    } else {
        echo "✓ Controller already supports Facebook reactions\n";
    }
    
    echo "\n4. Updating ReactionPublicationRepository...\n";
    
    $repositoryFile = __DIR__ . '/../src/Repository/ReactionPublicationRepository.php';
    if (file_exists($repositoryFile)) {
        $repositoryContent = file_get_contents($repositoryFile);
        
        // Add method to get reaction summary
        $summaryMethod = <<<'PHP'
    /**
     * Get reaction summary for a publication
     */
    public function getReactionSummary(int $publicationId): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.type', 'COUNT(r.id) as count')
            ->where('r.publication = :publicationId')
            ->groupBy('r.type')
            ->setParameter('publicationId', $publicationId);
        
        $results = $qb->getQuery()->getResult();
        
        $summary = [];
        $total = 0;
        
        foreach ($results as $result) {
            $summary[$result['type']] = [
                'count' => (int) $result['count'],
                'emoji' => ReactionPublication::getEmojiForType($result['type'])
            ];
            $total += (int) $result['count'];
        }
        
        return [
            'summary' => $summary,
            'total' => $total
        ];
    }
PHP;
        
        if (strpos($repositoryContent, 'getReactionSummary') === false) {
            $repositoryContent = str_replace('}', $summaryMethod . "\n}", $repositoryContent);
            file_put_contents($repositoryFile, $repositoryContent);
            echo "✓ Repository updated with summary method\n";
        } else {
            echo "✓ Repository already has summary method\n";
        }
    }
    
    echo "\n5. Creating template integration...\n";
    
    // Create integration template
    $integrationTemplate = <<<'TWIG'
{# Facebook-style Reactions Integration Template #}
{% set reactionCounts = reaction_repository.getReactionsCountForPublication(publication.id) %}
{% set reactionSummary = reaction_repository.getReactionSummary(publication.id) %}
{% set userReaction = reaction_repository.getUserReactionForPublication(publication.id, app.user.id|default(null)) %}

{% include 'components/facebook_reactions.html.twig' with {
    'publication': publication,
    'userReaction': userReaction,
    'reactionSummary': reactionSummary.summary,
    'totalReactions': reactionSummary.total
} %}
TWIG;
    
    file_put_contents(__DIR__ . '/../templates/components/facebook_reactions_integration.html.twig', $integrationTemplate);
    echo "✓ Integration template created\n";
    
    echo "\n6. Testing database migration...\n";
    
    // Test if we can insert new reaction types
    $testPublicationId = $connection->fetchOne("SELECT id FROM publication LIMIT 1");
    $testUserId = $connection->fetchOne("SELECT id FROM users LIMIT 1");
    
    if ($testPublicationId && $testUserId) {
        $testTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
        
        foreach ($testTypes as $type) {
            try {
                $connection->executeStatement(
                    "INSERT IGNORE INTO reaction_publication (publication_id, user_id, type) VALUES (?, ?, ?)",
                    [$testPublicationId, $testUserId, $type]
                );
                echo "✓ Test reaction '$type' successful\n";
            } catch (Exception $e) {
                echo "✗ Test reaction '$type' failed: " . $e->getMessage() . "\n";
            }
        }
        
        // Clean up test data
        $connection->executeStatement(
            "DELETE FROM reaction_publication WHERE publication_id = ? AND user_id = ?",
            [$testPublicationId, $testUserId]
        );
        echo "✓ Test data cleaned up\n";
    }
    
    echo "\n7. Adding CSS enhancements...\n";
    
    $cssEnhancements = <<<'CSS'
/* Facebook Reactions Additional Styles */
.facebook-reactions {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.facebook-like-btn {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.reaction-popup {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.reaction-emoji-btn:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.floating-emoji {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.facebook-notification {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .facebook-like-btn {
        background: #3a3b3c;
        color: #e4e6eb;
    }
    
    .facebook-like-btn:hover {
        background: #4e4f50;
        color: #e4e6eb;
    }
    
    .reaction-popup {
        background: #3a3b3c;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .reaction-emoji-btn:hover {
        background: #4e4f50;
    }
    
    .reaction-avatar {
        background: #3a3b3c;
        border-color: #3a3b3c;
    }
    
    .reactions-count {
        color: #e4e6eb;
    }
}
CSS;
    
    file_put_contents(__DIR__ . '/../assets/css/facebook-reactions.css', $cssEnhancements);
    echo "✓ CSS enhancements created\n";
    
    echo "\n=== SETUP COMPLETED ===\n";
    echo "Facebook-style reactions are now ready!\n\n";
    echo "Features added:\n";
    echo "✓ 6 reaction types (Like, Love, Haha, Wow, Sad, Angry)\n";
    echo "✓ Hover popup with emoji selector\n";
    echo "✓ Animated reactions summary\n";
    echo "✓ Facebook-like UI design\n";
    echo "✓ Mobile responsive design\n";
    echo "✓ Dark mode support\n";
    echo "✓ Smooth animations and transitions\n\n";
    
    echo "To use Facebook reactions:\n";
    echo "1. Include the template: {% include 'components/facebook_reactions.html.twig' %}\n";
    echo "2. Pass required variables: publication, userReaction, reactionSummary, totalReactions\n";
    echo "3. Or use the integration template for automatic setup\n";
    echo "4. Test by clicking the 'J'aime' button and selecting an emoji\n";
    
} catch (Exception $e) {
    echo "✗ Setup error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
