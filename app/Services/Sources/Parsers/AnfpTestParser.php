<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește bateria de teste exemplificative publicată de ANFP.
 *
 * Documentul își spune singur regula, într-o notă de subsol: răspunsurile
 * corecte sunt îngroșate. Îngroșarea se pierde la extragerea din PDF, dar
 * acolo unde ea e, e și altceva: temeiul legal, între paranteze, la capătul
 * variantei — „(art. 1 alin. (1))”. Exact o variantă din trei îl are, în toate
 * întrebările documentului.
 *
 * Temeiul se scoate din textul variantei și se păstrează separat. Lăsat acolo,
 * ar face fiecare întrebare trivială: candidatul ar căuta paranteza, nu
 * răspunsul. Mutat în explicație, devine exact ce lipsește celorlalte surse —
 * un motiv, nu doar un răspuns.
 */
final class AnfpTestParser
{
    /**
     * Capitolele documentului, recunoscute după actul normativ din titlu.
     *
     * Nu după litera din față: o ediție viitoare poate schimba ordinea, dar nu
     * poate schimba faptul că secțiunea despre Codul administrativ vorbește
     * despre Codul administrativ.
     *
     * @var array<string, array{slug: string, name: string}>
     */
    private const CHAPTERS = [
        'constitu' => ['slug' => 'constitutia-romaniei', 'name' => 'Constituția României'],
        '57/2019' => ['slug' => 'administratie-publica', 'name' => 'Administrație publică'],
        '137/2000' => ['slug' => 'nediscriminare', 'name' => 'Prevenirea și combaterea discriminării'],
        '202/2002' => ['slug' => 'egalitate-de-sanse', 'name' => 'Egalitatea de șanse și tratament'],
    ];

    private const SECTION = '/(?<=\s)([A-Z])\.\s+(?=\p{Lu})/u';

    private const QUESTION = '/(?<=\s)(\d{1,3})\.\s*(?=[^\s\d])/u';

    private const OPTION = '/(?<=\s)([abc])\)\s*/u';

    private const CITATION = '/\((?:art|Art)\.\s.*$/us';

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
        $questions = [];
        $rejected = [];
        $total = 0;

        preg_match_all(self::SECTION, $body, $sections, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        $count = count($sections);

        for ($i = 0; $i < $count; $i++) {
            $from = $sections[$i][0][1] + strlen($sections[$i][0][0]);
            $to = $i + 1 < $count ? $sections[$i + 1][0][1] : strlen($body);
            $block = ' '.substr($body, $from, $to - $from);

            preg_match_all(self::QUESTION, $block, $starts, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            if ($starts === []) {
                continue;
            }

            $title = trim(substr($block, 0, $starts[0][0][1]));
            $chapter = $this->chapterFor($title);

            if ($chapter === null) {
                $rejected[] = [
                    'code' => $sections[$i][1][0].'. '.mb_substr($title, 0, 60),
                    'reason' => 'Nu recunosc actul normativ din titlul secțiunii, deci nu știu unde să pun întrebările.',
                    'text' => $title,
                ];

                continue;
            }

            foreach ($starts as $index => $start) {
                $total++;
                $begin = $start[0][1] + strlen($start[0][0]);
                $end = $index + 1 < count($starts) ? $starts[$index + 1][0][1] : strlen($block);
                $parsed = $this->question(substr($block, $begin, $end - $begin));

                if (isset($parsed['error'])) {
                    $rejected[] = [
                        'code' => $chapter['name'].' — întrebarea '.$start[1][0],
                        'reason' => (string) $parsed['error'],
                        'text' => trim(mb_substr(substr($block, $begin, $end - $begin), 0, 300)),
                    ];

                    continue;
                }

                $questions[] = [
                    ...$parsed,
                    'chapter_slug' => $chapter['slug'],
                    'number' => (int) $start[1][0],
                ];
            }
        }

        return ['total' => $total, 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * Numerele de pagină stau singure pe câte un rând și, odată lipite textul,
     * ar ajunge în mijlocul unei variante. Nota de subsol de la final e ultima
     * linie de conținut.
     */
    private function normalize(string $text): string
    {
        $kept = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (preg_match('/^\s*\d{1,3}\s*$/u', $line) !== 1) {
                $kept[] = $line;
            }
        }

        $body = ' '.trim((string) preg_replace('/\s+/u', ' ', implode(' ', $kept)));

        return (string) preg_split('/\*\s*R[ăa]spunsurile corecte/u', $body)[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function question(string $chunk): array
    {
        $padded = ' '.$chunk;
        preg_match_all(self::OPTION, $padded, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $wanted = ['a', 'b', 'c'];
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

        $prompt = trim(substr($padded, 0, $chosen[0][0][1]));
        $options = [];

        foreach ($chosen as $index => $match) {
            $start = $match[0][1] + strlen($match[0][0]);
            $stop = $index + 1 < 3 ? $chosen[$index + 1][0][1] : strlen($padded);
            $options[] = trim(substr($padded, $start, $stop - $start));
        }

        $cited = [];

        foreach ($options as $index => $option) {
            if (preg_match(self::CITATION, $option) === 1) {
                $cited[] = $index;
            }
        }

        if (count($cited) !== 1) {
            return ['error' => count($cited) === 0
                ? 'Nicio variantă nu citează un temei legal, deci nu știu care e corectă.'
                : 'Mai multe variante citează un temei legal.'];
        }

        $correct = $cited[0];
        preg_match(self::CITATION, $options[$correct], $citation);
        $reference = $this->tidyReference($citation[0]);
        $options[$correct] = trim((string) preg_replace(self::CITATION, '', $options[$correct]), " \t\n\r.;");

        if ($prompt === '' || in_array('', $options, true)) {
            return ['error' => 'Enunțul sau una dintre variante e goală.'];
        }

        return [
            'prompt' => $prompt,
            'options' => $options,
            'correct' => $correct + 1,
            'reference' => $reference,
        ];
    }

    /**
     * Scoate doar parantezele din jurul citării, nu și pe cele dinăuntru:
     * „(art. 5 lit. g))” citează litera g), nu litera g.
     */
    private function tidyReference(string $citation): string
    {
        $reference = trim($citation);

        if (str_starts_with($reference, '(') && str_ends_with($reference, ')')) {
            $reference = substr($reference, 1, -1);
        }

        return trim($reference);
    }

    /**
     * @return array{slug: string, name: string}|null
     */
    private function chapterFor(string $title): ?array
    {
        $folded = mb_strtolower($title, 'UTF-8');

        foreach (self::CHAPTERS as $needle => $chapter) {
            if (str_contains($folded, $needle)) {
                return $chapter;
            }
        }

        return null;
    }
}
