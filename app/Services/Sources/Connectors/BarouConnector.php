<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Enums\TestMode;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\LocalDocumentSource;
use App\Services\Sources\Parsers\BarouParser;
use App\Services\Sources\PdfTextExtractor;
use RuntimeException;

/**
 * INPPA / UNBR — examenul de primire în profesia de avocat, aprilie 2026.
 *
 * Opt grile a câte o sută de întrebări: patru pentru stagiari, patru pentru
 * definitivi, fiecare cu cinci materii a douăzeci de întrebări. Opt sute de
 * întrebări dintr-o singură sesiune, cea mai mare sursă din catalog după
 * CNCAN.
 *
 * Fișierele stau în depozit, nu se descarcă: INPPA și UNBR răspund 403 la
 * orice cerere care nu vine dintr-un browser. Au fost aduse o dată, de mână, și
 * de acolo le citește conducta ca pe orice altă sursă, cu amprentă și istoric.
 *
 * Răspunsul e publicat de două ori — scris sub întrebare și desenat în grila de
 * corectură — iar o întrebare intră doar dacă ambele spun același lucru.
 */
final class BarouConnector implements LocalDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'barou-2026-04';

    private const SESSION = 'aprilie 2026';

    /**
     * @var array<string, array{slug: string, name: string, label: string}>
     */
    private const CATEGORIES = [
        'stagiari' => ['slug' => 'stagiari', 'name' => 'Avocat stagiar', 'label' => 'stagiari'],
        'definitivi' => ['slug' => 'definitivi', 'name' => 'Avocat definitiv', 'label' => 'definitivi'],
    ];

    /**
     * Proba, așa cum o publică INPPA: o sută de întrebări în patru ore.
     */
    private const EXAM_SECONDS = 4 * 3600;

    public function __construct(
        private readonly BarouParser $parser,
        private readonly PdfTextExtractor $pdf,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'barou-primire-profesie';
    }

    public function directory(): string
    {
        return 'docs/barou-2026';
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'INPPA / UNBR',
            'title' => 'Subiecte și bareme, proba scrisă tip grilă, examenul de primire în profesia de avocat',
            'exam' => 'Primire în profesia de avocat',
            'specialty' => null,
            'vertical_slug' => 'drept',
            'taxonomy_slug' => 'barou',
            'source_page_url' => 'https://inppa.ro/examene/',
            'document_url' => 'https://inppa.ro/examene/',
            'license_url' => null,
            'version_label' => 'Sesiunea '.self::SESSION,
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'embedded',
            'explanations' => 'none',
            'import_difficulty' => 'mixed',
            'count_status' => 'counted_exact',
            'notes' => 'Fișierele nu se pot descărca automat: INPPA și UNBR răspund 403 la orice '
                .'cerere care nu vine dintr-un browser. Stau aduse de mână în docs/barou-2026. '
                .'Citirea are nevoie de `pdftotext` cu păstrarea așezării din pagină, fiindcă în '
                .'ordinea brută a textului răspunsul apare înaintea variantelor lui. Răspunsul e '
                .'publicat de două ori, iar o întrebare intră doar dacă ambele surse coincid.',
        ];
    }

    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        foreach (self::CATEGORIES as $category) {
            $node = $this->node($parent->vertical_id, $parent->id, $category['slug'], $category['name'], 0, $parent->is_active);

            foreach (BarouParser::subjects() as $index => $subject) {
                $this->node(
                    $parent->vertical_id,
                    $node->id,
                    $subject['slug'],
                    $subject['name'],
                    $index,
                    $node->is_active,
                    $category['name'],
                );
            }
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

        foreach (self::CATEGORIES as $key => $category) {
            for ($grila = 1; $grila <= 4; $grila++) {
                $subjects = $this->find($paths, $key.'/subiecte/', $grila);
                $barem = $this->find($paths, $key.'/barem/', $grila);

                if ($subjects === null || $barem === null) {
                    $rejected[] = [
                        'code' => $category['name'].', grila '.$grila,
                        'reason' => 'Lipsește fișierul cu subiecte sau cel cu baremul.',
                        'text' => '',
                    ];

                    continue;
                }

                $key0 = $this->parser->answerKey($this->pdf->extractLayout($barem));
                $parsed = $this->parser->parse($this->pdf->extractLayout($subjects), $key0);
                $total += $parsed['total'];

                foreach ($parsed['rejected'] as $row) {
                    $rejected[] = [
                        'code' => $category['name'].', grila '.$grila.' — '.$row['code'],
                        'reason' => $row['reason'],
                        'text' => $row['text'],
                    ];
                }

                foreach ($parsed['questions'] as $question) {
                    $questions[] = [...$question, 'category' => $key, 'grila' => $grila];
                }
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
        $rows = [];

        foreach ($questions as $question) {
            $correct = (array) $question['correct'];
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => in_array($index + 1, $correct, true),
                ];
            }

            $category = (string) $question['category'];

            $rows[] = [
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$category.':g'.$question['grila'].':'.$question['number'],
                'type' => QuestionType::MultipleChoice->value,
                'prompt' => $question['prompt'],
                'taxonomy_slug' => $question['subject_slug'],
                'source_label' => 'INPPA — examen de primire în profesia de avocat, '
                    .self::CATEGORIES[$category]['label'].', sesiunea '.self::SESSION
                    .', grila '.$question['grila'],
                'source_url' => $source->source_page_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'session' => self::SESSION,
                    'category' => $category,
                    'grila' => $question['grila'],
                    'exam_number' => $question['number'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe materie și o simulare pe categorie.
     *
     * Simularea nu inventează nimic: o sută de întrebări în patru ore e
     * formatul scris chiar pe prima pagină a fiecărei grile.
     */
    public function buildTests(Source $source): int
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            return 0;
        }

        $built = 0;

        foreach (self::CATEGORIES as $category) {
            $node = TaxonomyNode::query()
                ->where('parent_id', $parent->id)
                ->where('slug', $category['slug'])
                ->first();

            if ($node === null) {
                continue;
            }

            foreach (TaxonomyNode::query()->where('parent_id', $node->id)->orderBy('sort_order')->get() as $subject) {
                $test = $this->tests->build(
                    $subject,
                    $category['slug'].'-'.$subject->slug,
                    $subject->name.' — '.mb_strtolower($category['name']),
                    'Întrebări de '.mb_strtolower($subject->name).' din grilele examenului de primire în '
                        .'profesia de avocat, sesiunea '.self::SESSION.'.',
                    20,
                );

                $built += $test === null ? 0 : 1;
            }

            $simulation = $this->tests->build(
                $node,
                $category['slug'].'-simulare',
                'Simulare examen de Barou — '.mb_strtolower($category['name']),
                'O sută de întrebări în patru ore, exact formatul probei scrise tip grilă, din grilele '
                    .'sesiunii '.self::SESSION.'.',
                100,
                includeChildren: true,
                mode: TestMode::Exam,
                durationSeconds: self::EXAM_SECONDS,
            );

            $built += $simulation === null ? 0 : 1;
        }

        return $built;
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function find(array $paths, string $prefix, int $grila): ?string
    {
        foreach ($paths as $relative => $absolute) {
            $normalised = str_replace('\\', '/', $relative);

            if (str_starts_with($normalised, $prefix) && preg_match('/_G'.$grila.'\.pdf$/i', $normalised) === 1) {
                return $absolute;
            }
        }

        return null;
    }

    private function node(
        int $verticalId,
        int $parentId,
        string $slug,
        string $name,
        int $sort,
        bool $active,
        ?string $category = null,
    ): TaxonomyNode {
        $node = TaxonomyNode::query()->firstOrNew([
            'vertical_id' => $verticalId,
            'parent_id' => $parentId,
            'slug' => $slug,
        ]);

        $isNew = ! $node->exists;
        $suffix = $category === null ? '' : ' — '.mb_strtolower($category);

        $node->fill([
            'type' => $category === null ? TaxonomyNodeType::Exam : TaxonomyNodeType::Subject,
            'name' => $name,
            'sort_order' => $sort,
            'seo_title' => $name.$suffix.' — grile examen Barou | e-test.ro',
            'seo_description' => 'Întrebări din grilele oficiale ale examenului de primire în profesia '
                .'de avocat, sesiunea '.self::SESSION.$suffix.'.',
            'metadata' => array_replace($node->metadata ?? [], [
                'catalog' => [
                    'authority' => 'INPPA / UNBR',
                    'source_url' => 'https://inppa.ro/examene/',
                    'rights' => 'official_public_unclear',
                ],
            ]),
        ]);

        if ($isNew) {
            $node->is_active = $active;
        }

        $node->save();

        return $node;
    }
}
