<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru tractor și tramvai, din `docs/auto/DRPCIV_qa_Tr-Tv.csv`.
 *
 * Fișierul le ține laolaltă și nu spune care întrebare e a cărei categorii,
 * deci stau într-o singură secțiune. Nu are nici capitole: întrebările intră
 * direct sub categorie. Proba are aceleași reguli ca la B și C, iar
 * troleibuzul — categoria Tb — e în aceeași grupă, dacă apare vreodată un
 * fișier pentru el.
 */
final class DrpcivTractorTramvaiConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'Tr-Tv';
    }

    protected function label(): string
    {
        return 'categoriile Tr și Tv';
    }
}
