<?php

namespace App\Twig;

use App\Repository\ReactionPublicationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ReactionExtension extends AbstractExtension
{
    private $reactionRepository;
    private array $cache = [];

    public function __construct(ReactionPublicationRepository $reactionRepository)
    {
        $this->reactionRepository = $reactionRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_reactions_count', [$this, 'getReactionsCount']),
            new TwigFunction('get_user_reaction', [$this, 'getUserReaction']),
        ];
    }

    public function getReactionsCount(int $publicationId): array
    {
        if (!isset($this->cache[$publicationId])) {
            $this->cache[$publicationId] = $this->reactionRepository->getReactionsCountForPublication($publicationId);
        }
        return $this->cache[$publicationId];
    }

    public function getUserReaction(int $publicationId, ?int $userId): ?\App\Entity\ReactionPublication
    {
        if (!$userId) {
            return null;
        }
        return $this->reactionRepository->getUserReactionForPublication($publicationId, $userId);
    }
}
