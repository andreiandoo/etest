<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru categoria B, din `docs/auto/DRPCIV_qa_B.csv`.
 */
final class DrpcivCategoriaBConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'B';
    }
}
