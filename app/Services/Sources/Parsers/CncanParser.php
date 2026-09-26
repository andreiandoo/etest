<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește listele de întrebări pentru permisele de exercitare CNCAN.
 *
 * Fiecare specialitate are două documente: unul cu întrebările, cu câte cinci
 * variante, și unul cu „răspunsurile corecte (comentate)” — litera corectă
 * urmată de o explicație scrisă de comisie. E singura sursă din catalog care
 * publică motivul, nu doar răspunsul, iar explicația e chiar ce transformă un
 * test într-o pregătire.
 *
 * Numerotarea o repornește fiecare capitol, deci capitolele se împerechează în
 * ordine între cele două documente, nu după nume: cel de răspunsuri le scrie
 * altfel decât cel de întrebări, „bazele radioprotecției” față de
 * „radioprotecție”, pentru același capitol.
 *
 * Numerele se caută în ordine, dar cu goluri permise: documentul de întrebări
 * al röntgendiagnosticului sare de la 244 la 252, deși baremul are răspunsuri
 * pentru toate. Ce nu se găsește se raportează; o întrebare lipsă nu trebuie să
 * mute răspunsurile celorlalte cu șapte poziții.
 */
final class CncanParser
{
    private const SECTION = '/(?m)^[ \t]*Întrebări de ([^\n]{3,70}?)[ \t]*$/u';

    private const ANSWER = '/(?m)^[ \t]*(\d{1,4})\.[ \t]*([a-e])[ \t]*$/u';

    private const OPTION = '/(?<=\s)([a-e])\)\s/u';

    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $questions, string $answers): array
    {
        $answerSections = $this->answerSections($answers);
        $questionSections = $this->questionSections($questions);

        if ($answerSections === [] || count($answerSections) !== count($questionSections)) {
            return ['total' => 0, 'questions' => [], 'rejected' => [[
                'code' => 'capitole',
                'reason' => 'Documentul de întrebări are '.count($questionSections).' capitole, cel de '
                    .'răspunsuri '.count($answerSections).'. Nu le pot împerechea.',
                'text' => '',
            ]]];
        }

        $parsed = [];
        $rejected = [];
        $total = 0;

        foreach ($answerSections as $index => $section) {
            $body = $questionSections[$index]['body'];
            $slug = $questionSections[$index]['slug'];
            $name = $questionSections[$index]['name'];
            $starts = $this->questionStarts($body, array_keys($section['answers']));
            $total += count($section['answers']);

            foreach ($section['answers'] as $number => $answer) {
                if (! isset($starts[$number])) {
                    $rejected[] = $this->reject($name, $number, 'Baremul are răspuns, dar întrebarea lipsește din document.');

                    continue;
                }

                $chunk = $this->chunk($body, $starts, $number);
                $question = $this->question($chunk);

                if (isset($question['error'])) {
                    $rejected[] = $this->reject($name, $number, (string) $question['error'], $chunk);

                    continue;
                }

                $parsed[] = [
                    ...$question,
                    'number' => $number,
                    'correct' => ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5][$answer['letter']],
                    'explanation' => $answer['explanation'],
                    'section_slug' => $slug,
                    'section_name' => $name,
                ];
            }
        }

        return ['total' => $total, 'questions' => $parsed, 'rejected' => $rejected];
    }

    /**
     * @return array<int, array{name: string, answers: array<int, array{letter: string, explanation: string}>}>
     */
    private function answerSections(string $text): array
    {
        preg_match_all(self::SECTION, $text, $heads, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $sections = [];
        $count = count($heads);

        for ($i = 0; $i < $count; $i++) {
            $from = $heads[$i][0][1] + strlen($heads[$i][0][0]);
            $to = $i + 1 < $count ? $heads[$i + 1][0][1] : strlen($text);
            $block = substr($text, $from, $to - $from);

            preg_match_all(self::ANSWER, $block, $marks, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            $answers = [];
            $marked = count($marks);

            for ($j = 0; $j < $marked; $j++) {
                $start = $marks[$j][0][1] + strlen($marks[$j][0][0]);
                $stop = $j + 1 < $marked ? $marks[$j + 1][0][1] : strlen($block);
                $number = (int) $marks[$j][1][0];

                if (isset($answers[$number])) {
                    continue;
                }

                $answers[$number] = [
                    'letter' => $marks[$j][2][0],
                    'explanation' => trim((string) preg_replace('/\s+/u', ' ', substr($block, $start, $stop - $start))),
                ];
            }

            ksort($answers);
            $sections[] = ['name' => trim($heads[$i][1][0]), 'answers' => $answers];
        }

        return $sections;
    }

    /**
     * @return array<int, array{name: string, slug: string, body: string}>
     */
    private function questionSections(string $text): array
    {
        $clean = (string) preg_replace('/(?m)^[ \t]*COMISIA NAȚIONALĂ.*$/u', ' ', $text);
        $clean = (string) preg_replace('/(?m)^[ \t]*pagina \d+[ \t]*$/iu', ' ', $clean);

        preg_match_all(self::SECTION, $clean, $heads, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $sections = [];
        $count = count($heads);

        for ($i = 0; $i < $count; $i++) {
            $from = $heads[$i][0][1] + strlen($heads[$i][0][0]);
            $to = $i + 1 < $count ? $heads[$i + 1][0][1] : strlen($clean);
            $name = trim($heads[$i][1][0]);

            $sections[] = [
                'name' => $this->title($name),
                'slug' => $this->slug($name),
                'body' => ' '.trim((string) preg_replace('/\s+/u', ' ', substr($clean, $from, $to - $from))),
            ];
        }

        return $sections;
    }

    /**
     * Numerele se caută în ordine, fiecare după cel dinainte. Cel care nu se
     * găsește nu mută căutarea: următorul se caută tot de unde am rămas, ca un
     * gol din document să nu decaleze restul capitolului.
     *
     * @param  array<int, int>  $numbers
     * @return array<int, array{start: int, end: int}>
     */
    private function questionStarts(string $body, array $numbers): array
    {
        $starts = [];
        $offset = 0;

        foreach ($numbers as $number) {
            $pattern = '/(?<=\s)'.$number.'\s+(?=[^\s\d])/u';

            if (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE, $offset) !== 1) {
                continue;
            }

            $starts[$number] = ['start' => $match[0][1], 'end' => $match[0][1] + strlen($match[0][0])];
            $offset = $starts[$number]['end'];
        }

        return $starts;
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $starts
     */
    private function chunk(string $body, array $starts, int $number): string
    {
        $from = $starts[$number]['end'];
        $to = strlen($body);

        foreach ($starts as $candidate => $position) {
            if ($candidate > $number && $position['start'] > $from) {
                $to = min($to, $position['start']);
            }
        }

        return substr($body, $from, $to - $from);
    }

    /**
     * @return array<string, mixed>
     */
    private function question(string $chunk): array
    {
        $padded = ' '.$chunk;
        preg_match_all(self::OPTION, $padded, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $wanted = ['a', 'b', 'c', 'd', 'e'];
        $chosen = [];

        foreach ($matches as $match) {
            if ($match[1][0] === $wanted[count($chosen)]) {
                $chosen[] = $match;
            }

            if (count($chosen) === 5) {
                break;
            }
        }

        if (count($chosen) !== 5) {
            return ['error' => 'Nu am găsit cele cinci variante.'];
        }

        $prompt = trim(substr($padded, 0, $chosen[0][0][1]));
        $options = [];

        foreach ($chosen as $index => $match) {
            $start = $match[0][1] + strlen($match[0][0]);
            $stop = $index + 1 < 5 ? $chosen[$index + 1][0][1] : strlen($padded);
            $options[] = trim(substr($padded, $start, $stop - $start), " \t\n\r.;");
        }

        if ($prompt === '' || in_array('', $options, true)) {
            return ['error' => 'Enunțul sau una dintre variante e goală.'];
        }

        return ['prompt' => $prompt, 'options' => $options];
    }

    private function title(string $name): string
    {
        return mb_strtoupper(mb_substr($name, 0, 1), 'UTF-8').mb_substr($name, 1);
    }

    private function slug(string $name): string
    {
        $folded = strtr(mb_strtolower($name, 'UTF-8'), [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ş' => 's', 'ș' => 's', 'ţ' => 't', 'ț' => 't',
        ]);

        return trim((string) preg_replace('/-+/', '-', (string) preg_replace('/[^a-z0-9]+/u', '-', $folded)), '-');
    }

    /**
     * @return array{code: string, reason: string, text: string}
     */
    private function reject(string $section, int $number, string $reason, string $text = ''): array
    {
        return [
            'code' => $section.' — întrebarea '.$number,
            'reason' => $reason,
            'text' => trim(mb_substr($text, 0, 300)),
        ];
    }
}
