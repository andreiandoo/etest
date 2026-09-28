<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru categoria E, din `docs/auto/DRPCIV_qa_E.csv`.
 */
final class DrpcivCategoriaEConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'E';
    }
}
