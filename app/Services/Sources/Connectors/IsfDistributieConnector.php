<?php

namespace App\Services\Sources\Connectors;

/**
 * Examenul de definitivat pentru intermediarii în asigurări și pentru angajații
 * societăților de asigurare.
 */
final class IsfDistributieConnector extends IsfConnector
{
    public function key(): string
    {
        return 'isf-distributie-asigurari';
    }

    protected function fileMarker(): string
    {
        return 'definitivat';
    }

    protected function examSlug(): string
    {
        return 'distributie-asigurari';
    }

    protected function examName(): string
    {
        return 'distribuție de asigurări';
    }
}
