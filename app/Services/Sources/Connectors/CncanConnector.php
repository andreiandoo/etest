<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\Question;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\MultiDocumentSource;
use App\Services\Sources\Parsers\CncanParser;
use App\Services\Sources\PdfTextExtractor;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * CNCAN — permisele de exercitare în domeniul nuclear.
 *
 * Douăzeci și una de specialități, fiecare cu lista ei de întrebări și cu
 * „răspunsurile corecte (comentate)”. Peste treisprezece mii de întrebări, cu
 * cinci variante fiecare și cu o explicație scrisă de comisie la fiecare
 * răspuns — singura sursă din catalog care publică și motivul.
 *
 * Fișierele nu se numesc după nicio regulă: „RTG-Intrebari.pdf”,
 * „ListaintrebariAPV2.0-27.08.2018.pdf”, „Intrebari-CNDXCB.pdf”. Adresele
 * stau într-un fișier de date, scrise o dată, citite de aici.
 *
 * Aceleași întrebări generale de radioprotecție se repetă între specialități.
 * Nu le unificăm: un candidat se pregătește pentru o singură specialitate, iar
 * fondul lui trebuie să fie întreg, nu ciuruit de trimiteri în altă parte.
 */
final class CncanConnector implements MultiDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'cncan';

    private const VERSION = 'V2.0 27.08.2018';

    public function __construct(
        private readonly CncanParser $parser,
        private readonly PdfTextExtractor $pdf,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'cncan-permise';
    }

    /**
     * Specialitățile, din fișierul de date.
     *
     * @return array<string, array{name: string, questions: string, answers: string}>
     */
    public static function specialties(): array
    {
        $path = database_path('data/cncan-specialitati.csv');

        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $specialties = [];

        try {
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) {
                    continue;
                }

                $specialties[trim((string) $row[0])] = [
                    'name' => trim((string) $row[1]),
                    'questions' => trim((string) $row[2]),
                    'answers' => trim((string) $row[3]),
                ];
            }
        } finally {
            fclose($handle);
        }

        return $specialties;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'CNCAN',
            'title' => 'Liste de întrebări și răspunsuri comentate pentru permisele de exercitare',
            'exam' => 'Permis de exercitare nivel 1 și 2',
            'specialty' => null,
            'vertical_slug' => 'radioprotectie',
            'taxonomy_slug' => 'permise-de-exercitare',
            'source_page_url' => 'https://www.cncan.ro/surse-de-radiatii-ionizante/permise-de-exercitare/',
            'document_url' => 'https://www.cncan.ro/surse-de-radiatii-ionizante/permise-de-exercitare/',
            'license_url' => null,
            'version_label' => self::VERSION,
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'separate',
            'explanations' => 'full',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_raw_requires_dedupe',
            'notes' => 'Fiecare răspuns vine cu explicația comisiei, deci întrebările intră cu motivul '
                .'lor, nu doar cu litera corectă. Întrebările generale de radioprotecție se repetă '
                .'între specialități și rămân repetate: fondul unei specialități trebuie să fie '
                .'întreg. Documentele de întrebări sar uneori peste numere pentru care baremul are '
                .'răspuns — acelea se raportează, nu se ghicesc.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documents(): array
    {
        $documents = [];

        foreach (self::specialties() as $slug => $specialty) {
            $documents[$slug.'-intrebari.pdf'] = $specialty['questions'];
            $documents[$slug.'-raspunsuri.pdf'] = $specialty['answers'];
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

        foreach (self::specialties() as $slug => $specialty) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $slug,
            ]);

            $isNew = ! $node->exists;

            $node->fill([
                'type' => TaxonomyNodeType::Subject,
                'name' => $specialty['name'],
                'sort_order' => $index++,
                'seo_title' => 'Permis CNCAN '.$specialty['name'].' — întrebări și răspunsuri | e-test.ro',
                'seo_description' => 'Întrebările oficiale CNCAN pentru permisul de exercitare, '
                    .'specialitatea '.$specialty['name'].', cu explicația comisiei la fiecare răspuns.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'CNCAN',
                        'source_url' => $specialty['questions'],
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

        foreach (self::specialties() as $slug => $specialty) {
            $questionsPath = $paths[$slug.'-intrebari.pdf'] ?? null;
            $answersPath = $paths[$slug.'-raspunsuri.pdf'] ?? null;

            if ($questionsPath === null || $answersPath === null) {
                $rejected[] = [
                    'code' => $specialty['name'],
                    'reason' => 'Lipsește lista de întrebări sau cea de răspunsuri.',
                    'text' => '',
                ];

                continue;
            }

            $parsed = $this->parser->parse(
                $this->pdf->extractLayout($questionsPath),
                $this->pdf->extractLayout($answersPath),
            );

            $total += $parsed['total'];

            foreach ($parsed['rejected'] as $row) {
                $rejected[] = [
                    'code' => $specialty['name'].' — '.$row['code'],
                    'reason' => $row['reason'],
                    'text' => $row['text'],
                ];
            }

            foreach ($parsed['questions'] as $question) {
                $questions[] = [...$question, 'specialty' => $slug];
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
        $specialties = self::specialties();
        $rows = [];

        foreach ($questions as $question) {
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => $index + 1 === (int) $question['correct'],
                ];
            }

            $specialty = (string) $question['specialty'];

            $rows[] = [
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$specialty.':'.$question['section_slug'].':'.$question['number'],
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $question['prompt'],
                'explanation' => $question['explanation'],
                'taxonomy_slug' => $specialty,
                'taxonomy_parent_slug' => 'permise-de-exercitare',
                'source_label' => 'CNCAN — listă de întrebări pentru permisul de exercitare, '
                    .($specialties[$specialty]['name'] ?? $specialty).', '.self::VERSION,
                'source_url' => $specialties[$specialty]['questions'] ?? $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'specialty' => $specialty,
                    'section' => $question['section_slug'],
                    'section_name' => $question['section_name'],
                    'exam_number' => $question['number'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe specialitate și câte unul pe capitolul ei.
     *
     * Un fond de opt sute de întrebări nu se exersează dintr-o bucată, iar
     * capitolele sunt chiar felul în care CNCAN împarte materia.
     */
    public function buildTests(Source $source): int
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            return 0;
        }

        $built = 0;

        foreach (TaxonomyNode::query()->where('parent_id', $parent->id)->orderBy('sort_order')->get() as $specialty) {
            $test = $this->tests->build(
                $specialty,
                $specialty->slug.'-exercitii',
                $specialty->name.' — test de exercițiu',
                'Întrebări oficiale CNCAN pentru permisul de exercitare, specialitatea '
                    .mb_strtolower($specialty->name).', cu explicația comisiei la fiecare răspuns.',
                40,
            );

            $built += $test === null ? 0 : 1;

            foreach ($this->sections($specialty) as $slug => $name) {
                $chapter = $this->tests->build(
                    $specialty,
                    $specialty->slug.'-'.$slug,
                    $name.' — '.mb_strtolower($specialty->name),
                    'Întrebări din capitolul „'.$name.'” al listei CNCAN pentru specialitatea '
                        .mb_strtolower($specialty->name).'.',
                    25,
                    filter: $this->sectionFilter($slug),
                );

                $built += $chapter === null ? 0 : 1;
            }
        }

        return $built;
    }

    /**
     * Capitolele pe care le are efectiv specialitatea, citite din întrebările
     * ei — nu dintr-o listă fixă, fiindcă nu toate au aceleași capitole.
     *
     * @return array<string, string>
     */
    private function sections(TaxonomyNode $specialty): array
    {
        $sections = [];

        $rows = Question::query()
            ->where('taxonomy_node_id', $specialty->id)
            ->whereNotNull('metadata')
            ->select('metadata')
            ->get();

        foreach ($rows as $row) {
            $slug = (string) data_get($row->metadata, 'section');
            $name = (string) data_get($row->metadata, 'section_name');

            if ($slug !== '' && $name !== '') {
                $sections[$slug] = $name;
            }
        }

        return $sections;
    }

    /**
     * @return callable(Builder<Question>): void
     */
    private function sectionFilter(string $slug): callable
    {
        return static function (Builder $query) use ($slug): void {
            $query->whereJsonContains('metadata->section', $slug);
        };
    }
}
