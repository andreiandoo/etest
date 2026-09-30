<?php

namespace App\Services\Sources\Connectors;

/**
 * Examenul pentru conducătorii societăților de asigurare și de intermediere.
 *
 * Împarte cu examenul de definitivat un fond comun de peste patru sute de
 * întrebări și adaugă partea lui: guvernanță, raportări, soluționarea
 * litigiilor.
 */
final class IsfConducatorConnector extends IsfConnector
{
    public function key(): string
    {
        return 'isf-conducator';
    }

    protected function fileMarker(): string
    {
        return 'conduc';
    }

    protected function examSlug(): string
    {
        return 'conducator-asigurari';
    }

    protected function examName(): string
    {
        return 'conducător în asigurări';
    }
}
