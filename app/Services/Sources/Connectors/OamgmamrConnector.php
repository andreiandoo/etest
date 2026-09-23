<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\AnswerGridReader;
use App\Services\Sources\Contracts\MultiDocumentSource;
use App\Services\Sources\PdfTextExtractor;
use App\Services\Sources\Parsers\OamgmamrTestParser;
use RuntimeException;

/**
 * OAMGMAMR — examenul de grad principal, sesiunea 2026.
 *
 * Nouă specialități, fiecare cu un test de o sută de întrebări și cu grila lui
 * de corectură: optsprezece fișiere pentru un singur examen. Nici unul nu e de
 * ajuns singur — testul n-are răspunsuri, grila n-are întrebări.
 *
 * Grila nu publică baremul ca text, ci ca imagine: fiecare rând e o bandă de
 * 70×17 pixeli cu trei căsuțe, una înnegrită. `AnswerGridReader` o citește
 * determinist, fără OCR. Pentru specialitățile a căror grilă e scanată, baremul
 * vine dintr-un fișier verificat de om, ținut în depozit.
 *
 * O specialitate intră întreagă sau deloc: dacă numerotarea testului nu e
 * 1–100, sau dacă baremul nu acoperă exact aceleași numere, se respinge toată,
 * cu motiv. Un barem decalat cu un rând ar da o sută de răspunsuri greșite fără
 * ca nimic să pară rupt.
 */
final class OamgmamrConnector implements MultiDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'oamgmamr-2026';

    private const BASE = 'https://www.oamr.ro/wp-content/uploads/2026/09/';

    /**
     * Specialitățile sesiunii, cu fișierul de test și cel de grilă.
     *
     * Numele fișierelor nu urmează o regulă: șapte grile se cheamă
     * „Corectura_…”, două „Grila-de-corectura-…”, iar testul de stomatologie
     * are un „-1” în coadă. Se scriu una câte una, fiindcă orice regulă
     * inventată aici s-ar rupe la sesiunea următoare.
     *
     * @var array<string, array{name: string, test: string, grid: string}>
     */
    private const SPECIALTIES = [
        'asistenta-medicala-generala' => [
            'name' => 'Asistență medicală generală',
            'test' => 'Grad_principal_-_Sesiunea_2026_ASISTENTA_MEDICALA_GENERALA.pdf',
            'grid' => 'Grila-de-corectura-amg.pdf',
        ],
        'balneofizioterapie' => [
            'name' => 'Balneofizioterapie',
            'test' => 'Grad_principal_-_Sesiunea_2026_BALNEOFIZIOTERAPIE.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_BALNEOFIZIOTERAPIE.pdf',
        ],
        'farmacie' => [
            'name' => 'Farmacie',
            'test' => 'Grad_principal_-_Sesiunea_2026_FARMACIE.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_FARMACIE.pdf',
        ],
        'igiena-si-sanatate-publica' => [
            'name' => 'Igienă și sănătate publică',
            'test' => 'Grad_principal_-_Sesiunea_2026_IGIENA_SI_SANATATE_PUBLICA.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_IGIENA_SI_SANATATE_PUBLICA.pdf',
        ],
        'laborator' => [
            'name' => 'Laborator',
            'test' => 'Grad_principal_-_Sesiunea_2026_LABORATOR.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_LABORATOR.pdf',
        ],
        'moasa' => [
            'name' => 'Moașă',
            'test' => 'Grad_principal_-_Sesiunea_2026_MOASA.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_MOASA.pdf',
        ],
        'nutritie-si-dietetica' => [
            'name' => 'Nutriție și dietetică',
            'test' => 'Grad_principal_-_Sesiunea_2026_NUTRITIE_SI_DIETETICA.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_NUTRITIE_SI_DIETETICA.pdf',
        ],
        'radiologie' => [
            'name' => 'Radiologie',
            'test' => 'Grad_principal_-_Sesiunea_2026_RADIOLOGIE.pdf',
            'grid' => 'Grila-de-corectura-Radiologie.pdf',
        ],
        'stomatologie' => [
            'name' => 'Stomatologie',
            'test' => 'Grad_principal_-_Sesiunea_2026_STOMATOLOGIE-1.pdf',
            'grid' => 'Corectura_Grad_principal_-_Sesiunea_2026_STOMATOLOGIE.pdf',
        ],
    ];

    public function __construct(
        private readonly OamgmamrTestParser $parser,
        private readonly AnswerGridReader $grids,
        private readonly PdfTextExtractor $pdf,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'oamgmamr-grad-principal';
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    public static function specialties(): array
    {
        $out = [];

        foreach (self::SPECIALTIES as $slug => $specialty) {
            $out[] = ['slug' => $slug, 'name' => $specialty['name']];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'OAMGMAMR',
            'title' => 'Teste-grilă și grile de corectură, examenul de grad principal',
            'exam' => 'Grad principal asistenți medicali',
            'specialty' => null,
            'vertical_slug' => 'medicina',
            'taxonomy_slug' => 'grad-principal',
            'source_page_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
            'document_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
            'license_url' => null,
            'version_label' => 'Sesiunea 2026',
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'separate',
            'explanations' => 'none',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_exact',
            'notes' => 'Baremul e publicat ca imagine, nu ca text: fiecare rând al grilei e o bandă '
                .'cu trei căsuțe, una înnegrită, citită din pixeli. Grila de la asistență medicală '
                .'generală e scanată și nu poate fi citită automat, așa că baremul ei vine din '
                .'database/data/barem-oamgmamr-2026.csv, verificat de om. Întrebarea 76 de acolo are '
                .'în grila oficială două căsuțe înnegrite și nu a fost importată.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documents(): array
    {
        $documents = [];

        foreach (self::SPECIALTIES as $slug => $specialty) {
            $documents[$slug.'-test.pdf'] = self::BASE.$specialty['test'];
            $documents[$slug.'-grila.pdf'] = self::BASE.$specialty['grid'];
        }

        return $documents;
    }

    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        foreach (self::specialties() as $index => $specialty) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $specialty['slug'],
            ]);

            $isNew = ! $node->exists;

            $node->fill([
                'type' => TaxonomyNodeType::Subject,
                'name' => $specialty['name'],
                'sort_order' => $index,
                'seo_title' => 'Grad principal '.$specialty['name'].' — teste grilă | e-test.ro',
                'seo_description' => 'Testul oficial OAMGMAMR de grad principal, specialitatea '
                    .$specialty['name'].', sesiunea 2026, cu baremul publicat.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'OAMGMAMR',
                        'source_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
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
        $fallback = $this->fallbackKeys();
        $questions = [];
        $rejected = [];
        $total = 0;

        foreach (self::SPECIALTIES as $slug => $specialty) {
            $testPath = $paths[$slug.'-test.pdf'] ?? null;
            $gridPath = $paths[$slug.'-grila.pdf'] ?? null;

            if ($testPath === null || $gridPath === null) {
                $rejected[] = $this->reject($specialty['name'], 'Lipsește testul sau grila.');

                continue;
            }

            $parsed = $this->parser->parse($this->pdf->extract($testPath));
            $total += $parsed['total'];

            foreach ($parsed['rejected'] as $row) {
                $rejected[] = $this->reject($specialty['name'].' — întrebarea '.$row['code'], $row['reason'], $row['text']);
            }

            $numbers = array_column($parsed['questions'], 'number');

            if ($numbers !== range(1, 100)) {
                $rejected[] = $this->reject(
                    $specialty['name'],
                    'Testul nu are exact o sută de întrebări numerotate 1–100, deci nu pot lega baremul de ele.',
                );

                continue;
            }

            $answers = $this->grids->read($gridPath)['answers'];
            $source = 'grila';

            if ($answers === []) {
                $answers = $fallback[$slug] ?? [];
                $source = 'fișier verificat';
            }

            if ($answers === []) {
                $rejected[] = $this->reject(
                    $specialty['name'],
                    'Grila nu poate fi citită automat și nu există barem verificat pentru ea.',
                );

                continue;
            }

            foreach ($parsed['questions'] as $question) {
                $letter = $answers[$question['number']] ?? null;

                if ($letter === null) {
                    $rejected[] = $this->reject(
                        $specialty['name'].' — întrebarea '.$question['number'],
                        'Baremul ('.$source.') nu are răspuns pentru această întrebare.',
                        $question['prompt'],
                    );

                    continue;
                }

                $questions[] = [
                    'specialty' => $slug,
                    'number' => $question['number'],
                    'prompt' => $question['prompt'],
                    'options' => $question['options'],
                    'correct' => ['a' => 1, 'b' => 2, 'c' => 3][$letter],
                ];
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
        $label = 'OAMGMAMR — test de grad principal, sesiunea 2026';
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
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$question['specialty'].':'.$question['number'],
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $question['prompt'],
                'taxonomy_slug' => $question['specialty'],
                'source_label' => $label.', '.self::SPECIALTIES[$question['specialty']]['name'],
                'source_url' => $source->source_page_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'session' => 'Sesiunea 2026',
                    'specialty' => $question['specialty'],
                    'exam_number' => $question['number'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe specialitate, exact în formatul examenului.
     *
     * Aici formatul e cunoscut și publicat: o sută de întrebări. Nu inventăm
     * nimic, doar reproducem ce a fost dat în sesiune.
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
                $specialty->slug.'-2026',
                'Grad principal '.$specialty->name.' — sesiunea 2026',
                'Testul oficial OAMGMAMR de grad principal, specialitatea '.$specialty->name
                    .', sesiunea 2026, cu baremul publicat de Ordin.',
                100,
            );

            $built += $test === null ? 0 : 1;
        }

        return $built;
    }

    /**
     * Baremele verificate de om, pentru grilele care nu se pot citi automat.
     *
     * @return array<string, array<int, string>>
     */
    private function fallbackKeys(): array
    {
        $path = database_path('data/barem-oamgmamr-2026.csv');

        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $keys = [];

        try {
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                $letter = strtolower(trim((string) $row[2]));

                if (! in_array($letter, ['a', 'b', 'c'], true)) {
                    continue;
                }

                $keys[trim((string) $row[0])][(int) $row[1]] = $letter;
            }
        } finally {
            fclose($handle);
        }

        return $keys;
    }

    /**
     * @return array{code: string, reason: string, text: string}
     */
    private function reject(string $code, string $reason, string $text = ''): array
    {
        return ['code' => $code, 'reason' => $reason, 'text' => $text];
    }
}
