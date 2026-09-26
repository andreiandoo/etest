<?php

namespace App\Services\Sources\Parsers;

/**
 * Citește testul-grilă de la admiterea în magistratură.
 *
 * E cea mai comodă sursă din tot catalogul: institutul publică grila nr. 1 cu
 * răspunsul corect tipărit sub fiecare întrebare, „Răspuns: B”, deci nu e nevoie
 * nici de barem, nici de verificări încrucișate. Baremul publicat alături e o
 * fișă de răspuns scanată, cu bulinele înnegrite, din care nu se poate citi
 * nimic — și nici nu trebuie.
 *
 * Proba are o sută de întrebări, douăzeci și cinci din fiecare dintre cele patru
 * materii, iar materia o spune antetul fiecărei pagini. Trei variante de răspuns,
 * una corectă.
 *
 * Numerotarea merge de la 1 la 100 peste toate materiile, iar o întrebare nouă
 * începe numai la numărul care urmează. Regula asta nu e pedanterie: în caietele
 * din 2023 și 2024 rămân pe pagină rânduri care încep cu o cifră — trimiteri la
 * articole de lege rupte de rând — iar fără ea ar deveni întrebări fantomă.
 */
final class InmGrilaParser
{
    /**
     * Materiile concursului, cu numele sub care intră în taxonomie.
     *
     * Sunt fixate prin regulament, nu citite din document: dacă în antet apare
     * altceva, e semn că s-a schimbat structura probei și vreau să văd asta ca
     * rând respins, nu ca o secțiune nouă apărută de la sine.
     */
    private const DISCIPLINES = [
        'drept civil' => ['slug' => 'drept-civil', 'name' => 'Drept civil'],
        'drept procesual civil' => ['slug' => 'drept-procesual-civil', 'name' => 'Drept procesual civil'],
        'drept penal' => ['slug' => 'drept-penal', 'name' => 'Drept penal'],
        'drept procesual penal' => ['slug' => 'drept-procesual-penal', 'name' => 'Drept procesual penal'],
    ];

    /** Antetul paginii: „Drept civil - -   Admitere INM și Admitere în magistratură - …”. */
    private const HEADER = '/^\s*(.{3,40}?)\s+-\s+-\s+Admitere/u';

    private const NUMBER = '/^\s*(\d{1,3})\s+(\S.*)$/u';

    private const OPTION = '/^\s*([A-C])\.\s+(\S.*)$/u';

    /** „Răspuns: B”, îngăduitor la cum iese cuvântul din PDF. */
    private const ANSWER = '/R[^\s:]{0,8}spuns\s*:\s*([A-C])/u';

    /**
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text): array
    {
        /** @var array<int, array<string, mixed>> $found */
        $found = [];
        $discipline = null;
        $current = null;
        $target = null;

        foreach (explode("\f", $text) as $page) {
            foreach (explode("\n", $page) as $line) {
                if (preg_match(self::HEADER, $line, $head) === 1) {
                    $discipline = trim($head[1]);

                    continue;
                }

                if (str_contains($line, 'Admitere') && str_contains($line, 'Grila')) {
                    continue;
                }

                if (preg_match(self::ANSWER, $line, $mark, PREG_OFFSET_CAPTURE) === 1) {
                    if ($current !== null) {
                        $found[$current]['answer'] = $mark[1][0];
                    }

                    // Ce urmează după „Răspuns” nu mai aparține niciunei
                    // variante, iar restul rândului nici atât.
                    $line = substr($line, 0, $mark[0][1]);
                    $target = null;
                }

                if (preg_match(self::NUMBER, $line, $start) === 1
                    && $this->opensQuestion($found, $current, (int) $start[1], $discipline)) {
                    $found[] = [
                        'number' => (int) $start[1],
                        'discipline' => $discipline,
                        'prompt' => [$start[2]],
                        'options' => [],
                        'answer' => null,
                    ];
                    $current = count($found) - 1;
                    $target = 'prompt';

                    continue;
                }

                if ($current === null) {
                    continue;
                }

                if (preg_match(self::OPTION, $line, $option) === 1) {
                    $found[$current]['options'][$option[1]] = [$option[2]];
                    $target = $option[1];

                    continue;
                }

                if ($target === null || trim($line) === '') {
                    continue;
                }

                if ($target === 'prompt') {
                    $found[$current]['prompt'][] = trim($line);
                } elseif (isset($found[$current]['options'][$target])) {
                    $found[$current]['options'][$target][] = trim($line);
                }
            }
        }

        return $this->collect($found);
    }

    /**
     * O întrebare nouă începe la numărul care urmează celei dinainte, sau la 1
     * dacă am intrat în altă materie.
     *
     * @param  array<int, array<string, mixed>>  $found
     */
    private function opensQuestion(array $found, ?int $current, int $number, ?string $discipline): bool
    {
        if ($current === null) {
            return $number === 1;
        }

        $previous = $found[$current];

        return $number === (int) $previous['number'] + 1
            || ($number === 1 && $discipline !== $previous['discipline']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $found
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    private function collect(array $found): array
    {
        $questions = [];
        $rejected = [];

        foreach ($found as $question) {
            $prompt = $this->tidy((array) $question['prompt']);
            $options = [];

            foreach ((array) $question['options'] as $letter => $parts) {
                $options[$letter] = $this->tidy((array) $parts);
            }

            $discipline = $this->discipline((string) $question['discipline']);
            $answer = $question['answer'];

            $reason = match (true) {
                $question['discipline'] === null => 'Nu am văzut în antetul paginii nicio materie.',
                $discipline === null => 'Materia din antetul paginii nu e una dintre cele patru ale concursului: „'
                    .$question['discipline'].'”.',
                count($options) !== 3 => 'Am găsit '.count($options).' variante de răspuns, nu trei.',
                $answer === null => 'Întrebarea nu are răspunsul tipărit sub ea.',
                ! isset($options[(string) $answer]) => 'Litera tipărită drept răspuns nu e printre variante.',
                in_array('', $options, true) || $prompt === '' => 'Enunțul sau una dintre variante e goală.',
                default => null,
            };

            if ($reason !== null) {
                $rejected[] = [
                    'code' => 'întrebarea '.$question['number'],
                    'reason' => $reason,
                    'text' => mb_substr($prompt, 0, 200),
                ];

                continue;
            }

            $questions[] = [
                'number' => (int) $question['number'],
                'discipline_slug' => $discipline['slug'],
                'discipline_name' => $discipline['name'],
                'prompt' => $prompt,
                'options' => array_values($options),
                'correct' => (int) array_search((string) $answer, array_keys($options), true) + 1,
            ];
        }

        return ['total' => count($found), 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * @return array{slug: string, name: string}|null
     */
    private function discipline(string $header): ?array
    {
        $folded = strtr(mb_strtolower(trim($header), 'UTF-8'), [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ş' => 's', 'ș' => 's', 'ţ' => 't', 'ț' => 't',
        ]);

        $folded = trim((string) preg_replace('/\s+/u', ' ', $folded));

        return self::DISCIPLINES[$folded] ?? null;
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function tidy(array $parts): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)), " \t\n\r;");
    }
}
