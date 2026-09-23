<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\SingleDocumentSource;
use App\Services\Sources\Parsers\AnfpTestParser;
use RuntimeException;

/**
 * ANFP — bateria de teste pentru concursul național de ocupare a funcțiilor publice.
 *
 * Douăzeci și trei de întrebări, nu o bancă. Documentul se numește chiar
 * „baterii de teste, cu titlu exemplificativ”, iar asta e tot ce publică ANFP
 * ca material de antrenament. Puțin, dar curat: e singura sursă de până acum
 * care dă și motivul, nu doar răspunsul.
 *
 * Fiecare răspuns corect vine cu articolul de lege pe care se sprijină. Îl
 * scoatem din varianta de răspuns — altfel candidatul ar căuta paranteza, nu
 * răspunsul — și îl punem în explicația întrebării.
 */
final class AnfpConnector implements SingleDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'anfp-teste';

    public function __construct(
        private readonly AnfpTestParser $parser,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'anfp-concurs-national';
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'ANFP — Agenția Națională a Funcționarilor Publici',
            'title' => 'Baterii de teste exemplificative pentru proba de testare preliminară',
            'exam' => 'Concursul național pentru ocuparea funcțiilor publice',
            'specialty' => null,
            'vertical_slug' => 'administratie',
            'taxonomy_slug' => 'concurs-national',
            'source_page_url' => 'https://concurs-national.anfp.gov.ro/materiale-utile/teste-antrenament/',
            'document_url' => 'https://concurs-national.anfp.gov.ro/wp-content/uploads/Baterie-teste-adm-exemplificative.pdf',
            'license_url' => null,
            'version_label' => 'Baterie exemplificativă',
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'embedded',
            'explanations' => 'full',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_exact',
            'notes' => 'Documentul spune că răspunsurile corecte sunt îngroșate, dar tot ele sunt și '
                .'singurele care citează un articol de lege, în toate cele douăzeci și trei de '
                .'întrebări. Citarea se mută din varianta de răspuns în explicație: lăsată acolo, ar '
                .'face întrebarea trivială. E tot ce publică ANFP ca material de antrenament.',
        ];
    }

    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        foreach (AnfpTestParser::chapters() as $index => $chapter) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $chapter['slug'],
            ]);

            $isNew = ! $node->exists;

            $node->fill([
                'type' => TaxonomyNodeType::Subject,
                'name' => $chapter['name'],
                'sort_order' => $index,
                'seo_title' => $chapter['name'].' — teste concurs național ANFP | e-test.ro',
                'seo_description' => 'Întrebări din bateria ANFP pentru proba de testare preliminară, '
                    .'capitolul „'.$chapter['name'].'”, cu temeiul legal al fiecărui răspuns.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'ANFP',
                        'source_url' => 'https://concurs-national.anfp.gov.ro/materiale-utile/teste-antrenament/',
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
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text): array
    {
        return $this->parser->parse($text);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function rows(Source $source, array $questions): array
    {
        $rows = [];

        foreach ($questions as $question) {
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => $index + 1 === (int) $question['correct'],
                ];
            }

            $reference = (string) $question['reference'];

            $rows[] = [
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$question['chapter_slug'].':'.$question['number'],
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $question['prompt'],
                'explanation' => 'Temei legal: '.$reference.'.',
                'taxonomy_slug' => $question['chapter_slug'],
                'source_label' => 'ANFP — baterie de teste exemplificative, proba de testare preliminară',
                'source_url' => $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'legal_reference' => $reference,
                    'chapter' => $question['chapter_slug'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe capitol și unul din tot materialul.
     *
     * Documentul e mic, deci testul general chiar are rost: douăzeci și trei
     * de întrebări sunt o singură ședință de exersare, nu un fond din care se
     * extrage.
     */
    public function buildTests(Source $source): int
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            return 0;
        }

        $built = 0;

        foreach (TaxonomyNode::query()->where('parent_id', $parent->id)->orderBy('sort_order')->get() as $chapter) {
            $test = $this->tests->build(
                $chapter,
                $chapter->slug.'-exercitii',
                $chapter->name.' — test de exercițiu',
                'Întrebări din bateria ANFP pentru capitolul „'.$chapter->name
                    .'”, fiecare cu articolul de lege pe care se sprijină răspunsul.',
                20,
            );

            $built += $test === null ? 0 : 1;
        }

        $all = $this->tests->build(
            $parent,
            'testare-preliminara',
            'Proba de testare preliminară — test de exercițiu',
            'Toate întrebările publicate de ANFP ca material de antrenament pentru proba de testare '
                .'preliminară a concursului național.',
            25,
            includeChildren: true,
        );

        return $built + ($all === null ? 0 : 1);
    }
}
