<?php

namespace App\Services\Sources\Connectors;

/**
 * Examenul de consultant fiscal asistent, cu chestionar propriu în fiecare
 * sesiune.
 */
final class CcfAsistentConnector extends CcfConnector
{
    public function key(): string
    {
        return 'ccf-consultant-fiscal-asistent';
    }

    protected function exam(): string
    {
        return 'consultant-fiscal-asistent';
    }

    protected function examName(): string
    {
        return 'consultant fiscal asistent';
    }
}
