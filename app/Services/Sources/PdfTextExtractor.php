<?php

namespace App\Services\Sources;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Scoate textul dintr-un PDF.
 *
 * Pasul ăsta e ținut separat de parsere pentru că e singurul care depinde de o
 * bibliotecă din afară, iar două biblioteci dau două rezultate. Parserele
 * primesc text și pot fi verificate pe probe fixe; aici se schimbă doar cum
 * ajungem la acel text.
 *
 * `pdftotext` din poppler citește layout-ul mai bine decât orice bibliotecă
 * PHP, așa că îl folosim când există pe mașină. Când nu există — cazul obișnuit
 * pe un server gestionat, unde nu avem sudo — cade pe biblioteca PHP.
 */
final class PdfTextExtractor
{
    public function extract(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException('Fișierul nu există: '.$path);
        }

        return $this->viaPoppler($path) ?? $this->viaLibrary($path);
    }

    private function viaPoppler(string $path): ?string
    {
        $binary = $this->popplerBinary();

        if ($binary === null) {
            return null;
        }

        $output = tempnam(sys_get_temp_dir(), 'etest-pdf');

        if ($output === false) {
            return null;
        }

        $command = escapeshellcmd($binary).' -enc UTF-8 '.escapeshellarg($path).' '.escapeshellarg($output);
        exec($command.' 2>/dev/null', $lines, $status);

        $text = $status === 0 && is_file($output) ? (string) file_get_contents($output) : null;
        @unlink($output);

        return $text !== null && trim($text) !== '' ? $text : null;
    }

    private function popplerBinary(): ?string
    {
        exec('command -v pdftotext 2>/dev/null', $output, $status);

        return $status === 0 && isset($output[0]) && $output[0] !== '' ? $output[0] : null;
    }

    private function viaLibrary(string $path): string
    {
        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (Throwable $exception) {
            throw new RuntimeException('Nu am putut citi textul din PDF: '.$exception->getMessage(), previous: $exception);
        }

        if (trim($text) === '') {
            throw new RuntimeException('PDF-ul nu conține text — probabil e scanat și are nevoie de OCR.');
        }

        return $text;
    }
}
