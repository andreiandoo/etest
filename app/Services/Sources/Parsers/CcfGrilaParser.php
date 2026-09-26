<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește chestionarele examenului de consultant fiscal.
 *
 * Camera publică, pentru fiecare sesiune, chestionarul cu răspunsul corect
 * tipărit sub fiecare întrebare. Patruzeci de întrebări, patru variante, una
 * corectă.
 *
 * Documentul se taie la marcajul răspunsului, nu la numerotare: din 2023 Camera
 * a renunțat la numerotarea întrebărilor, iar în acel an a scris marcajul în
 * engleză, „ANSWER: A”, în loc de „RĂSPUNS CORECT: A”. Ce stă între două
 * marcaje e o întrebare, oricum ar fi numerotată și în orice limbă e scris
 * marcajul.
 *
 * Antetul documentului — numele Camerei, sesiunea, varianta — se recunoaște
 * după faptul că e scris cu majuscule, iar titlul „Chestionar și grila de
 * corectare …, noiembrie 2025” după tiparul lui. Fără asta, antetul s-ar lipi la
 * începutul primei întrebări din fiecare document.
 */
final class CcfGrilaParser
{
    private const ANSWER = '/(?:R[^\s:]{0,8}SPUNS\s+CORECT|ANSWER)\s*:\s*([A-F])/iu';

    private const OPTION = '/^\s*([A-F])\)\s+(\S.*)$/u';

    private const NUMBER = '/^\s*\d{1,3}\.\s+/u';

    private const NOISE = '/^\s*(\d{1,3}|Pagina\s+\d+.*)\s*$/iu';

    private const TITLE = '/(Chestionar|Consultant Fiscal).*(ianuarie|februarie|martie|aprilie|mai|iunie|iulie'
        .'|august|septembrie|octombrie|noiembrie|decembrie)\s+\d{4}/iu';

    /** Câte litere trebuie să aibă un rând ca să merite cântărit dacă e antet. */
    private const HEADING_LETTERS = 8;

    /** Cât de majuscul trebuie să fie un rând ca să fie antet, nu enunț. */
    private const HEADING_RATIO = 0.7;

    /**
     * Examenul are patru variante la fiecare întrebare. O întrebare din care am
     * citit trei e o întrebare din care s-a pierdut una, nu o întrebare cu trei
     * variante: se raportează, nu se publică.
     */
    private const OPTIONS_EXPECTED = 4;

    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text): array
    {
        /** @var array<int, array{lines: array<int, string>, answer: string}> $blocks */
        $blocks = [];
        $buffer = [];

        foreach (explode("\n", $text) as $line) {
            if (preg_match(self::NOISE, $line) === 1) {
                continue;
            }

            if (preg_match(self::ANSWER, $line, $mark, PREG_OFFSET_CAPTURE) === 1) {
                $buffer[] = substr($line, 0, $mark[0][1]);
                $blocks[] = ['lines' => $buffer, 'answer' => mb_strtoupper($mark[1][0])];
                $buffer = [];

                continue;
            }

            if ($this->isHeading($line)) {
                continue;
            }

            $buffer[] = $line;
        }

        $questions = [];
        $rejected = [];

        foreach ($blocks as $block) {
            $question = $this->question($block['lines'], $block['answer']);

            if (isset($question['error'])) {
                $rejected[] = [
                    'code' => mb_substr((string) $question['prompt'], 0, 70),
                    'reason' => (string) $question['error'],
                    'text' => '',
                ];

                continue;
            }

            $questions[] = $question;
        }

        return ['total' => count($blocks), 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<string, mixed>
     */
    private function question(array $lines, string $answer): array
    {
        $prompt = [];
        $options = [];
        $target = null;

        foreach ($lines as $line) {
            if (preg_match(self::OPTION, $line, $option) === 1) {
                $target = $option[1];
                $options[$target] = [$option[2]];

                continue;
            }

            if (trim($line) === '') {
                continue;
            }

            if ($target === null) {
                $prompt[] = trim((string) preg_replace(self::NUMBER, '', $line));
            } else {
                $options[$target][] = trim($line);
            }
        }

        $head = $this->tidy($prompt);
        $texts = [];

        foreach ($options as $letter => $parts) {
            $texts[$letter] = $this->tidy($parts);
        }

        $error = match (true) {
            count($texts) !== self::OPTIONS_EXPECTED => 'Am citit '.count($texts).' variante de răspuns, nu '
                .self::OPTIONS_EXPECTED.'.',
            ! isset($texts[$answer]) => 'Litera tipărită drept răspuns corect nu e printre variante.',
            $head === '' || in_array('', $texts, true) => 'Enunțul sau una dintre variante e goală.',
            default => null,
        };

        if ($error !== null) {
            return ['error' => $error, 'prompt' => $head];
        }

        return [
            'prompt' => $head,
            'options' => array_values($texts),
            'correct' => (int) array_search($answer, array_keys($texts), true) + 1,
        ];
    }

    /**
     * Un rând scris aproape tot cu majuscule e antet, nu enunț.
     */
    private function isHeading(string $line): bool
    {
        $trimmed = trim($line);

        if (mb_stripos($trimmed, 'grila de corectare') !== false || preg_match(self::TITLE, $trimmed) === 1) {
            return true;
        }

        $letters = (string) preg_replace('/[^\p{L}]+/u', '', $trimmed);
        $count = mb_strlen($letters);

        if ($count < self::HEADING_LETTERS) {
            return false;
        }

        $upper = 0;

        foreach (preg_split('//u', $letters, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (mb_strtoupper($char) === $char) {
                $upper++;
            }
        }

        return $upper / $count >= self::HEADING_RATIO;
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function tidy(array $parts): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)), " \t\n\r.;");
    }
}
