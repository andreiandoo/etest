<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru categoria A, din `docs/auto/DRPCIV_qa_A.csv`.
 */
final class DrpcivCategoriaAConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'A';
    }
}
