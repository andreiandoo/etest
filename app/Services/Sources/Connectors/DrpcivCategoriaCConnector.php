<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru categoria C, din `docs/auto/DRPCIV_qa_C.csv`.
 */
final class DrpcivCategoriaCConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'C';
    }
}
