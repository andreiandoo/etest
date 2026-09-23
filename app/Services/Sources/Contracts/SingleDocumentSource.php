<?php

namespace App\Services\Sources\Contracts;

/**
 * O sursă al cărei conținut vine dintr-un singur fișier.
 *
 * ANCOM publică un PDF, Ministerul Mediului un document Word. Sincronizarea
 * descarcă fișierul, îi scoate textul și îl dă parserului.
 */
interface SingleDocumentSource extends SourceConnector
{
    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text): array;
}
