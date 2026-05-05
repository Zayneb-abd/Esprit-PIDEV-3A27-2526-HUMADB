<?php

namespace App\DTO;

class ReactionCountDTO
{
    public function __construct(
        public readonly string $reactionType,
        public readonly int $count
    ) {
    }
}
