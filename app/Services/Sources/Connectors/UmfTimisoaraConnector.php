<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Enums\TestMode;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\MultiDocumentSource;
use App\Services\Sources\Parsers\UmfAdmitereParser;
use App\Services\Sources\PdfTextExtractor;
use RuntimeException;
use ZipArchive;

/**
 * UMF „Victor Babeș” Timișoara — admiterea la licență.
 *
 * Dintre universitățile de medicină, Timișoara e singura care publică și
 * caietele de concurs și baremul, sesiune după sesiune, de mai mulți ani. UMF
 * Carol Davila publică doar grilele corecte — o fișă de răspuns cu bulinele
 * completate, fără întrebări — deci de acolo nu se poate lua nimic.
 *
 * Sunt două probe: tip I, pentru Medicină și Medicină Dentară, și tip II, pentru
 * asistență medicală, farmacie, balneofizioterapie și celelalte licențe.
 * Materia e anatomia și fiziologia din manualul de clasa a XI-a, plus chimie la
 * tip I — adică fondul comun al oricărei admiteri la medicină din România, nu
 * doar al celei de la Timișoara.
 *
 * Caietele vin uneori ca un singur PDF cu toate variantele, uneori ca o arhivă
 * cu câte un PDF pe variantă. Amândouă se citesc la fel: varianta o spune
 * antetul paginii, nu numele fișierului.
 *
 * Lista de sesiuni nu e completă și nici nu poate fi. Sesiunile din septembrie
 * 2024 și 2025 publică un caiet fără variante marcate și un barem pe o singură
 * coloană, deci răspunsurile n-ar avea cu ce să fie verificate; iar caietele de
 * la tip I iulie 2025 sunt scanate, fără text. Acelea lipsesc dinadins.
 */
final class UmfTimisoaraConnector implements MultiDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'umft';

    public function __construct(
        private readonly UmfAdmitereParser $parser,
        private readonly PdfTextExtractor $pdf,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'umf-timisoara-admitere';
    }

    /**
     * Sesiunile, din fișierul de date.
     *
     * @return array<string, array{name: string, subjects: string, answers: string}>
     */
    public static function sessions(): array
    {
        $path = database_path('data/umf-timisoara-sesiuni.csv');

        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $sessions = [];

        try {
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) {
                    continue;
                }

                $sessions[trim((string) $row[0])] = [
                    'name' => trim((string) $row[1]),
                    'subjects' => trim((string) $row[2]),
                    'answers' => trim((string) $row[3]),
                ];
            }
        } finally {
            fclose($handle);
        }

        return $sessions;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'UMF „Victor Babeș” Timișoara',
            'title' => 'Caiete de concurs și bareme de corectură, admitere licență',
            'exam' => 'Concurs de admitere licență, probele tip I și tip II',
            'specialty' => 'Biologie — anatomia și fiziologia omului, și chimie',
            'vertical_slug' => 'medicina',
            'taxonomy_slug' => 'umf-timisoara',
            'source_page_url' => 'https://www.umft.ro/ro/admitere-ciclul-licenta-2026/',
            'document_url' => 'https://www.umft.ro/ro/admitere-ciclul-licenta-2026/',
            'license_url' => null,
            'version_label' => 'sesiunile 2023–2026',
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'separate',
            'explanations' => 'none',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_verified',
            'notes' => 'Fiecare sesiune are patru variante cu aceleași întrebări amestecate, deci '
                .'răspunsul corect se citește de patru ori și se publică numai dacă toate citirile dau '
                .'același text. Sesiunile cu o singură variantă publicată, sau cu caietul scanat, nu '
                .'sunt în listă: acolo nu ar fi nimic de verificat.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documents(): array
    {
        $documents = [];

        foreach (self::sessions() as $slug => $session) {
            foreach ($this->subjectUrls($session['subjects']) as $index => $url) {
                $documents[$slug.'-subiecte-'.($index + 1).'.'.$this->extension($url)] = $url;
            }

            $documents[$slug.'-barem.pdf'] = $session['answers'];
        }

        return $documents;
    }

    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        $index = 0;

        foreach (self::sessions() as $slug => $session) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $slug,
            ]);

            $isNew = ! $node->exists;

            $node->fill([
                'type' => TaxonomyNodeType::Chapter,
                'name' => $session['name'],
                'sort_order' => $index++,
                'seo_title' => 'Subiecte admitere UMF Timișoara — '.mb_strtolower($session['name']).' | e-test.ro',
                'seo_description' => 'Grilele de la admiterea la UMF „Victor Babeș” Timișoara, '
                    .mb_strtolower($session['name']).', cu răspunsurile din baremul oficial.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'UMF „Victor Babeș” Timișoara',
                        'source_url' => $this->subjectUrls($session['subjects'])[0] ?? $session['answers'],
                        'rights' => 'official_public_unclear',
                    ],
                ]),
            ]);

            if ($isNew) {
                $node->is_active = $parent->is_active;
            }

            $node->save();
        }
    }

    /**
     * @param  array<string, string>  $paths
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parseDocuments(array $paths): array
    {
        $questions = [];
        $rejected = [];
        $total = 0;

        foreach (self::sessions() as $slug => $session) {
            $answers = $paths[$slug.'-barem.pdf'] ?? null;
            $subjects = [];

            foreach (array_keys($this->subjectUrls($session['subjects'])) as $index) {
                foreach ($paths as $name => $path) {
                    if (str_starts_with($name, $slug.'-subiecte-'.($index + 1).'.')) {
                        $subjects = [...$subjects, ...$this->documentPdfs($path)];
                    }
                }
            }

            if ($answers === null || $subjects === []) {
                $rejected[] = [
                    'code' => $session['name'],
                    'reason' => 'Lipsește caietul de concurs sau baremul.',
                    'text' => '',
                ];

                continue;
            }

            $parsed = $this->parser->parse(
                array_map(fn (string $path): string => $this->pdf->extractLayout($path), $subjects),
                $this->pdf->extractLayout($answers),
            );

            $total += $parsed['total'];

            foreach ($parsed['rejected'] as $row) {
                $rejected[] = [
                    'code' => $session['name'].' — '.$row['code'],
                    'reason' => $row['reason'],
                    'text' => $row['text'],
                ];
            }

            foreach ($parsed['questions'] as $question) {
                $questions[] = [...$question, 'session' => $slug];
            }
        }

        return ['total' => $total, 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function rows(Source $source, array $questions): array
    {
        $sessions = self::sessions();
        $rows = [];

        foreach ($questions as $question) {
            $correct = array_map('intval', (array) $question['correct']);
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => in_array($index + 1, $correct, true),
                ];
            }

            $session = (string) $question['session'];
            $prompt = (string) $question['prompt'];

            $rows[] = [
                // Cheia se face din enunț, nu din numărul din caiet: numărul
                // diferă de la o variantă la alta, iar la o resincronizare am
                // ajunge cu aceeași întrebare de două ori.
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$session.':'.substr(sha1($prompt), 0, 12),
                // Întotdeauna răspuns multiplu, chiar când e corect unul
                // singur: la concurs candidatul nu știe câte sunt, iar un
                // câmp care lasă o singură bifă i-ar spune.
                'type' => QuestionType::MultipleChoice->value,
                'prompt' => $prompt,
                'explanation' => '',
                'taxonomy_slug' => $session,
                'source_label' => 'UMF „Victor Babeș” Timișoara — caiet de concurs, '
                    .($sessions[$session]['name'] ?? $session).', cu baremul oficial de corectură',
                'source_url' => $this->subjectUrls($sessions[$session]['subjects'] ?? '')[0] ?? $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'session' => $session,
                    'exam_number' => $question['number'],
                    'verified_variants' => $question['variants'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe sesiune, plus unul care le amestecă pe toate.
     *
     * Sesiunea e felul în care candidatul caută — „subiecte admitere 2025” —
     * iar testul amestecat e cel cu care se pregătește după ce le-a făcut pe
     * toate o dată.
     */
    public function buildTests(Source $source): int
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            return 0;
        }

        $built = 0;

        foreach (TaxonomyNode::query()->where('parent_id', $parent->id)->orderBy('sort_order')->get() as $session) {
            $test = $this->tests->build(
                $session,
                'umf-timisoara-'.$session->slug,
                'Admitere UMF Timișoara — '.$session->name,
                'Grilele date la admiterea la UMF „Victor Babeș” Timișoara, '.$session->name
                    .', cu răspunsurile din baremul oficial de corectură.',
                50,
            );

            $built += $test === null ? 0 : 1;
        }

        $mixed = $this->tests->build(
            $parent,
            'umf-timisoara-admitere-simulare',
            'Simulare admitere UMF Timișoara',
            'Cincizeci de întrebări trase din toate sesiunile de admitere publicate de UMF '
                .'„Victor Babeș” Timișoara, în formatul probei: cinci variante, una sau mai multe corecte.',
            50,
            includeChildren: true,
            mode: TestMode::Exam,
            durationSeconds: 3 * 3600,
        );

        return $built + ($mixed === null ? 0 : 1);
    }

    /**
     * Un caiet poate fi un PDF sau o arhivă cu câte un PDF pe variantă.
     *
     * @return array<int, string>
     */
    private function documentPdfs(string $path): array
    {
        if (! str_ends_with(mb_strtolower($path), '.zip')) {
            return [$path];
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Nu am putut deschide arhiva '.basename($path).'.');
        }

        $directory = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME);
        $pdfs = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);

                if (! str_ends_with(mb_strtolower($name), '.pdf')) {
                    continue;
                }

                $contents = $zip->getFromIndex($index);

                if ($contents === false) {
                    continue;
                }

                $target = $directory.'/'.basename($name);

                if (! is_dir($directory) && ! mkdir($directory, 0o775, true) && ! is_dir($directory)) {
                    throw new RuntimeException('Nu am putut scrie fișierele din arhiva '.basename($path).'.');
                }

                file_put_contents($target, $contents);
                $pdfs[] = $target;
            }
        } finally {
            $zip->close();
        }

        sort($pdfs);

        return $pdfs;
    }

    /**
     * @return array<int, string>
     */
    private function subjectUrls(string $column): array
    {
        return array_values(array_filter(array_map('trim', explode('|', $column))));
    }

    private function extension(string $url): string
    {
        return str_ends_with(mb_strtolower($url), '.zip') ? 'zip' : 'pdf';
    }
}
