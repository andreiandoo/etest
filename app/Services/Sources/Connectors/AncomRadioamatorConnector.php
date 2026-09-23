<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\Question;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\SingleDocumentSource;
use App\Services\Sources\Parsers\AncomRadioParser;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * ANCOM — lista de subiecte pentru proba de electronică și radiotehnică.
 *
 * Prima sursă importată, și nu întâmplător: disclaimerul ANCOM permite explicit
 * reproducerea conținutului cu indicarea sursei, fără modificarea sensului. E
 * singura din tot catalogul cu permisiunea scrisă negru pe alb, deci e locul
 * potrivit pentru a rula conducta întreagă înainte de a o duce pe surse unde
 * drepturile trebuie întâi lămurite.
 *
 * Versiunea documentului — 2017-02-09 — rămâne lipită de fiecare întrebare,
 * fiindcă pagina ANCOM încă trimite la el, dar o listă de subiecte tehnice se
 * actualizează periodic, iar candidatul are dreptul să știe pe ce ediție
 * învață.
 */
final class AncomRadioamatorConnector implements SingleDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'ancom-radio';

    public function __construct(
        private readonly AncomRadioParser $parser,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'ancom-radioamator';
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'ANCOM',
            'title' => 'Listă de subiecte pentru proba de electronică și radiotehnică',
            'exam' => 'Certificat de radioamator, clasele a III-a și a II-a',
            'specialty' => 'Radiotehnică și electronică',
            'vertical_slug' => 'radio',
            'taxonomy_slug' => 'radiotehnica-si-electronica',
            'source_page_url' => 'https://ancom.ro/category/autorizare-ro/radioamatori/',
            'document_url' => 'https://www.ancom.ro/wp-content/uploads/2010/07/subiecte_radiotehnica_09_02_2017.pdf',
            'license_url' => 'https://www.ancom.ro/uncategorized-ro/disclaimer/',
            'version_label' => '2017-02-09',
            'file_format' => 'pdf',
            'rights_status' => 'explicit_allowed',
            'answer_key' => 'embedded',
            'explanations' => 'none',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_exact',
            'notes' => 'Reproducerea e permisă cu indicarea sursei ANCOM, fără modificarea sensului '
                .'și fără a prezenta materialul ca provenind din altă sursă. Eticheta de sursă și '
                .'versiunea rămân pe fiecare întrebare.',
        ];
    }

    /**
     * Capitolele documentului devin secțiuni, ca întrebările să nu ajungă
     * toate într-o grămadă de 640 și ca fiecare capitol să aibă pagina lui.
     */
    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        foreach (AncomRadioParser::chapters() as $index => $chapter) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $chapter['slug'],
            ]);

            $isNew = ! $node->exists;

            $node->fill([
                'type' => TaxonomyNodeType::Chapter,
                'name' => $chapter['name'],
                'sort_order' => $index,
                'seo_title' => $chapter['name'].' — întrebări examen radioamator | e-test.ro',
                'seo_description' => 'Întrebări oficiale ANCOM din capitolul „'.$chapter['name']
                    .'”, pentru proba de electronică și radiotehnică.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'ANCOM',
                        'source_url' => $this->definition()['document_url'],
                        'rights' => 'explicit_allowed',
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
        $label = 'ANCOM — listă de subiecte radiotehnică, versiunea '.$source->version_label;
        $rows = [];

        foreach ($questions as $question) {
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => $index + 1 === (int) $question['correct'],
                ];
            }

            $rows[] = [
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$question['code'],
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $question['prompt'],
                'difficulty' => $question['difficulty'],
                'taxonomy_slug' => $question['chapter_slug'],
                'source_label' => $label,
                'source_url' => $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'ancom_code' => $question['code'],
                    'difficulty_letter' => $question['difficulty_letter'],
                    'variant_group' => $question['group'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe capitol, plus câte unul pe clasă de certificat.
     *
     * Împărțirea pe clase nu e o invenție: documentul spune explicit că pentru
     * clasa a III-a sunt valabile doar subiectele cu gradele de dificultate A
     * și B. Un candidat la clasa a III-a care exersează pe tot fondul ar
     * învăța lucruri care nu i se cer.
     *
     * Testele sunt de exercițiu, nu simulări de examen: documentul publică
     * subiectele, nu regulile probei — numărul de întrebări și timpul — iar
     * acelea nu se inventează.
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
                'Întrebări oficiale ANCOM din capitolul „'.$chapter->name.'”, cu răspunsul corect afișat după fiecare încercare.',
                20,
            );

            $built += $test === null ? 0 : 1;
        }

        foreach ([
            ['clasa-a-iii-a', 'Radioamator clasa a III-a — test de exercițiu', 'Doar subiectele cu gradele de dificultate A și B, singurele valabile pentru clasa a III-a.', 30, [1, 2]],
            ['clasa-a-ii-a', 'Radioamator clasa a II-a — test de exercițiu', 'Tot fondul de subiecte de electronică și radiotehnică publicat de ANCOM.', 40, null],
        ] as [$slug, $title, $description, $limit, $difficulties]) {
            $test = $this->tests->build(
                $parent,
                $slug,
                $title,
                $description,
                $limit,
                includeChildren: true,
                filter: $difficulties === null ? null : $this->difficultyFilter($difficulties),
            );

            $built += $test === null ? 0 : 1;
        }

        return $built;
    }

    /**
     * @param  array<int, int>  $difficulties
     * @return callable(Builder<Question>): void
     */
    private function difficultyFilter(array $difficulties): callable
    {
        return static function (Builder $query) use ($difficulties): void {
            $query->whereIn('difficulty', $difficulties);
        };
    }
}
