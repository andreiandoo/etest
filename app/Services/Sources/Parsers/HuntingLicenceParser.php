<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește setul oficial de întrebări pentru examenul de vânător.
 *
 * Documentul e un Word în care fiecare întrebare are trei variante, iar
 * răspunsul corect e doar îngroșat — nicio literă, nicio steluță, nimic în
 * text. Parserul primește deci DocBook, nu text simplu: are nevoie de
 * `<emphasis role='bold'>` ca să știe care variantă e cea bună.
 *
 * Îngroșarea vine uneori pe lângă: un punct și virgulă, un spațiu rămas
 * îngroșat dintr-o corectură veche. De aceea se numără doar îngroșarea care
 * conține litere sau cifre; restul e zgomot de editare, nu un răspuns.
 *
 * Optzeci și ceva de întrebări cer o imagine — „Care din imaginile de mai jos
 * reprezintă gâsca de vară?”, cu variantele A, B și C. Fără imagini, întrebarea
 * nu are răspuns posibil, așa că nu intră: ajunge în lista de respinse, ca să
 * se vadă ce lipsește, nu ca să ajungă pe site o întrebare fără sens.
 */
final class HuntingLicenceParser
{
    /**
     * Capitolele documentului. Cheia e ce rămâne din titlu după ce scoatem
     * diacriticele și spațiile, fiindcă titlurile sunt scrise inconsecvent —
     * „SUBCAPITOLUL I. A” și „SUBCAPITOLUL 1.B” în același document.
     *
     * @var array<string, array{slug: string, name: string}>
     */
    private const CHAPTERS = [
        'MAMIFEREMARI' => ['slug' => 'mamifere-mari', 'name' => 'Mamifere mari'],
        'MAMIFEREMICI' => ['slug' => 'mamifere-mici', 'name' => 'Mamifere mici'],
        'PASARI' => ['slug' => 'pasari', 'name' => 'Păsări'],
        'LEGISLATIE' => ['slug' => 'legislatie-cinegetica', 'name' => 'Legislație în domeniul cinegetic'],
        'ETICAVANATOREASCA' => ['slug' => 'etica-vanatoreasca', 'name' => 'Etică vânătorească'],
        'CHINOLOGIE' => ['slug' => 'chinologie', 'name' => 'Chinologie'],
        'BOLIALEVANATULUI' => ['slug' => 'boli-ale-vanatului', 'name' => 'Boli ale vânatului'],
        'ARMEMUNITII' => ['slug' => 'arme-si-munitii', 'name' => 'Arme, muniții și echipamente de vânătoare'],
        'MANAGEMENTULSPECIILOR' => ['slug' => 'managementul-speciilor', 'name' => 'Managementul speciilor de interes vânătoresc'],
        'ORGANIZAREA' => ['slug' => 'organizarea-vanatorii', 'name' => 'Organizarea și practicarea vânătorii'],
    ];

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
    public function parse(string $docbook): array
    {
        $blocks = $this->blocks($docbook);
        $questions = [];
        $rejected = [];
        $total = 0;
        $count = count($blocks);
        $index = 0;

        while ($index < $count) {
            $block = $blocks[$index];

            if (! $this->startsQuestion($blocks, $index)) {
                $index++;

                continue;
            }

            $total++;
            $options = array_slice($blocks, $index + 1, 3);
            $index += 4;

            $parsed = $this->assemble($block, $options);

            if (isset($parsed['error'])) {
                $rejected[] = [
                    'code' => mb_substr($block['text'], 0, 80),
                    'reason' => (string) $parsed['error'],
                    'text' => $block['text'].' — '.implode(' / ', array_column($options, 'text')),
                ];

                continue;
            }

            $questions[] = $parsed;
        }

        return ['total' => $total, 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * Paragrafele care contează, cu capitolul curent lipit de fiecare.
     *
     * Ce cade: tot ce e înainte de primul capitol (antetul ordinului), nota de
     * subsol și anexa cu specii de la final, rândurile care nu conțin decât
     * imagini sau etichetele „A B C” de sub ele.
     *
     * @return array<int, array{text: string, bold: string, chapter: ?string}>
     */
    private function blocks(string $docbook): array
    {
        preg_match_all('/<para>(.*?)<\/para>/su', $docbook, $matches, PREG_SET_ORDER);

        $blocks = [];
        $chapter = null;
        $started = false;

        foreach ($matches as $match) {
            $text = $this->plain($match[1]);

            if (str_starts_with($text, '*NOT')) {
                break;
            }

            if (preg_match('/^(?:SUB)?CAPITOLUL/u', $text) === 1) {
                // „CAPITOLUL I – BIOLOGIA SPECIILOR” e doar un titlu-umbrelă:
                // întrebările lui stau sub subcapitolele care urmează, așa că
                // pentru el capitolul curent redevine necunoscut.
                $chapter = $this->chapterFor($text);
                $started = true;

                continue;
            }

            if (! $started || $this->isBlank($text)) {
                continue;
            }

            // Sub imaginile unei întrebări vizuale, etichetele vin uneori pe un
            // singur rând — „A B C”. Aruncat, rândul lăsa întrebarea fără
            // variante și îi înghițea pe următoarea; desfăcut, structura se
            // păstrează și întrebarea vizuală poate fi respinsă la locul ei.
            $labels = $this->labelRow($text);

            if ($labels !== []) {
                foreach ($labels as $label) {
                    $blocks[] = ['text' => $label.';', 'bold' => '', 'chapter' => $chapter];
                }

                continue;
            }

            preg_match_all("/<emphasis role='bold'>(.*?)<\/emphasis>/su", $match[1], $emphasis);

            $blocks[] = [
                'text' => $text,
                'bold' => $this->plain(implode(' ', $emphasis[1])),
                'chapter' => $chapter,
            ];
        }

        return $blocks;
    }

    /**
     * Un paragraf începe o întrebare dacă se termină cu două puncte sau semnul
     * întrebării. Trei întrebări din document nu au nicio punctuație la final;
     * pe acelea le recunoaștem după faptul că sunt îngroșate cap-coadă și sunt
     * urmate de trei variante.
     *
     * @param  array<int, array{text: string, bold: string, chapter: ?string}>  $blocks
     */
    private function startsQuestion(array $blocks, int $index): bool
    {
        $text = $blocks[$index]['text'];

        if (str_ends_with($text, ':') || str_ends_with($text, '?')) {
            return true;
        }

        if ($blocks[$index]['bold'] === '' || ! isset($blocks[$index + 3])) {
            return false;
        }

        foreach ([1, 2, 3] as $offset) {
            $candidate = $blocks[$index + $offset]['text'];

            if (! str_ends_with($candidate, ';') && ! str_ends_with($candidate, '.')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{text: string, bold: string, chapter: ?string}  $question
     * @param  array<int, array{text: string, bold: string, chapter: ?string}>  $options
     * @return array<string, mixed>
     */
    private function assemble(array $question, array $options): array
    {
        if (count($options) !== 3) {
            return ['error' => 'Întrebarea nu are trei variante.'];
        }

        if ($question['chapter'] === null) {
            return ['error' => 'Întrebarea nu se află sub niciun capitol.'];
        }

        if ($this->needsPicture($question['text'], $options)) {
            return ['error' => 'Întrebarea se bazează pe imagini, iar documentul nu ni le dă separat.'];
        }

        $correct = [];

        foreach ($options as $position => $option) {
            if ($this->meaningful($option['bold'])) {
                $correct[] = $position + 1;
            }
        }

        if (count($correct) !== 1) {
            return ['error' => count($correct) === 0
                ? 'Nicio variantă nu e îngroșată.'
                : 'Sunt îngroșate mai multe variante.'];
        }

        $texts = array_map(fn (array $option): string => $this->tidy($option['text']), $options);

        if (in_array('', $texts, true)) {
            return ['error' => 'O variantă e goală.'];
        }

        return [
            'prompt' => $this->tidy($question['text']),
            'options' => $texts,
            'correct' => $correct[0],
            'chapter_slug' => $question['chapter'],
        ];
    }

    /**
     * @param  array<int, array{text: string, bold: string, chapter: ?string}>  $options
     */
    private function needsPicture(string $prompt, array $options): bool
    {
        if (preg_match('/imagin/iu', $prompt) === 1) {
            return true;
        }

        foreach ($options as $option) {
            if (preg_match('/^(?:[abc]|nr\.?\s*\d+)[;.]?$/iu', trim($option['text'])) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Îngroșarea care nu conține nici literă, nici cifră — un punct și virgulă,
     * un spațiu — e rest de editare, nu marcaj de răspuns.
     */
    private function meaningful(string $bold): bool
    {
        return preg_match('/[\p{L}\p{N}]/u', $bold) === 1;
    }

    private function chapterFor(string $text): ?string
    {
        $key = $this->fold($text);

        foreach (self::CHAPTERS as $needle => $chapter) {
            if (str_contains($key, $needle)) {
                return $chapter['slug'];
            }
        }

        return null;
    }

    private function isBlank(string $text): bool
    {
        return (preg_replace('/\[pic\]|[\s\-_=]+/u', '', $text) ?? '') === '';
    }

    /**
     * @return array<int, string>
     */
    private function labelRow(string $text): array
    {
        $candidate = trim(str_replace('[pic]', ' ', $text));

        if (preg_match('/^(?:[abc]\)?[\s.;]*)+$/iu', $candidate) !== 1) {
            return [];
        }

        preg_match_all('/[abc]/iu', $candidate, $letters);

        if (count($letters[0]) < 2) {
            return [];
        }

        return array_map('mb_strtoupper', array_slice($letters[0], 0, 3));
    }

    private function plain(string $markup): string
    {
        $text = (string) preg_replace('/<[^>]+>/u', '', $markup);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function tidy(string $text): string
    {
        return trim($text, " \t\n\r;.");
    }

    /**
     * Litere mari, fără diacritice și fără spații — titlurile sunt scrise
     * inconsecvent și nu se pot compara altfel.
     */
    private function fold(string $text): string
    {
        $text = strtr(mb_strtoupper($text, 'UTF-8'), [
            'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ş' => 'S', 'Ș' => 'S', 'Ţ' => 'T', 'Ț' => 'T',
        ]);

        return (string) preg_replace('/[^A-Z]/u', '', $text);
    }
}
