<?php

namespace App\Services\Sources;

use App\Models\ContentImport;
use App\Models\Source;
use App\Models\SourceDocument;

/**
 * Ce s-a întâmplat la o sincronizare.
 *
 * @phpstan-type RejectedRow array{code: string, reason: string, text: string}
 */
final readonly class SyncResult
{
    /**
     * @param  array<int, array{code: string, reason: string, text: string}>  $rejected
     * @param  array{questions: int, tests: int}  $published
     */
    public function __construct(
        public string $status,
        public string $sha256,
        public Source $source,
        public ?SourceDocument $document = null,
        public ?ContentImport $import = null,
        public int $total = 0,
        public array $rejected = [],
        public int $tests = 0,
        public array $published = ['questions' => 0, 'tests' => 0],
    ) {}

    public function isUnchanged(): bool
    {
        return $this->status === 'unchanged';
    }
}
