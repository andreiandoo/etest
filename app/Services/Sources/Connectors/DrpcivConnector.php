<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Enums\TestMode;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Content\RemoteImageStore;
use App\Services\Sources\Contracts\LocalDocumentSource;
use App\Services\Sources\Parsers\DrpcivCsvParser;
use RuntimeException;
use Throwable;

/**
 * Chestionarele DRPCIV, aduse din fișiere CSV puse în depozit.
 *
 * Fiecare fișier ține o categorie de permis, spusă de numele lui:
 * `DRPCIV_qa_B.csv` e categoria B. Câte un conector pe categorie, fiindcă o
 * sursă publică subarborele secțiunii ei, iar categoriile de permis sunt
 * secțiuni surori, nu una sub alta. Se adaugă câte o clasă de patru rânduri pe
 * măsură ce apar fișiere noi.
 *
 * Întrebările se așază pe capitole — prioritate de trecere, indicatoare și
 * marcaje, noțiuni de mecanică — citite din adresa categoriei, ultimul segment.
 * Capitolele se repetă între categoriile de permis, deci fiecare întrebare
 * spune și sub ce categorie intră capitolul ei.
 *
 * Imaginile se aduc la noi pe disc la prima sincronizare și de acolo se
 * servesc. O întrebare a cărei imagine nu poate fi adusă e respinsă, nu
 * publicată ciungă: un desen de intersecție nu se poate răspunde din text.
 */
abstract class DrpcivConnector implements LocalDocumentSource
{
    /**
     * Numele capitolelor, scrise cum se cuvine.
     *
     * Adresa dă doar forma fără diacritice, iar „Notiuni de mecanica” pe un
     * titlu de capitol arată a import, nu a produs. Ce nu e în listă se scrie
     * din adresă și se vede la următoarea trecere.
     *
     * @var array<string, string>
     */
    private const CHAPTERS = [
        'circulatia-pe-autostrazi' => 'Circulația pe autostrăzi',
        'conducerea-ecologica' => 'Conducerea ecologică',
        'conducerea-preventiva' => 'Conducerea preventivă',
        'depasirea' => 'Depășirea',
        'indicatoare-si-marcaje' => 'Indicatoare și marcaje',
        'masuri-de-prim-ajutor' => 'Măsuri de prim ajutor',
        'notiuni-de-mecanica' => 'Noțiuni de mecanică',
        'obligatiile-conducatorilor-de-autovehicule' => 'Obligațiile conducătorilor de autovehicule',
        'oprirea-stationarea-si-parcarea' => 'Oprirea, staționarea și parcarea',
        'pozitia-in-timpul-mersului-si-semnalele-conducatorilor-de-autovehicule' => 'Poziția în timpul mersului și semnalele conducătorilor',
        'prioritate-de-trecere' => 'Prioritate de trecere',
        'reguli-generale' => 'Reguli generale',
        'reguli-referitoare-la-manevre' => 'Reguli referitoare la manevre',
        'sanctiuni-si-contraventii' => 'Sancțiuni și contravenții',
        'semnale-luminoase' => 'Semnale luminoase',
        'semnalele-politistilor' => 'Semnalele polițiștilor',
        'trecerea-la-nivel-cu-calea-ferata' => 'Trecerea la nivel cu calea ferată',
        'viteza-si-distanta-intre-vehicule' => 'Viteza și distanța între vehicule',
    ];

    public function __construct(
        private readonly DrpcivCsvParser $parser,
        private readonly RemoteImageStore $images,
        private readonly PracticeTestBuilder $tests,
    ) {}

    /**
     * Litera categoriei, așa cum se termină numele fișierului.
     */
    abstract protected function category(): string;

    /**
     * Cum se numește proba în titluri: „categoria B”, dar „redobândire”.
     */
    protected function label(): string
    {
        return 'categoria '.mb_strtoupper($this->category());
    }

    public function key(): string
    {
        return 'drpciv-categoria-'.mb_strtolower($this->category());
    }

    public function directory(): string
    {
        return 'docs/auto';
    }

    /**
     * Secțiunea din catalog: `permis-categoria-b` pentru fișierul categoriei B.
     */
    protected function taxonomySlug(): string
    {
        return 'permis-categoria-'.mb_strtolower($this->category());
    }

    protected function fileName(): string
    {
        return 'DRPCIV_qa_'.$this->category().'.csv';
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'DRPCIV — Direcția Regim Permise de Conducere și Înmatriculare a Vehiculelor',
            'title' => 'Chestionare de legislație rutieră, '.$this->label(),
            'exam' => 'Proba teoretică pentru permisul de conducere, '.$this->label(),
            'specialty' => 'Legislație rutieră, conduită preventivă, mecanică, prim ajutor',
            'vertical_slug' => 'auto',
            'taxonomy_slug' => $this->taxonomySlug(),
            'source_page_url' => 'https://dgpci.mai.gov.ro/',
            'document_url' => 'https://dgpci.mai.gov.ro/',
            'license_url' => null,
            'version_label' => 'chestionar '.$this->label(),
            'file_format' => 'csv',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'embedded',
            'explanations' => 'full',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_exact',
            'notes' => 'Fișierele stau în depozit, unul pe categorie de permis. Explicația fiecărei '
                .'întrebări e textul articolului de lege pe care se sprijină răspunsul. Imaginile se aduc '
                .'la prima sincronizare și se servesc de la noi; o întrebare a cărei imagine nu poate fi '
                .'adusă se raportează, nu se publică fără ea.',
        ];
    }

    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        $path = base_path($this->directory().'/'.$this->fileName());

        if (! is_file($path)) {
            return;
        }

        $index = 0;

        foreach ($this->chaptersIn($path) as $slug) {
            $node = TaxonomyNode::query()->firstOrNew([
                'vertical_id' => $parent->vertical_id,
                'parent_id' => $parent->id,
                'slug' => $slug,
            ]);

            $isNew = ! $node->exists;
            $name = $this->chapterName($slug);

            $node->fill([
                'type' => TaxonomyNodeType::Chapter,
                'name' => $name,
                'sort_order' => $index++,
                'seo_title' => $name.' — chestionare auto '.$this->label().' | e-test.ro',
                'seo_description' => 'Întrebări de '.mb_strtolower($name).' din chestionarele pentru permisul '
                    .'de conducere, '.$this->label().', cu articolul de lege la fiecare răspuns.',
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
        $path = $paths[$this->fileName()] ?? null;

        if ($path === null) {
            return ['total' => 0, 'questions' => [], 'rejected' => [[
                'code' => $this->fileName(),
                'reason' => 'Fișierul categoriei nu e în folderul '.$this->directory().'.',
                'text' => '',
            ]]];
        }

        $parsed = $this->parser->parse($path);
        $questions = [];
        $rejected = $parsed['rejected'];

        foreach ($parsed['questions'] as $question) {
            $url = $question['image'] ?? null;

            if ($url === null) {
                $questions[] = [...$question, 'media' => null];

                continue;
            }

            try {
                $stored = $this->images->fetch((string) $url, 'questions/auto/'.mb_strtolower($this->category()));
            } catch (Throwable $exception) {
                $rejected[] = [
                    'code' => mb_substr((string) $question['prompt'], 0, 70),
                    'reason' => 'Imaginea nu a putut fi adusă: '.$exception->getMessage(),
                    'text' => (string) $url,
                ];

                continue;
            }

            $questions[] = [...$question, 'media' => [
                'path' => $stored['path'],
                // Nimeni nu a scris un text alternativ pentru imaginile astea,
                // iar unul inventat ar minți cititorul de ecran. Rămâne neutru
                // și însemnat, ca să se poată lua la rând.
                'alt' => 'Imaginea întrebării',
                'license' => 'material oficial de examinare',
            ]];
        }

        return ['total' => $parsed['total'], 'questions' => $questions, 'rejected' => $rejected];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function rows(Source $source, array $questions): array
    {
        $category = mb_strtolower($this->category());
        $rows = [];

        foreach ($questions as $question) {
            $prompt = (string) $question['prompt'];
            $options = (array) $question['options'];
            $media = $question['media'] ?? null;
            $chapter = $question['chapter'] ?? null;

            $rows[] = [
                'source_key' => 'drpciv:'.$category.':'
                    .substr(sha1($prompt."\n".implode("\n", array_column($options, 'content'))), 0, 12),
                // Una, două sau toate trei variantele pot fi corecte, iar
                // candidatul nu știe câte sunt: câmpul nu trebuie să-i spună.
                'type' => QuestionType::MultipleChoice->value,
                'prompt' => $prompt,
                'explanation' => $question['explanation'],
                'media' => $media,
                // Unde nu există capitole, întrebarea stă direct sub categoria
                // de permis.
                'taxonomy_slug' => $chapter ?? $this->taxonomySlug(),
                'taxonomy_parent_slug' => $chapter === null ? '' : $this->taxonomySlug(),
                'source_label' => 'DRPCIV — chestionar oficial de legislație rutieră, '.$this->label(),
                'source_url' => $source->source_page_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => array_filter([
                    'category' => $category,
                    'chapter' => $chapter,
                    'origin' => $question['origin'] ?? null,
                    'needs_alt' => $media === null ? null : true,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            ];
        }

        return $rows;
    }

    /**
     * Un test pe capitol, plus simularea probei.
     *
     * Simularea nu își alege singură cifrele: numărul de întrebări, timpul și
     * pragul vin din regulile categoriei, scrise în catalog după Ordinul
     * 268/2010.
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
                $this->taxonomySlug().'-'.$chapter->slug,
                $chapter->name.' — '.$this->label(),
                'Întrebări de '.mb_strtolower($chapter->name).' din chestionarele pentru permisul de '
                    .'conducere, '.$this->label().', cu articolul de lege la fiecare răspuns.',
                20,
                dynamic: true,
            );

            $built += $test === null ? 0 : 1;
        }

        $exam = (array) data_get($parent->metadata, 'exam');
        $questions = (int) ($exam['questions'] ?? 0);

        if ($questions < 1) {
            return $built;
        }

        $simulation = $this->tests->build(
            $parent,
            $this->taxonomySlug().'-examen',
            $parent->name.' — simulare examen',
            'Proba teoretică pentru '.mb_strtolower($parent->name).', în formatul ei oficial: '.$questions
                .' întrebări în '.intdiv((int) ($exam['duration_seconds'] ?? 0), 60).' minute, cu '
                .($exam['passing_questions'] ?? 0).' răspunsuri corecte pentru promovare, iar chestionarul se '
                .'închide la '.($exam['max_wrong'] ?? 0).' greșeli.',
            $questions,
            includeChildren: true,
            mode: TestMode::Exam,
            durationSeconds: (int) ($exam['duration_seconds'] ?? 0) ?: null,
            passingQuestions: (int) ($exam['passing_questions'] ?? 0) ?: null,
            maxWrongAnswers: (int) ($exam['max_wrong'] ?? 0) ?: null,
            // Nu există „testul numărul 7”: există proba, iar întrebările se
            // trag altele la fiecare accesare, din tot fondul categoriei.
            dynamic: true,
        );

        return $built + ($simulation === null ? 0 : 1);
    }

    /**
     * Capitolele care apar în fișier, în ordinea primei apariții.
     *
     * @return array<int, string>
     */
    private function chaptersIn(string $path): array
    {
        $chapters = [];

        foreach ($this->parser->parse($path)['questions'] as $question) {
            $slug = $question['chapter'] ?? null;

            if ($slug !== null && ! in_array($slug, $chapters, true)) {
                $chapters[] = (string) $slug;
            }
        }

        sort($chapters);

        return $chapters;
    }

    private function chapterName(string $slug): string
    {
        if (isset(self::CHAPTERS[$slug])) {
            return self::CHAPTERS[$slug];
        }

        $words = str_replace('-', ' ', $slug);

        return mb_strtoupper(mb_substr($words, 0, 1)).mb_substr($words, 1);
    }
}
