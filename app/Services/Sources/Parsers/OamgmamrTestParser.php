<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește lista de întrebări a unui test de grad principal.
 *
 * Testele OAMGMAMR sunt cele mai regulate documente din tot catalogul: o sută
 * de întrebări numerotate 1–100, fiecare cu trei variante marcate `a)`, `b)`,
 * `c)`, toate pe o singură linie logică.
 *
 * Răspunsul corect nu e aici. El vine din grila de corectură, un fișier
 * separat în care marcajul e o imagine — vezi `AnswerGridReader`. Parserul
 * ăsta returnează întrebările cu numărul lor, iar conectorul le împerechează
 * cu baremul.
 */
final class OamgmamrTestParser
{
    /**
     * Numărul întrebării, cu punctul lui.
     *
     * Spațiul de după punct e opțional fiindcă nu toate bibliotecile de
     * extragere îl păstrează: una scoate „3. În prezentația”, alta „3.În
     * prezentația”. Litera de după e obligatorie, altfel „1,5 mg” sau „art.
     * 5.” din mijlocul unui enunț ar rupe împărțirea.
     */
    private const QUESTION = '/(?<=\s)(\d{1,3})\.\s*(?=\p{L})/u';

    private const OPTION = '/(?<=\s)([abc])\)\s*/u';

    /**
     * @return array{
     *     total: int,
     *     questions: array<int, array{number: int, prompt: string, options: array<int, string>}>,
     *     rejected: array<int, array{code: string, reason: string, text: string}>
     * }
     */
    public function parse(string $text): array
    {
        $body = $this->normalize($text);

        if ($body === '') {
            return ['total' => 0, 'questions' => [], 'rejected' => []];
        }

        preg_match_all(self::QUESTION, $body, $starts, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $questions = [];
        $rejected = [];
        $count = count($starts);

        for ($i = 0; $i < $count; $i++) {
            $number = (int) $starts[$i][1][0];
            $from = $starts[$i][0][1] + strlen($starts[$i][0][0]);
            $to = $i + 1 < $count ? $starts[$i + 1][0][1] : strlen($body);
            $chunk = substr($body, $from, $to - $from);

            $parsed = $this->question($number, $chunk);

            if (isset($parsed['error'])) {
                $rejected[] = [
                    'code' => (string) $number,
                    'reason' => (string) $parsed['error'],
                    'text' => trim(mb_substr($chunk, 0, 300)),
                ];

                continue;
            }

            $questions[] = $parsed;
        }

        return ['total' => $count, 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * Antetul stă o singură dată, înaintea primei întrebări, iar subsolul
     * generatorului se lipește de ultima variantă a ultimei întrebări.
     */
    private function normalize(string $text): string
    {
        $body = (string) preg_replace('/\s+/u', ' ', $text);
        $body = ' '.trim((string) preg_replace('/Powered by TCPDF \(www\.tcpdf\.org\)/u', '', $body));

        if (preg_match(self::QUESTION, $body, $first, PREG_OFFSET_CAPTURE) !== 1) {
            return '';
        }

        // Corpul păstrează un spațiu la început, ca marcajele să poată fi
        // căutate uniform, precedate de spațiu, inclusiv la prima întrebare.
        return ' '.trim(substr($body, $first[0][1]));
    }

    /**
     * @return array<string, mixed>
     */
    private function question(int $number, string $chunk): array
    {
        // Marcajele se caută precedate de spațiu, iar prima variantă stă chiar
        // la începutul bucății la întrebările fără enunț pe rând separat.
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

        if ($prompt === '' || in_array('', $options, true)) {
            return ['error' => 'Enunțul sau una dintre variante e goală.'];
        }

        return ['number' => $number, 'prompt' => $prompt, 'options' => $options];
    }
}
