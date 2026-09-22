<?php

namespace App\Services\Content;

use Generator;
use JsonException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

final class ContentImportParser
{
    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function rows(string $path, string $format): Generator
    {
        yield from match (strtolower($format)) {
            'csv' => $this->csvRows($path),
            'json' => $this->jsonRows($path),
            'xlsx', 'xls' => $this->spreadsheetRows($path),
            default => throw new RuntimeException('Unsupported import format: '.$format),
        };
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    private function csvRows(string $path): Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Cannot open CSV file.');
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return;
            }

            $headers = array_map(static fn (mixed $header): string => trim((string) $header), $headers);

            while (($values = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($values)) {
                    continue;
                }

                $values = array_pad($values, count($headers), null);

                yield array_combine($headers, array_slice($values, 0, count($headers)));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return Generator<int, array<string, mixed>>
     *
     * @throws JsonException
     */
    private function jsonRows(string $path): Generator
    {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $rows = isset($decoded['questions']) && is_array($decoded['questions'])
            ? $decoded['questions']
            : $decoded;

        if (! is_array($rows)) {
            throw new RuntimeException('JSON import must contain an array of objects.');
        }

        foreach ($rows as $row) {
            if (is_array($row)) {
                yield $row;
            }
        }
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    private function spreadsheetRows(string $path): Generator
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $headers = [];

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $values = [];

            foreach ($row->getCellIterator() as $cell) {
                $values[] = $cell->getCalculatedValue();
            }

            if ($rowIndex === 1) {
                $headers = array_map(static fn (mixed $header): string => trim((string) $header), $values);

                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $values = array_pad($values, count($headers), null);

            yield array_combine($headers, array_slice($values, 0, count($headers)));
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
