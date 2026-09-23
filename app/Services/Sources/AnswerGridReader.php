<?php

namespace App\Services\Sources;

/**
 * Citește baremul dintr-o grilă de corectură în care răspunsul e o imagine.
 *
 * OAMGMAMR nu publică baremul ca text. Publică un tabel în care fiecare rând e
 * o imagine de 70×17 pixeli cu trei căsuțe, una înnegrită. `pdftotext` vede
 * numerele întrebărilor și antetul „a b c”, dar nu vede niciun răspuns.
 *
 * Aici se citesc trei lucruri din PDF: unde stă fiecare număr de întrebare,
 * unde stă fiecare imagine, și care treime a imaginii e întunecată. Din ele
 * iese baremul, determinist, fără OCR și fără ghicit.
 *
 * Litera nu se deduce din numele obiectului — `/I1`, `/I2`, `/I3` — ci din
 * pixeli. Numele sunt o convenție a programului care a generat fișierul, iar
 * convenția se poate schimba de la o sesiune la alta fără ca nimeni să anunțe;
 * treimea întunecată nu.
 */
final class AnswerGridReader
{
    /**
     * Cât de departe pe verticală poate sta eticheta față de imaginea ei.
     */
    private const ROW_TOLERANCE = 5.0;

    /**
     * Cât de departe pe orizontală, la dreapta etichetei, poate sta imaginea.
     */
    private const ROW_WIDTH = 90.0;

    /**
     * Poziționare absolută (`Td`), poziționare prin matrice (`Tm`) sau un
     * vector de text (`TJ`), în ordinea în care apar în flux.
     */
    private const TEXT = '/([\d.-]+)\s+([\d.-]+)\s+Td'
        .'|[\d.-]+\s+[\d.-]+\s+[\d.-]+\s+[\d.-]+\s+([\d.-]+)\s+([\d.-]+)\s+Tm'
        .'|\[([^\[\]]*)\]\s*TJ/s';

    private const FRAGMENT = '/\(((?:[^()\\\\]|\\\\.)*)\)/s';

    /**
     * @return array{answers: array<int, string>, problems: array<int, string>}
     */
    public function read(string $path): array
    {
        $raw = (string) file_get_contents($path);
        $content = $this->contentStream($raw);

        if ($content === null) {
            return ['answers' => [], 'problems' => ['Nu am găsit fluxul de conținut al paginii.']];
        }

        $labels = $this->labels($content);
        $marks = $this->marks($content);

        if ($labels === [] || $marks === []) {
            return ['answers' => [], 'problems' => [
                'Grila nu conține nici numere, nici marcaje care să poată fi citite — probabil e scanată.',
            ]];
        }

        $letters = [];
        $answers = [];
        $problems = [];

        foreach ($marks as $mark) {
            $letters[$mark['name']] ??= $this->letterOf($raw, $content, $mark['name']);
            $letter = $letters[$mark['name']];

            if ($letter === null) {
                continue;
            }

            $number = $this->numberFor($labels, $mark);

            if ($number === null) {
                $problems[] = 'Un marcaj de la '.round($mark['x']).'/'.round($mark['y']).' nu are număr alături.';

                continue;
            }

            if (isset($answers[$number])) {
                $problems[] = 'Întrebarea '.$number.' are mai multe marcaje.';

                continue;
            }

            $answers[$number] = $letter;
        }

        ksort($answers);

        return ['answers' => $answers, 'problems' => $problems];
    }

    /**
     * Fluxul care descrie pagina, adică cel cu cele mai multe desenări de
     * imagine. Celelalte fluxuri din fișier sunt fonturi și bitmap-uri.
     */
    private function contentStream(string $raw): ?string
    {
        preg_match_all('/stream\r?\n/', $raw, $matches, PREG_OFFSET_CAPTURE);

        $best = null;
        $bestCount = 0;

        foreach ($matches[0] as $match) {
            $start = $match[1] + strlen($match[0]);
            $end = strpos($raw, 'endstream', $start);

            if ($end === false) {
                continue;
            }

            $inflated = @gzuncompress(substr($raw, $start, $end - $start));

            if ($inflated === false) {
                continue;
            }

            $count = substr_count($inflated, ' Do');

            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $inflated;
            }
        }

        return $bestCount > 0 ? $best : null;
    }

    /**
     * Numerele de întrebare, cu poziția lor.
     *
     * Textul e așezat fie cu `Td`, fie cu `Tm`, după programul care a generat
     * fișierul, iar conținutul e uneori pe doi octeți per caracter. Se merge
     * prin flux în ordine, ținând minte ultima poziție, fiindcă un singur bloc
     * de text poate conține zeci de bucăți așezate fiecare la locul ei.
     *
     * @return array<int, array{x: float, y: float, number: int}>
     */
    private function labels(string $content): array
    {
        preg_match_all(self::TEXT, $content, $matches, PREG_SET_ORDER);

        $labels = [];
        $x = null;
        $y = null;

        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                $x = (float) $match[1];
                $y = (float) $match[2];

                continue;
            }

            if (($match[3] ?? '') !== '') {
                $x = (float) $match[3];
                $y = (float) $match[4];

                continue;
            }

            if ($x === null || $y === null) {
                continue;
            }

            // Un vector de text poate fi tăiat în bucăți, cu numere de spațiere
            // între ele: `[(5)-3(\))]`. Ne interesează literele, nu spațierea.
            preg_match_all(self::FRAGMENT, $match[5] ?? '', $pieces);
            $text = str_replace([chr(0), chr(92), ' '], '', implode('', $pieces[1]));

            if (preg_match('/^(\d{1,3})\)$/', $text, $number) === 1) {
                $labels[] = ['x' => $x, 'y' => $y, 'number' => (int) $number[1]];
            }
        }

        return $labels;
    }

    /**
     * Imaginile desenate în pagină. Înălțimea poate fi negativă, la fișierele
     * cu axa verticală întoarsă; ne interesează doar unde ajunge imaginea.
     *
     * @return array<int, array{x: float, y: float, name: string}>
     */
    private function marks(string $content): array
    {
        preg_match_all(
            '/q\s+[\d.-]+\s+0\s+0\s+[\d.-]+\s+([\d.-]+)\s+([\d.-]+)\s+cm[^Q]{0,40}?\/(\w+)\s+Do/s',
            $content,
            $matches,
            PREG_SET_ORDER,
        );

        $marks = [];

        foreach ($matches as $match) {
            $marks[] = ['x' => (float) $match[1], 'y' => (float) $match[2], 'name' => $match[3]];
        }

        return $marks;
    }

    /**
     * @param  array<int, array{x: float, y: float, number: int}>  $labels
     * @param  array{x: float, y: float, name: string}  $mark
     */
    private function numberFor(array $labels, array $mark): ?int
    {
        $found = null;

        foreach ($labels as $label) {
            $distance = $mark['x'] - $label['x'];

            if (abs($label['y'] - $mark['y']) > self::ROW_TOLERANCE || $distance <= 0 || $distance > self::ROW_WIDTH) {
                continue;
            }

            if ($found !== null) {
                return null;
            }

            $found = $label['number'];
        }

        return $found;
    }

    /**
     * Care treime a imaginii e cea mai întunecată: a, b sau c.
     */
    private function letterOf(string $raw, string $content, string $name): ?string
    {
        $image = $this->image($raw, $content, $name);

        if ($image === null) {
            return null;
        }

        [$width, $height, $pixels] = $image;
        $thirds = [0, 0, 0];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($pixels($x, $y) < 128) {
                    $thirds[min(2, intdiv($x * 3, $width))]++;
                }
            }
        }

        $ranked = $thirds;
        rsort($ranked);

        // O căsuță înnegrită lasă treimea ei de cel puțin două ori mai
        // întunecată decât următoarea. Ce nu trece pragul — sigla instituției,
        // de pildă — nu e un răspuns și nu se ghicește.
        if ($ranked[0] === 0 || $ranked[0] < 2 * $ranked[1]) {
            return null;
        }

        return ['a', 'b', 'c'][(int) array_search($ranked[0], $thirds, true)];
    }

    /**
     * @return array{0: int, 1: int, 2: callable(int, int): int}|null
     */
    private function image(string $raw, string $content, string $name): ?array
    {
        if (preg_match('/\/'.preg_quote($name, '/').'\s+(\d+)\s+0\s+R/', $raw, $reference) !== 1) {
            return null;
        }

        $start = $this->objectOffset($raw, (int) $reference[1]);

        if ($start === null) {
            return null;
        }

        $streamAt = strpos($raw, 'stream', $start);

        if ($streamAt === false) {
            return null;
        }

        $header = substr($raw, $start, $streamAt - $start);

        if (preg_match('/\/Width\s+(\d+)/', $header, $w) !== 1 || preg_match('/\/Height\s+(\d+)/', $header, $h) !== 1) {
            return null;
        }

        $width = (int) $w[1];
        $height = (int) $h[1];
        $body = $this->streamBody($raw, $streamAt);

        if (str_contains($header, '/DCTDecode')) {
            return $this->fromJpeg($body, $width, $height);
        }

        $pixels = @gzuncompress($body);

        if ($pixels === false || strlen($pixels) < $width * $height * 3) {
            return null;
        }

        return [$width, $height, static function (int $x, int $y) use ($pixels, $width): int {
            $offset = ($y * $width + $x) * 3;

            return intdiv(ord($pixels[$offset]) + ord($pixels[$offset + 1]) + ord($pixels[$offset + 2]), 3);
        }];
    }

    /**
     * @return array{0: int, 1: int, 2: callable(int, int): int}|null
     */
    private function fromJpeg(string $body, int $width, int $height): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($body);

        if ($image === false) {
            return null;
        }

        return [$width, $height, static function (int $x, int $y) use ($image): int {
            $colour = (int) imagecolorat($image, $x, $y);

            return intdiv((($colour >> 16) & 0xFF) + (($colour >> 8) & 0xFF) + ($colour & 0xFF), 3);
        }];
    }

    private function objectOffset(string $raw, int $number): ?int
    {
        if (preg_match('/(?<![\d])'.$number.'\s+0\s+obj/', $raw, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return $match[0][1];
    }

    private function streamBody(string $raw, int $streamAt): string
    {
        $start = $streamAt + strlen('stream');

        while ($start < strlen($raw) && ($raw[$start] === "\r" || $raw[$start] === "\n")) {
            $start++;
        }

        $end = strpos($raw, 'endstream', $start);

        return $end === false ? '' : substr($raw, $start, $end - $start);
    }
}
