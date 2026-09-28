<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru categoria D, din `docs/auto/DRPCIV_qa_D.csv`.
 */
final class DrpcivCategoriaDConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'D';
    }
}
