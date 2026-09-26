<?php

namespace App\Services\Sources\Connectors;

/**
 * Examenul de consultant fiscal — cel cu drept de semnătură.
 */
final class CcfConsultantFiscalConnector extends CcfConnector
{
    public function key(): string
    {
        return 'ccf-consultant-fiscal';
    }

    protected function exam(): string
    {
        return 'consultant-fiscal';
    }

    protected function examName(): string
    {
        return 'consultant fiscal';
    }
}
