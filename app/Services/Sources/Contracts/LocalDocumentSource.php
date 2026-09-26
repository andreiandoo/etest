<?php

namespace App\Services\Sources\Contracts;

/**
 * O sursă ale cărei fișiere stau deja în depozit.
 *
 * Nu orice autoritate lasă un program să-i descarce documentele. INPPA și UNBR
 * răspund 403 la orice cerere care nu vine dintr-un browser, deci fișierele
 * ajung în proiect aduse de mână, o dată, și de acolo le citește conducta.
 *
 * Amprenta se calculează la fel ca la sursele descărcate, din conținutul
 * fișierelor, deci o sesiune nouă adăugată în folder se vede la sincronizarea
 * următoare.
 */
interface LocalDocumentSource extends SourceConnector
{
    /**
     * Folderul cu fișierele sursei, relativ la rădăcina proiectului.
     */
    public function directory(): string;

    /**
     * @param  array<string, string>  $paths cale relativă în folder => cale absolută
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parseDocuments(array $paths): array;
}
