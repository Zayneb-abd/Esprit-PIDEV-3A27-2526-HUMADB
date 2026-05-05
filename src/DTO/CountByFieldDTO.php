<?php

namespace App\DTO;

class CountByFieldDTO
{
    public function __construct(
        public readonly string $key,
        public readonly int $count
    ) {
    }
}
