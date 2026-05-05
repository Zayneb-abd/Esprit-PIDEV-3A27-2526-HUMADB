<?php

namespace App\DTO;

class MostLikedPublicationDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $contenu,
        public readonly int $likeCount
    ) {
    }
}
