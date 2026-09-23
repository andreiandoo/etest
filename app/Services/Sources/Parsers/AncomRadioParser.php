<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește lista de subiecte de radiotehnică publicată de ANCOM.
 *
 * Formatul documentului, așa cum e descris chiar în preambulul lui: fiecare
 * subiect are un cod de forma `01A11/`, patru variante numerotate de la 1 la 4
 * și un singur răspuns corect, marcat cu `@`. Codul conține numărul din
 * capitol, gradul de dificultate (A–F) și capitolul; litera finală, acolo unde
 * apare, grupează variantele aceleiași probleme.
 *
 * Parserul primește text, nu PDF. Extragerea din PDF e o treabă separată și
 * schimbătoare — două biblioteci dau două rezultate — pe când regulile de mai
 * jos sunt cele ale documentului și pot fi verificate pe o probă fixă.
 *
 * Documentul are și defecte reale: variante numerotate greșit, un `@` rupt de
 * cifra lui, un subiect fără text. Parserul nu le ghicește. Ce nu iese curat
 * ajunge în lista de respinse, cu textul brut, ca să fie reparat de om în
 * panou — decât să intre în examen o întrebare cu răspunsul corect presupus.
 */
final class AncomRadioParser
{
    /**
     * Capitolele documentului, după prima cifră a codului.
     *
     * @var array<int, array{slug: string, name: string}>
     */
    private const CHAPTERS = [
        1 => ['slug' => 'notiuni-teoretice', 'name' => 'Noțiuni teoretice de electricitate și radio'],
        2 => ['slug' => 'componente', 'name' => 'Componente'],
        3 => ['slug' => 'circuite', 'name' => 'Circuite'],
        4 => ['slug' => 'receptoare', 'name' => 'Receptoare'],
        5 => ['slug' => 'emitatoare', 'name' => 'Emițătoare'],
        6 => ['slug' => 'antene-si-linii', 'name' => 'Antene și linii de transmisiune'],
        7 => ['slug' => 'propagare', 'name' => 'Propagare'],
        8 => ['slug' => 'masuratori', 'name' => 'Măsurători'],
        9 => ['slug' => 'interferente', 'name' => 'Interferențe'],
    ];

    /**
     * Gradele din document, A–F, aduse la scara de dificultate a platformei.
     *
     * @var array<string, int>
     */
    private const DIFFICULTY = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 5];

    private const CODE_PATTERN = '/(?<![\w])(\d{2})([A-F])(\d{2})([A-Z]?)\s*(?:\/|(?=\s+["„(\p{Lu}]))/u';

    private const MARKER_PATTERN = '/([1-4])\s*([)@])\s*([)@])?/u';

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    public static function chapters(): array
    {
        return array_values(self::CHAPTERS);
    }

    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text): array
    {
        $body = $this->normalize($text);

        if ($body === '') {
            return ['total' => 0, 'questions' => [], 'rejected' => []];
        }

        preg_match_all(self::CODE_PATTERN, $body, $codes, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $questions = [];
        $rejected = [];
        $count = count($codes);

        for ($i = 0; $i < $count; $i++) {
            $code = $codes[$i][1][0].$codes[$i][2][0].$codes[$i][3][0].$codes[$i][4][0];
            $start = $codes[$i][0][1] + strlen($codes[$i][0][0]);
            $end = $i + 1 < $count ? $codes[$i + 1][0][1] : strlen($body);
            $chunk = substr($body, $start, $end - $start);

            $parsed = $this->parseQuestion($code, $codes[$i][2][0], (int) $codes[$i][3][0][0], $codes[$i][4][0], $chunk);

            if (isset($parsed['error'])) {
                $rejected[] = [
                    'code' => $code,
                    'reason' => (string) $parsed['error'],
                    'text' => trim(mb_substr($chunk, 0, 400)),
                ];

                continue;
            }

            $questions[] = $parsed;
        }

        return ['total' => $count, 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * Curăță ce nu e conținut și aduce documentul la o singură linie.
     *
     * Antetele și cuprinsul se recunosc după faptul că nu au nicio literă mică;
     * o întrebare are întotdeauna. Tot ce e înainte de primul cod e preambulul
     * și bibliografia.
     */
    private function normalize(string $text): string
    {
        $kept = [];

        foreach (preg_split('/\R/u', str_replace("\f", "\n", $text)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^Pagina \d+ din \d+$/u', $line) === 1) {
                continue;
            }

            $isHeading = preg_match('/^(?:[IVX]+|\d+)\.\s+\S/u', $line) === 1
                && preg_match('/\p{Ll}/u', $line) === 0
                && ! str_contains($line, '/');

            if ($isHeading) {
                continue;
            }

            $kept[] = $line;
        }

        $body = (string) preg_replace('/\s+/u', ' ', implode(' ', $kept));

        if (preg_match(self::CODE_PATTERN, $body, $first, PREG_OFFSET_CAPTURE) !== 1) {
            return '';
        }

        return substr($body, $first[0][1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseQuestion(string $code, string $difficulty, int $section, string $group, string $chunk): array
    {
        $markers = $this->markers($chunk);
        $chosen = $this->chooseMarkers($chunk, $markers);

        if (count($chosen) !== 4) {
            return ['error' => 'Nu am găsit patru variante numerotate.'];
        }

        $correct = [];

        foreach ($chosen as $number => $marker) {
            if ($marker['correct']) {
                $correct[] = $number;
            }
        }

        if (count($correct) !== 1) {
            return ['error' => count($correct) === 0
                ? 'Niciun răspuns nu e marcat cu @.'
                : 'Sunt marcate cu @ mai multe răspunsuri.'];
        }

        $ordered = $chosen;
        uasort($ordered, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $prompt = trim(substr($chunk, 0, min(array_column($ordered, 'start'))));
        $options = [];
        $positions = array_values($ordered);
        $numbers = array_keys($ordered);

        foreach ($positions as $index => $marker) {
            $stop = $index + 1 < count($positions) ? $positions[$index + 1]['start'] : strlen($chunk);
            $text = substr($chunk, $marker['end'], $stop - $marker['end']);
            $options[$numbers[$index]] = trim((string) preg_replace('/^[)@\s.]+/u', '', $text), " .;\t\n\r");
        }

        ksort($options);

        if ($prompt === '' || in_array('', $options, true)) {
            return ['error' => 'Enunțul sau una dintre variante e goală.'];
        }

        $chapter = self::CHAPTERS[$section] ?? self::CHAPTERS[1];

        return [
            'code' => $code,
            'prompt' => $prompt,
            'options' => array_values($options),
            'correct' => $correct[0],
            'difficulty' => self::DIFFICULTY[$difficulty] ?? 3,
            'difficulty_letter' => $difficulty,
            'chapter_slug' => $chapter['slug'],
            'group' => $group !== '' ? $group : null,
        ];
    }

    /**
     * Toate marcajele de variantă din text, cu un scor de încredere.
     *
     * Un `4)` precedat de `(` sau `/` e aproape sigur parte din enunț — „antena
     * (în /4)”, „radical(3)” — nu începutul unei variante. Unul lipit de
     * cuvântul dinainte e suspect, dar există: „0,7V2) Uef=1V”. Scorul lasă
     * alegerea pe etape, de la sigur la ultimă instanță.
     *
     * @return array<int, array{number: int, start: int, end: int, correct: bool, score: int}>
     */
    private function markers(string $chunk): array
    {
        preg_match_all(self::MARKER_PATTERN, $chunk, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $markers = [];

        foreach ($matches as $match) {
            $start = $match[0][1];
            $previous = $start > 0 ? $chunk[$start - 1] : ' ';

            $score = match (true) {
                $previous === '(' || $previous === '/' => 0,
                $previous === '.' || ctype_space($previous) => 2,
                default => 1,
            };

            $separators = ($match[2][0] ?? '').($match[3][0] ?? '');

            $markers[] = [
                'number' => (int) $match[1][0],
                'start' => $start,
                'end' => $start + strlen($match[0][0]),
                'correct' => str_contains($separators, '@'),
                'score' => $score,
            ];
        }

        return $markers;
    }

    /**
     * Alege câte un marcaj pentru fiecare variantă, în trei încercări.
     *
     * Întâi în ordine, cum arată un document corect. Dacă nu iese — extragerea
     * din PDF citește uneori coloanele în ordinea 1, 3, 2, 4 — se ia prima
     * apariție a fiecărui număr, indiferent de ordine. Ce lipsește încă se
     * caută în intervalul rămas, întâi printre marcajele slabe, apoi ca simplă
     * cifră, pentru rândurile unde paranteza s-a pierdut de tot.
     *
     * @param  array<int, array{number: int, start: int, end: int, correct: bool, score: int}>  $markers
     * @return array<int, array{number: int, start: int, end: int, correct: bool, score: int}>
     */
    private function chooseMarkers(string $chunk, array $markers): array
    {
        $strong = array_values(array_filter($markers, static fn (array $m): bool => $m['score'] >= 2));

        $sequential = [];
        $after = -1;

        foreach ([1, 2, 3, 4] as $number) {
            foreach ($strong as $marker) {
                if ($marker['number'] === $number && $marker['start'] > $after) {
                    $sequential[$number] = $marker;
                    $after = $marker['start'];

                    break;
                }
            }
        }

        if (count($sequential) === 4) {
            return $sequential;
        }

        $chosen = [];

        foreach ($strong as $marker) {
            $chosen[$marker['number']] ??= $marker;
        }

        foreach ([1, 2, 3, 4] as $number) {
            if (isset($chosen[$number])) {
                continue;
            }

            [$from, $to] = $this->gap($chunk, $chosen, $number);

            if ($to <= $from) {
                continue;
            }

            $found = null;

            foreach ($markers as $marker) {
                if ($marker['number'] === $number && $marker['score'] >= 1 && $marker['start'] >= $from && $marker['start'] < $to) {
                    $found = $marker;

                    break;
                }
            }

            if ($found === null) {
                $found = $this->bareDigit($chunk, $number, $from, $to);
            }

            if ($found !== null) {
                $chosen[$number] = $found;
            }
        }

        return $chosen;
    }

    /**
     * Intervalul în care poate sta varianta lipsă: după cea dinainte, înainte
     * de cea de după.
     *
     * @param  array<int, array{number: int, start: int, end: int, correct: bool, score: int}>  $chosen
     * @return array{0: int, 1: int}
     */
    private function gap(string $chunk, array $chosen, int $number): array
    {
        $from = 0;
        $to = strlen($chunk);

        foreach ($chosen as $key => $marker) {
            if ($key < $number) {
                $from = max($from, $marker['end']);
            }

            if ($key > $number) {
                $to = min($to, $marker['start']);
            }
        }

        return [$from, $to];
    }

    /**
     * @return array{number: int, start: int, end: int, correct: bool, score: int}|null
     */
    private function bareDigit(string $chunk, int $number, int $from, int $to): ?array
    {
        $segment = substr($chunk, $from, $to - $from);
        $pattern = '/(?<=[\s.])'.$number.'(?=\s|\p{Lu})/u';

        if (preg_match($pattern, $segment, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return [
            'number' => $number,
            'start' => $from + $match[0][1],
            'end' => $from + $match[0][1] + strlen($match[0][0]),
            'correct' => false,
            'score' => 1,
        ];
    }
}
