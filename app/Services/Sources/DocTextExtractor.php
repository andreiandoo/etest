<?php

namespace App\Services\Sources;

use RuntimeException;

/**
 * Scoate conținutul dintr-un document Word binar (.doc), cu formatarea intactă.
 *
 * Aici formatarea nu e un moft: la setul de întrebări pentru examenul de
 * vânător, răspunsul corect nu e marcat prin niciun semn din text, ci prin
 * scrierea îngroșată. O extragere de text simplu ar da o mie de întrebări fără
 * niciun răspuns.
 *
 * `.doc` e format binar OLE2, nu XML. Nu există bibliotecă PHP care să-i
 * citească formatarea de caracter în mod fiabil, așa că folosim `antiword`,
 * care scoate DocBook cu `<emphasis role='bold'>` pe fiecare fragment
 * îngroșat. E un pachet de câteva sute de kiloocteți, prezent în depozitele
 * obișnuite.
 *
 * Dacă lipsește, spunem limpede ce e de instalat în loc să întoarcem text fără
 * răspunsuri, care ar trece de parser și ar strica o mie de întrebări.
 */
final class DocTextExtractor
{
    public function extract(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException('Fișierul nu există: '.$path);
        }

        $binary = $this->antiword();

        if ($binary === null) {
            throw new RuntimeException(
                'Documentele .doc au nevoie de `antiword`, care nu e instalat. '
                .'Instalează-l cu `sudo apt install antiword`, sau convertește documentul '
                .'în altă parte și rulează cu --fisier=cale/catre/document.xml.'
            );
        }

        $command = escapeshellcmd($binary).' -m UTF-8.txt -x db '.escapeshellarg($path);
        exec($command.' 2>/dev/null', $lines, $status);

        if ($status !== 0 || $lines === []) {
            // Unele instalări nu au tabela de conversie UTF-8; a doua încercare
            // renunță la ea, iar diacriticele se repară la citirea textului.
            $lines = [];
            exec(escapeshellcmd($binary).' -x db '.escapeshellarg($path).' 2>/dev/null', $lines, $status);
        }

        if ($status !== 0 || $lines === []) {
            throw new RuntimeException('`antiword` nu a putut citi documentul.');
        }

        return implode("\n", $lines);
    }

    private function antiword(): ?string
    {
        exec('command -v antiword 2>/dev/null', $output, $status);

        return $status === 0 && isset($output[0]) && $output[0] !== '' ? $output[0] : null;
    }
}
