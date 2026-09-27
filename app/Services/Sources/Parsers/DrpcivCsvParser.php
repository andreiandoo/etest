<?php

namespace App\Services\Sources\Parsers;

use RuntimeException;

/**
 * Citește chestionarele DRPCIV dintr-un fișier CSV cu o coloană pe răspuns.
 *
 * Forma fișierului: enunțul într-o coloană, până la trei răspunsuri corecte în
 * coloane separate, restul până la trei în coloanele de răspunsuri greșite.
 * Fiecare variantă își poartă litera la început — „A - ”, „B - ”, „C - ” — iar
 * litera nu e decor: ea spune în ce ordine au fost puse variantele la examen.
 * De aceea se citește și se folosește ca să refacem ordinea originală, apoi se
 * taie din text. Fără ea, variantele corecte ar rămâne grupate la început,
 * fiindcă așa vin coloanele.
 *
 * Explicația e text de lege copiat ca atare, în HTML. Uneori e un div gol, și
 * atunci întrebarea intră fără explicație — nu e un motiv s-o respingem.
 *
 * Capitolul se ia din adresa categoriei, ultimul segment: o adresă care se
 * termină în `/notiuni-de-mecanica` înseamnă capitolul „Noțiuni de mecanică”.
 */
final class DrpcivCsvParser
{
    /** Litera din capul unei variante, cu textul care îi urmează. */
    private const LETTERED = '/^\s*([A-C])\s*[-–]\s*(.+)$/su';

    private const REQUIRED = ['data', 'raspuns_corect_1', 'raspuns_1', 'image', 'explicatie', 'categorii'];

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
            $missing = array_diff(self::REQUIRED, $headers);

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
                $row = $this->row($headers, $values);
                $question = $this->question($row);

                if (isset($question['error'])) {
                    $rejected[] = [
                        'code' => 'rândul '.$line,
                        'reason' => (string) $question['error'],
                        'text' => mb_substr(trim((string) ($row['data'] ?? '')), 0, 200),
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

            // Fișierele vin cu marcaj de ordine a octeților în fața primei
            // coloane; lăsat acolo, numele coloanei nu se mai potrivește.
            if ($index === 0) {
                $header = (string) preg_replace('/^\xEF\xBB\xBF/', '', $header);
            }

            $clean[] = $header;
        }

        return $clean;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $values
     * @return array<string, string>
     */
    private function row(array $headers, array $values): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = trim((string) ($values[$index] ?? ''));
        }

        return $row;
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
        $prompt = $this->tidy($row['data'] ?? '');

        if ($prompt === '') {
            return ['error' => 'Rândul nu are enunț.'];
        }

        $options = [];

        foreach ([['raspuns_corect_1', true], ['raspuns_corect_2', true], ['raspuns_corect_3', true],
            ['raspuns_1', false], ['raspuns_2', false]] as [$column, $correct]) {
            $cell = $row[$column] ?? '';

            if (trim($cell) === '') {
                continue;
            }

            if (preg_match(self::LETTERED, $cell, $found) !== 1) {
                return ['error' => 'Varianta din coloana „'.$column.'” nu începe cu litera ei.'];
            }

            $letter = $found[1];

            if (isset($options[$letter])) {
                return ['error' => 'Litera „'.$letter.'” apare de două ori printre variante.'];
            }

            $options[$letter] = ['content' => $this->tidyOption($found[2]), 'is_correct' => $correct];
        }

        // Ordinea de la examen, nu ordinea coloanelor.
        ksort($options);

        if (count($options) !== 3) {
            return ['error' => 'Am găsit '.count($options).' variante de răspuns, nu trei.'];
        }

        foreach ($options as $option) {
            if ($option['content'] === '') {
                return ['error' => 'Una dintre variante e goală.'];
            }
        }

        if (! in_array(true, array_column($options, 'is_correct'), true)) {
            return ['error' => 'Nicio variantă nu e marcată drept corectă.'];
        }

        $chapter = $this->chapter($row['categorii'] ?? '');

        if ($chapter === '') {
            return ['error' => 'Nu am putut citi capitolul din adresa categoriei.'];
        }

        return [
            'prompt' => $prompt,
            'options' => array_values($options),
            'explanation' => $this->explanation($row['explicatie'] ?? ''),
            'image' => $this->image($row['image'] ?? ''),
            'chapter' => $chapter,
            'origin' => $row['data3'] ?? '',
        ];
    }

    /**
     * Ultimul segment al adresei categoriei.
     */
    private function chapter(string $url): string
    {
        $path = (string) parse_url(trim($url), PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path)));

        return $segments === [] ? '' : (string) end($segments);
    }

    /**
     * Textul de lege din spatele marcajului HTML. Un div gol nu e explicație.
     */
    private function explanation(string $html): ?string
    {
        $text = (string) preg_replace('/<[^>]+>/u', ' ', $html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? null : $text;
    }

    private function image(string $url): ?string
    {
        $url = trim($url);

        return preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    private function tidy(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\u{00A0}", ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Variantele vin din fișier cu punctuația de listă: primele două se termină
     * în punct și virgulă, ultima în punct. Afișate una sub alta, punctuația
     * asta arată a listă ruptă în bucăți, nu a variante de răspuns. Enunțul și-o
     * păstrează pe a lui.
     */
    private function tidyOption(string $value): string
    {
        return trim($this->tidy($value), " \t\n\r;.");
    }
}
