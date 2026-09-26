<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește caietele de concurs de la admiterea la medicină.
 *
 * O sesiune are un caiet pe variantă — A, B, C, D — și un singur barem cu
 * câte o coloană pentru fiecare variantă. Variantele conțin aceleași întrebări,
 * amestecate, cu răspunsurile puse în altă ordine. Asta e chiar ce face
 * verificarea posibilă: răspunsul corect al unei întrebări se citește de patru
 * ori, o dată din fiecare variantă, și de fiecare dată trebuie să iasă același
 * text. Dacă nu iese, întrebarea nu se publică.
 *
 * Verificarea nu e un lux. Baremul e un tabel, iar tabelele ies din PDF-uri
 * altfel de la un extractor la altul: numerele de rând ale UMFT sunt desenate
 * cu o linie mai jos decât literele, iar un cititor care se ia după rânduri
 * decalează tot. De aceea coloanele se citesc pe verticală, după poziția în
 * linie, iar numerotarea din tabel se ignoră cu totul: coloana are exact atâtea
 * grupe de litere câte întrebări are varianta, în ordine.
 *
 * Întrebările au între unu și patru răspunsuri corecte din cinci, deci nu se
 * poate ghici nimic din formă.
 */
final class UmfAdmitereParser
{
    /**
     * Antetul fiecărei pagini spune varianta. Linia se scoate de tot: lăsată
     * în text, se lipește de ultimul răspuns de pe pagină, iar același răspuns
     * ar arăta diferit în două variante.
     */
    private const VARIANT = '/Varianta\s*:?\s*([A-D])\b/u';

    private const NUMBER = '/^\s*(\d{1,3})[.)]\s+(\S.*)$/u';

    private const OPTION = '/^\s*([A-E])[.)]\s+(\S.*)$/u';

    private const NOISE = '/^\s*(\d{1,3}|Pagina\s+\d+.*)\s*$/iu';

    /** Un grup de litere din barem: „ad”, „bce”, „c”. */
    private const LETTERS = '/(?<![A-Za-z])([a-e]{1,5})(?![A-Za-z])/u';

    /**
     * Un rând de barem n-are pe el decât cifre, puncte și litere de la a la e.
     *
     * Restul se sare: antetul de coloane repetat pe fiecare pagină și semnătura
     * președintelui comisiei, în care „Comisiei Centrale de Admitere” ascunde un
     * „de” care ar intra în tabel ca un răspuns.
     */
    private const ROW = '/^[\s\d.a-e]*$/u';

    /** Cât de departe pot sta două poziții și să fie tot aceeași coloană. */
    private const COLUMN_TOLERANCE = 6;

    /**
     * @param  array<int, string>  $subjects  textul fiecărui caiet de concurs
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(array $subjects, string $answers): array
    {
        $blocks = $this->variantBlocks($subjects);

        if (count($blocks) < 2) {
            return $this->refuse('Am găsit '.count($blocks).' variante în caiete. Fără cel puțin două, '
                .'răspunsurile nu se pot verifica.');
        }

        $columns = $this->answerColumns($answers, count($blocks));

        if (count($columns) !== count($blocks)) {
            return $this->refuse('Baremul nu are '.count($blocks).' coloane de aceeași lungime, câte variante '
                .'am găsit în caiete.');
        }

        $letters = array_keys($blocks);
        $readings = [];

        foreach (array_values($blocks) as $index => $block) {
            foreach ($this->questions($block) as $question) {
                $marked = $columns[$index][$question['number'] - 1] ?? null;

                if ($marked === null) {
                    continue;
                }

                $readings[$this->identity($question)][] = [
                    'variant' => $letters[$index],
                    'question' => $question,
                    'correct' => $this->correctTexts($question['options'], $marked),
                ];
            }
        }

        $questions = [];
        $rejected = [];

        foreach ($readings as $views) {
            $agreed = $this->agreement($views);
            $prompt = (string) $views[0]['question']['prompt'];

            if ($agreed === null) {
                $rejected[] = [
                    'code' => mb_substr($prompt, 0, 70),
                    'reason' => count($views) < 2
                        ? 'Am citit întrebarea într-o singură variantă, deci răspunsul nu are cu ce să fie verificat.'
                        : 'Variantele nu dau același răspuns corect. Fie caietul, fie baremul s-a citit greșit.',
                    'text' => $this->disagreement($views),
                ];

                continue;
            }

            $first = $views[0]['question'];

            $questions[] = [
                'prompt' => $prompt,
                'options' => array_values($first['options']),
                'correct' => $this->indexes($first['options'], $agreed),
                'number' => $first['number'],
                'variants' => count($views),
            ];
        }

        return ['total' => count($readings), 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * Textul tuturor caietelor, împărțit pe variante. Un caiet poate conține o
     * singură variantă sau pe toate patru — antetul paginii decide, nu fișierul.
     *
     * @param  array<int, string>  $subjects
     * @return array<string, string>
     */
    private function variantBlocks(array $subjects): array
    {
        $blocks = [];

        foreach ($subjects as $text) {
            foreach (explode("\f", $text) as $page) {
                $variant = null;
                $kept = [];

                foreach (explode("\n", $page) as $line) {
                    if (mb_strlen(trim($line)) < 120 && preg_match(self::VARIANT, $line, $found) === 1) {
                        $variant = $found[1];

                        continue;
                    }

                    $kept[] = $line;
                }

                if ($variant !== null) {
                    $blocks[$variant] = ($blocks[$variant] ?? '')."\n".implode("\n", $kept);
                }
            }
        }

        ksort($blocks);

        return $blocks;
    }

    /**
     * Întrebările unei variante, în ordinea din caiet.
     *
     * Enunțurile și răspunsurile se întind pe mai multe rânduri, iar rândul de
     * continuare se lipește la ce a început ultimul — altfel același răspuns
     * iese trunchiat în variante diferite, după cum s-a nimerit ruptura de
     * pagină.
     *
     * @return array<int, array{number: int, prompt: string, options: array<string, string>}>
     */
    private function questions(string $block): array
    {
        $found = [];
        $current = null;
        $target = null;

        foreach (explode("\n", $block) as $line) {
            if (preg_match(self::NOISE, $line) === 1) {
                continue;
            }

            if (preg_match(self::NUMBER, $line, $start) === 1) {
                $found[] = ['number' => (int) $start[1], 'prompt' => [$start[2]], 'options' => []];
                $current = count($found) - 1;
                $target = 'prompt';

                continue;
            }

            if ($current !== null && preg_match(self::OPTION, $line, $option) === 1) {
                $found[$current]['options'][$option[1]] = [$option[2]];
                $target = $option[1];

                continue;
            }

            if ($current === null || $target === null || trim($line) === '') {
                continue;
            }

            if ($target === 'prompt') {
                $found[$current]['prompt'][] = trim($line);
            } elseif (isset($found[$current]['options'][$target])) {
                $found[$current]['options'][$target][] = trim($line);
            }
        }

        $questions = [];

        foreach ($found as $question) {
            if (count($question['options']) !== 5) {
                continue;
            }

            $options = [];

            foreach ($question['options'] as $letter => $parts) {
                $options[$letter] = $this->tidy($parts);
            }

            $questions[] = [
                'number' => $question['number'],
                'prompt' => $this->tidy($question['prompt']),
                'options' => $options,
            ];
        }

        return $questions;
    }

    /**
     * Coloanele baremului, citite pe verticală.
     *
     * Fiecare grup de litere își află coloana din poziția lui în linie, iar
     * coloanele bune sunt cele care au toate aceeași lungime și sunt exact
     * atâtea câte variante avem. Restul — un „de” rătăcit din semnătura de la
     * sfârșit — cade de la sine.
     *
     * @return array<int, array<int, string>>
     */
    private function answerColumns(string $answers, int $variants): array
    {
        $hits = [];

        foreach (explode("\n", $answers) as $line) {
            if (preg_match(self::ROW, $line) !== 1) {
                continue;
            }

            if (preg_match_all(self::LETTERS, $line, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
                continue;
            }

            foreach ($matches as $match) {
                // Poziția se măsoară în caractere, nu în octeți: o literă cu
                // diacritice mai la stânga nu trebuie să mute coloana.
                $hits[] = [mb_strlen(substr($line, 0, $match[1][1])), $match[1][0]];
            }
        }

        if ($hits === []) {
            return [];
        }

        $edges = $this->columnEdges(array_column($hits, 0));
        $columns = [];

        foreach ($hits as [$position, $letters]) {
            $column = 0;

            foreach ($edges as $index => $edge) {
                if ($position >= $edge) {
                    $column = $index;
                }
            }

            $columns[$column][] = $letters;
        }

        ksort($columns);

        return $this->equalColumns($columns, $variants);
    }

    /**
     * @param  array<int, int>  $positions
     * @return array<int, int>
     */
    private function columnEdges(array $positions): array
    {
        $unique = array_values(array_unique($positions));
        sort($unique);

        $edges = [];
        $previous = null;

        foreach ($unique as $position) {
            if ($previous === null || $position - $previous > self::COLUMN_TOLERANCE) {
                $edges[] = $position;
            }

            $previous = $position;
        }

        return $edges;
    }

    /**
     * @param  array<int, array<int, string>>  $columns
     * @return array<int, array<int, string>>
     */
    private function equalColumns(array $columns, int $variants): array
    {
        $sizes = [];

        foreach ($columns as $column) {
            $size = count($column);
            $sizes[$size] = ($sizes[$size] ?? 0) + 1;
        }

        $fitting = array_keys(array_filter($sizes, static fn (int $many): bool => $many === $variants));

        if ($fitting === []) {
            return [];
        }

        $wanted = max($fitting);

        return array_values(array_filter($columns, static fn (array $column): bool => count($column) === $wanted));
    }

    /**
     * @param  array<string, string>  $options
     * @return array<int, string>
     */
    private function correctTexts(array $options, string $marked): array
    {
        $texts = [];

        foreach (preg_split('//u', $marked, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $letter) {
            $upper = mb_strtoupper($letter);

            if (isset($options[$upper])) {
                $texts[] = $options[$upper];
            }
        }

        sort($texts);

        return $texts;
    }

    /**
     * Răspunsul pe care îl dau toate citirile, sau nimic dacă nu se potrivesc.
     *
     * @param  array<int, array{variant: string, question: array<string, mixed>, correct: array<int, string>}>  $views
     * @return array<int, string>|null
     */
    private function agreement(array $views): ?array
    {
        if (count($views) < 2) {
            return null;
        }

        $first = $views[0]['correct'];

        if ($first === []) {
            return null;
        }

        foreach ($views as $view) {
            if ($view['correct'] !== $first) {
                return null;
            }
        }

        return $first;
    }

    /**
     * @param  array<int, array{variant: string, question: array<string, mixed>, correct: array<int, string>}>  $views
     */
    private function disagreement(array $views): string
    {
        $lines = [];

        foreach ($views as $view) {
            $lines[] = $view['variant'].': '.implode(' | ', array_map(
                static fn (string $text): string => mb_substr($text, 0, 40),
                $view['correct'],
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * Pozițiile răspunsurilor corecte în ordinea din caietul citit.
     *
     * @param  array<string, string>  $options
     * @param  array<int, string>  $correct
     * @return array<int, int>
     */
    private function indexes(array $options, array $correct): array
    {
        $indexes = [];
        $position = 0;

        foreach ($options as $text) {
            $position++;

            if (in_array($text, $correct, true)) {
                $indexes[] = $position;
            }
        }

        return $indexes;
    }

    /**
     * Ce face din două citiri aceeași întrebare.
     *
     * Nu enunțul singur: un caiet are mai multe întrebări care încep cu
     * „Selectați afirmațiile adevărate:”, iar luate drept una singură ar intra
     * în conflict între ele și s-ar pierde toate. Enunțul plus mulțimea
     * răspunsurilor, în schimb, e același în toate variantele, oricum le-a
     * amestecat comisia.
     *
     * @param  array{number: int, prompt: string, options: array<string, string>}  $question
     */
    private function identity(array $question): string
    {
        $options = array_values($question['options']);
        sort($options);

        return sha1($question['prompt']."\n".implode("\n", $options));
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function tidy(array $parts): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)));
    }

    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    private function refuse(string $reason): array
    {
        return ['total' => 0, 'questions' => [], 'rejected' => [[
            'code' => 'sesiune',
            'reason' => $reason,
            'text' => '',
        ]]];
    }
}
