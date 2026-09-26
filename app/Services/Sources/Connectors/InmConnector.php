<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Enums\TestMode;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\MultiDocumentSource;
use App\Services\Sources\Parsers\InmGrilaParser;
use App\Services\Sources\PdfTextExtractor;
use RuntimeException;

/**
 * INM — testul-grilă de la admiterea în magistratură.
 *
 * Prima probă a concursului, eliminatorie: o sută de întrebări în cel mult patru
 * ore, douăzeci și cinci din fiecare materie, trei variante de răspuns, una
 * corectă. Institutul publică grila nr. 1 cu răspunsul tipărit sub fiecare
 * întrebare, sesiune după sesiune, din 2022 încoace.
 *
 * Întrebările stau pe materie, nu pe sesiune. Un candidat la magistratură nu
 * învață „sesiunea din aprilie 2024”, învață drept procesual penal; iar așezate
 * pe materie, întrebările care se repetă de la o sesiune la alta intră o singură
 * dată, fiindcă cheia lor nu conține sesiunea. Aceleași două concursuri —
 * admiterea la INM și admiterea în magistratură — dau în unii ani exact același
 * test, iar așa nu se dublează nimic.
 *
 * Sesiunile din 10 septembrie 2023 lipsesc: acolo caietul s-a publicat fără
 * răspunsuri, iar baremul de alături e o fișă scanată cu buline înnegrite.
 */
final class InmConnector implements MultiDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'inm';

    /**
     * Materiile, în ordinea din test.
     *
     * @var array<string, string>
     */
    private const DISCIPLINES = [
        'drept-civil' => 'Drept civil',
        'drept-procesual-civil' => 'Drept procesual civil',
        'drept-penal' => 'Drept penal',
        'drept-procesual-penal' => 'Drept procesual penal',
    ];

    public function __construct(
        private readonly InmGrilaParser $parser,
        private readonly PdfTextExtractor $pdf,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'inm-test-grila';
    }

    /**
     * Sesiunile, din fișierul de date.
     *
     * @return array<string, array{name: string, document: string}>
     */
    public static function sessions(): array
    {
        $path = database_path('data/inm-sesiuni.csv');

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
                if (count($row) < 3) {
                    continue;
                }

                $sessions[trim((string) $row[0])] = [
                    'name' => trim((string) $row[1]),
                    'document' => trim((string) $row[2]),
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
            'authority' => 'INM — Institutul Național al Magistraturii',
            'title' => 'Testul-grilă de verificare a cunoștințelor juridice, grila nr. 1',
            'exam' => 'Concursul de admitere la INM și de admitere în magistratură',
            'specialty' => 'Drept civil, procesual civil, penal și procesual penal',
            'vertical_slug' => 'drept',
            'taxonomy_slug' => 'inm',
            'source_page_url' => 'https://inm-lex.ro/subiecte-admitere-la-inm/',
            'document_url' => 'https://inm-lex.ro/subiecte-admitere-la-inm/',
            'license_url' => null,
            'version_label' => 'sesiunile 2022–2025',
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'inline',
            'explanations' => 'none',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_verified',
            'notes' => 'Răspunsul corect e tipărit în chiar caietul de concurs, sub fiecare întrebare, '
                .'deci nu e nevoie de barem. Întrebările se așează pe materie, nu pe sesiune, iar cheia '
                .'lor nu conține sesiunea: testul comun celor două concursuri dintr-un an intră o '
                .'singură dată. Grilele fără răspuns tipărit nu sunt în listă.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documents(): array
    {
        $documents = [];

        foreach (self::sessions() as $slug => $session) {
            $documents[$slug.'-grila.pdf'] = $session['document'];
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

        foreach (self::DISCIPLINES as $slug => $name) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $slug,
            ]);

            $isNew = ! $node->exists;

            $node->fill([
                'type' => TaxonomyNodeType::Subject,
                'name' => $name,
                'sort_order' => $index++,
                'seo_title' => 'Grile INM '.mb_strtolower($name).' — admitere în magistratură | e-test.ro',
                'seo_description' => 'Întrebările de '.mb_strtolower($name).' date la testul-grilă de la '
                    .'admiterea la INM și în magistratură, cu răspunsul din caietul oficial de concurs.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'INM — Institutul Național al Magistraturii',
                        'source_url' => 'https://inm-lex.ro/subiecte-admitere-la-inm/',
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
            $path = $paths[$slug.'-grila.pdf'] ?? null;

            if ($path === null) {
                $rejected[] = [
                    'code' => $session['name'],
                    'reason' => 'Lipsește caietul de concurs.',
                    'text' => '',
                ];

                continue;
            }

            $parsed = $this->parser->parse($this->pdf->extractLayout($path));
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
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => $index + 1 === (int) $question['correct'],
                ];
            }

            $session = (string) $question['session'];
            $prompt = (string) $question['prompt'];
            $discipline = (string) $question['discipline_slug'];

            $rows[] = [
                // Fără sesiune în cheie: o întrebare pusă în două sesiuni e tot
                // o singură întrebare, iar cele două concursuri dintr-un an dau
                // uneori exact același test.
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$discipline.':'
                    .substr(sha1($prompt."\n".implode("\n", (array) $question['options'])), 0, 12),
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $prompt,
                'explanation' => '',
                'taxonomy_slug' => $discipline,
                'taxonomy_parent_slug' => 'inm',
                'source_label' => 'INM — testul-grilă de la admiterea în magistratură, '
                    .($sessions[$session]['name'] ?? $session).', grila nr. 1',
                'source_url' => $sessions[$session]['document'] ?? $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'session' => $session,
                    'discipline' => $discipline,
                    'discipline_name' => $question['discipline_name'],
                    'exam_number' => $question['number'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe materie, plus simularea probei întregi.
     *
     * Regulamentul dă cel mult patru ore pentru cele o sută de întrebări, iar
     * proba se trece cu 60 de puncte din 100 — de asta simularea are ceas.
     */
    public function buildTests(Source $source): int
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            return 0;
        }

        $built = 0;

        foreach (TaxonomyNode::query()->where('parent_id', $parent->id)->orderBy('sort_order')->get() as $discipline) {
            $test = $this->tests->build(
                $discipline,
                'inm-'.$discipline->slug,
                'Grile INM — '.$discipline->name,
                'Întrebările de '.mb_strtolower($discipline->name).' date la testul-grilă de la admiterea '
                    .'la INM și în magistratură, cu răspunsul din caietul oficial de concurs.',
                25,
            );

            $built += $test === null ? 0 : 1;
        }

        $simulation = $this->tests->build(
            $parent,
            'inm-test-grila-simulare',
            'Simulare test-grilă INM — 100 de întrebări',
            'Proba eliminatorie de la admiterea în magistratură, în formatul ei: o sută de întrebări din '
                .'cele patru materii, în patru ore, cu trei variante de răspuns și una corectă.',
            100,
            includeChildren: true,
            mode: TestMode::Exam,
            durationSeconds: 4 * 3600,
        );

        return $built + ($simulation === null ? 0 : 1);
    }
}
