<?php

namespace App\Data;

final readonly class ScoreResult
{
    public function __construct(
        public float $awardedPoints,
        public bool $isCorrect,
    ) {}
}
