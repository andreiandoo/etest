<?php

namespace App\Services\Sources\Contracts;

/**
 * O sursă al cărei conținut e împrăștiat în mai multe fișiere.
 *
 * OAMGMAMR publică, pentru fiecare specialitate, un test și o grilă de
 * corectură separate — optsprezece fișiere pentru un singur examen. Nici unul
 * dintre ele nu e de ajuns singur: testul n-are răspunsuri, grila n-are
 * întrebări.
 *
 * Amprenta sursei devine amprenta setului: dacă oricare fișier se schimbă, se
 * schimbă și ea, iar sincronizarea următoare vede asta.
 */
interface MultiDocumentSource extends SourceConnector
{
    /**
     * Fișierele de descărcat, cu un nume scurt pentru fiecare.
     *
     * @return array<string, string> nume => adresă
     */
    public function documents(): array;

    /**
     * @param  array<string, string>  $paths nume => cale locală
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parseDocuments(array $paths): array;
}
