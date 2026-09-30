<?php

namespace App\Services\Sources\Parsers;

use RuntimeException;

/**
 * Citește băncile de întrebări ale Institutului de Studii Financiare.
 *
 * Institutul publică întrebările în PDF, ca tabel pe trei coloane, cu răspunsul
 * corect marcat printr-un asterisc. Tabelul acela nu se citește curat: coloanele
 * ies decalate pe verticală, iar un rând poate ține și enunțul unei întrebări,
 * și continuarea unei variante de la alta. De aceea PDF-urile trec întâi printr-o
 * transcriere în CSV, iar de aici încolo se citește o formă simplă și verificabilă:
 * o întrebare pe rând, cu cele trei variante în coloane separate și litera corectă
 * într-a șasea.
 *
 * Un rând căruia îi lipsește litera — fiindcă în PDF aveau asterisc două variante
 * sau niciuna — se raportează, nu se ghicește.
 */
final class IsfCsvParser
{
    private const COLUMNS = ['numar', 'intrebare', 'varianta_a', 'varianta_b', 'varianta_c', 'litera_corecta'];

    private const LETTERS = ['a' => 1, 'b' => 2, 'c' => 3];

    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Nu am putut deschide fișierul '.basename($path).'.');
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return ['total' => 0, 'questions' => [], 'rejected' => []];
            }

            $headers = $this->headers($headers);
            $missing = array_diff(self::COLUMNS, $headers);

            if ($missing !== []) {
                return ['total' => 0, 'questions' => [], 'rejected' => [[
                    'code' => basename($path),
                    'reason' => 'Fișierului îi lipsesc coloanele: '.implode(', ', $missing).'.',
                    'text' => '',
                ]]];
            }

            $questions = [];
            $rejected = [];
            $total = 0;
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($this->isEmpty($values)) {
                    continue;
                }

                $total++;
                $row = [];

                foreach ($headers as $index => $header) {
                    $row[$header] = trim((string) ($values[$index] ?? ''));
                }

                $question = $this->question($row);

                if (isset($question['error'])) {
                    $rejected[] = [
                        'code' => 'întrebarea '.($row['numar'] !== '' ? $row['numar'] : 'de pe rândul '.$line),
                        'reason' => (string) $question['error'],
                        'text' => mb_substr($row['intrebare'], 0, 200),
                    ];

                    continue;
                }

                $questions[] = $question;
            }

            return ['total' => $total, 'questions' => $questions, 'rejected' => $rejected];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string|null>  $headers
     * @return array<int, string>
     */
    private function headers(array $headers): array
    {
        $clean = [];

        foreach ($headers as $index => $header) {
            $header = trim((string) $header);

            if ($index === 0) {
                $header = (string) preg_replace('/^\xEF\xBB\xBF/', '', $header);
            }

            $clean[] = $header;
        }

        return $clean;
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function isEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function question(array $row): array
    {
        $prompt = $this->tidy($row['intrebare']);

        if ($prompt === '') {
            return ['error' => 'Rândul nu are enunț.'];
        }

        $options = [];

        foreach (['varianta_a', 'varianta_b', 'varianta_c'] as $column) {
            $options[] = $this->tidy($row[$column]);
        }

        if (in_array('', $options, true)) {
            return ['error' => 'Una dintre cele trei variante e goală.'];
        }

        $letter = mb_strtolower($this->tidy($row['litera_corecta']));

        if (! isset(self::LETTERS[$letter])) {
            return ['error' => $letter === ''
                ? 'Nu are marcat niciun răspuns corect. În PDF aveau asterisc două variante sau niciuna.'
                : 'Litera „'.$letter.'” nu e una dintre a, b sau c.'];
        }

        return [
            'number' => (int) $row['numar'],
            'prompt' => $prompt,
            'options' => $options,
            'correct' => self::LETTERS[$letter],
        ];
    }

    private function tidy(string $value): string
    {
        $value = str_replace("\u{00A0}", ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
