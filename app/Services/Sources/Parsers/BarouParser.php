<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește grilele examenului de primire în profesia de avocat.
 *
 * Fiecare grilă are o sută de întrebări, împărțite în cinci materii a câte
 * douăzeci, cu trei variante din care una sau două sunt corecte.
 *
 * Răspunsul e publicat de două ori, în două fișiere diferite: scris în clar
 * sub fiecare întrebare din subiecte, și desenat în grila de corectură, unde
 * paradoxal literele marchează variantele greșite, iar cele corecte sunt
 * înlocuite cu un simbol. Parserul le citește pe amândouă și păstrează doar
 * întrebările la care cele două surse spun același lucru. Pe toate cele opt
 * grile ale sesiunii aprilie 2026, nu există nicio nepotrivire.
 *
 * Numerotarea e strictă, de la 1 la 100, și tocmai de asta se caută secvențial:
 * ordinea textului extras din PDF e uneori amestecată, dar numărul următor nu
 * poate fi decât următorul. Singura excepție e chiar numărul 100, pe care
 * generatorul îl scrie greșit, ca `1E`, în toate fișierele.
 */
final class BarouParser
{
    /**
     * Materiile probei, recunoscute după antetul de secțiune.
     *
     * @var array<string, array{slug: string, name: string}>
     */
    private const SUBJECTS = [
        'OEPA' => ['slug' => 'organizarea-profesiei', 'name' => 'Organizarea și exercitarea profesiei de avocat'],
        'Drept procesual civil' => ['slug' => 'drept-procesual-civil', 'name' => 'Drept procesual civil'],
        'Drept procesual penal' => ['slug' => 'drept-procesual-penal', 'name' => 'Drept procesual penal'],
        'Drept civil' => ['slug' => 'drept-civil', 'name' => 'Drept civil'],
        'Drept penal' => ['slug' => 'drept-penal', 'name' => 'Drept penal'],
    ];

    private const QUESTIONS = 100;

    private const SECTION = '/(?:[ABC]{1,3}\s+)?([\p{Lu}][\p{L} ]{3,40}?)\s*-\s*\((?:STAGIAR|DEFINITIV)\)/u';

    private const OPTION = '/(?<=\s)([ABC])\.\s+/u';

    private const ANSWER = '/R[ăa]spuns:\s*([A-C]{1,3})/u';

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    public static function subjects(): array
    {
        return array_values(self::SUBJECTS);
    }

    /**
     * Baremul: numărul întrebării, apoi trei poziții. Litera înseamnă variantă
     * greșită, orice altceva — de obicei o căsuță bifată — înseamnă corectă.
     *
     * @return array<int, string>
     */
    public function answerKey(string $text): array
    {
        $body = (string) preg_replace('/\s+/u', ' ', ' '.$text);
        preg_match_all('/(?<=\s)(\d{1,3})\s*(\S)\s*(\S)\s*(\S)(?=\s|$)/u', $body, $matches, PREG_SET_ORDER);

        $answers = [];

        foreach ($matches as $match) {
            $number = (int) $match[1];
            $letters = '';

            foreach ([$match[2], $match[3], $match[4]] as $index => $slot) {
                if (! in_array(mb_strtolower($slot), ['a', 'b', 'c'], true)) {
                    $letters .= ['A', 'B', 'C'][$index];
                }
            }

            if ($number >= 1 && $number <= self::QUESTIONS && $letters !== '' && ! isset($answers[$number])) {
                $answers[$number] = $letters;
            }
        }

        ksort($answers);

        return $answers;
    }

    /**
     * @param  array<int, string>  $answerKey
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text, array $answerKey): array
    {
        $body = $this->normalize($text);
        $starts = $this->questionStarts($body);
        $subjects = $this->subjectRanges($body);

        $questions = [];
        $rejected = [];

        for ($number = 1; $number <= self::QUESTIONS; $number++) {
            $start = $starts[$number] ?? null;

            if ($start === null) {
                $rejected[] = $this->reject($number, 'Nu am găsit întrebarea în document.');

                continue;
            }

            $next = $this->nextStart($starts, $number);
            $chunk = substr($body, $start['end'], ($next ?? strlen($body)) - $start['end']);
            $parsed = $this->question($chunk);

            if (isset($parsed['error'])) {
                $rejected[] = $this->reject($number, (string) $parsed['error'], $chunk);

                continue;
            }

            $expected = $answerKey[$number] ?? null;

            if ($expected === null) {
                $rejected[] = $this->reject($number, 'Baremul nu are răspuns pentru această întrebare.');

                continue;
            }

            if ($parsed['answer'] !== null && $parsed['answer'] !== $expected) {
                $rejected[] = $this->reject(
                    $number,
                    'Răspunsul din subiecte ('.$parsed['answer'].') nu se potrivește cu baremul ('.$expected.').',
                    $chunk,
                );

                continue;
            }

            $subject = $this->subjectAt($subjects, $start['start']);

            if ($subject === null) {
                $rejected[] = $this->reject($number, 'Întrebarea nu se află sub nicio materie.');

                continue;
            }

            $questions[] = [
                'number' => $number,
                'prompt' => $parsed['prompt'],
                'options' => $parsed['options'],
                'correct' => $this->positions($expected),
                'subject_slug' => $subject,
            ];
        }

        return ['total' => self::QUESTIONS, 'questions' => $questions, 'rejected' => $rejected];
    }

    private function normalize(string $text): string
    {
        $body = (string) preg_replace('/Pagina \d+ din \d+/u', ' ', $text);
        $body = (string) preg_replace('/Timp de lucru:\s*\d+\s*ore/u', ' ', $body);
        $body = (string) preg_replace('/Grila nr\.\s*\d+/u', ' ', $body);

        return ' '.trim((string) preg_replace('/\s+/u', ' ', $body));
    }

    /**
     * Fiecare număr se caută după cel dinainte. Ordinea textului poate fi
     * amestecată, dar numărul următor tot următorul rămâne.
     *
     * @return array<int, array{start: int, end: int}>
     */
    private function questionStarts(string $body): array
    {
        $starts = [];
        $offset = 0;

        for ($number = 1; $number <= self::QUESTIONS; $number++) {
            // Generatorul scrie 100 ca `1E`. E o eroare a lui, constantă în
            // toate fișierele sesiunii; dacă se îndreaptă, tiparul normal
            // prinde oricum.
            $tokens = $number === self::QUESTIONS ? [(string) $number, '1E'] : [(string) $number];

            foreach ($tokens as $token) {
                $pattern = '/(?<=\s)'.preg_quote($token, '/').'\s+(?=[\p{Lu}„(])/u';

                if (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
                    $starts[$number] = [
                        'start' => $match[0][1],
                        'end' => $match[0][1] + strlen($match[0][0]),
                    ];
                    $offset = $starts[$number]['end'];

                    break;
                }
            }
        }

        return $starts;
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $starts
     */
    private function nextStart(array $starts, int $number): ?int
    {
        for ($next = $number + 1; $next <= self::QUESTIONS; $next++) {
            if (isset($starts[$next])) {
                return $starts[$next]['start'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{at: int, slug: string}>
     */
    private function subjectRanges(string $body): array
    {
        preg_match_all(self::SECTION, $body, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $ranges = [];

        foreach ($matches as $match) {
            $name = trim($match[1][0]);
            $subject = self::SUBJECTS[$name] ?? null;

            if ($subject === null) {
                continue;
            }

            if ($ranges !== [] && end($ranges)['slug'] === $subject['slug']) {
                continue;
            }

            $ranges[] = ['at' => $match[0][1], 'slug' => $subject['slug']];
        }

        return $ranges;
    }

    /**
     * @param  array<int, array{at: int, slug: string}>  $ranges
     */
    private function subjectAt(array $ranges, int $position): ?string
    {
        $found = null;

        foreach ($ranges as $range) {
            if ($range['at'] <= $position) {
                $found = $range['slug'];
            }
        }

        return $found;
    }

    /**
     * @return array<string, mixed>
     */
    private function question(string $chunk): array
    {
        $padded = ' '.$chunk;
        preg_match_all(self::OPTION, $padded, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $wanted = ['A', 'B', 'C'];
        $chosen = [];

        foreach ($matches as $match) {
            if ($match[1][0] === $wanted[count($chosen)]) {
                $chosen[] = $match;
            }

            if (count($chosen) === 3) {
                break;
            }
        }

        if (count($chosen) !== 3) {
            return ['error' => 'Nu am găsit cele trei variante.'];
        }

        $answer = null;

        if (preg_match(self::ANSWER, $padded, $found) === 1) {
            $letters = str_split($found[1]);
            sort($letters);
            $answer = implode('', $letters);
        }

        $prompt = $this->strip(substr($padded, 0, $chosen[0][0][1]));
        $options = [];

        foreach ($chosen as $index => $match) {
            $start = $match[0][1] + strlen($match[0][0]);
            $stop = $index + 1 < 3 ? $chosen[$index + 1][0][1] : strlen($padded);
            $options[] = $this->strip(substr($padded, $start, $stop - $start));
        }

        if ($prompt === '' || in_array('', $options, true)) {
            return ['error' => 'Enunțul sau una dintre variante e goală.'];
        }

        return ['prompt' => $prompt, 'options' => $options, 'answer' => $answer];
    }

    private function strip(string $text): string
    {
        $clean = (string) preg_replace(self::ANSWER, '', $text);
        $clean = (string) preg_replace(self::SECTION, '', $clean);

        return trim((string) preg_replace('/\s+/u', ' ', $clean), " \t\n\r.;");
    }

    /**
     * @return array<int, int>
     */
    private function positions(string $letters): array
    {
        $positions = [];

        foreach (str_split($letters) as $letter) {
            $positions[] = ['A' => 1, 'B' => 2, 'C' => 3][$letter];
        }

        sort($positions);

        return $positions;
    }

    /**
     * @return array{code: string, reason: string, text: string}
     */
    private function reject(int $number, string $reason, string $text = ''): array
    {
        return [
            'code' => 'întrebarea '.$number,
            'reason' => $reason,
            'text' => trim(mb_substr($text, 0, 300)),
        ];
    }
}
