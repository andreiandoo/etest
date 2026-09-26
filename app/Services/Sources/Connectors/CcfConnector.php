<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\MultiDocumentSource;
use App\Services\Sources\Parsers\CcfGrilaParser;
use App\Services\Sources\PdfTextExtractor;
use RuntimeException;

/**
 * Camera Consultanților Fiscali — chestionarele de examen, sesiune cu sesiune.
 *
 * Camera dă două examene diferite, consultant fiscal și consultant fiscal
 * asistent, iar în catalog sunt două secțiuni surori. O sursă se leagă de o
 * singură secțiune și publică ce e sub ea, deci sunt doi conectori, nu unul:
 * fiecare își ia din aceeași listă doar sesiunile examenului lui. Codul e
 * comun, cheia și examenul sunt ale fiecăruia.
 *
 * Sesiunile din 2016–2019 lipsesc din listă: acolo chestionarul se încheie cu o
 * fișă de răspuns cu buline înnegrite, iar răspunsurile nu sunt scrise nicăieri
 * ca text.
 */
abstract class CcfConnector implements MultiDocumentSource
{
    public function __construct(
        private readonly CcfGrilaParser $parser,
        private readonly PdfTextExtractor $pdf,
        private readonly PracticeTestBuilder $tests,
    ) {}

    /**
     * Secțiunea din catalog căreia îi aparțin sesiunile acestui conector.
     */
    abstract protected function exam(): string;

    /**
     * Numele examenului, așa cum intră în titlul unui test.
     */
    abstract protected function examName(): string;

    /**
     * Sesiunile examenului, din fișierul de date.
     *
     * @return array<string, array{name: string, document: string}>
     */
    public function sessions(): array
    {
        $path = database_path('data/ccf-sesiuni.csv');

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
                if (count($row) < 4 || trim((string) $row[0]) !== $this->exam()) {
                    continue;
                }

                $sessions[trim((string) $row[1])] = [
                    'name' => trim((string) $row[2]),
                    'document' => trim((string) $row[3]),
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
            'authority' => 'CCF — Camera Consultanților Fiscali',
            'title' => 'Chestionare de examen cu răspunsurile corecte, '.$this->examName(),
            'exam' => 'Examen de atribuire a calității de '.$this->examName(),
            'specialty' => 'Cod fiscal, cod de procedură fiscală, TVA, impozite',
            'vertical_slug' => 'fiscal',
            'taxonomy_slug' => $this->exam(),
            'source_page_url' => 'https://www.ccfiscali.ro/arhiva-chestionare/',
            'document_url' => 'https://www.ccfiscali.ro/arhiva-chestionare/',
            'license_url' => null,
            'version_label' => 'sesiunile 2020–2025',
            'file_format' => 'pdf',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'inline',
            'explanations' => 'none',
            'import_difficulty' => 'high_automation',
            'count_status' => 'counted_verified',
            'notes' => 'Răspunsul corect e tipărit sub fiecare întrebare, deci nu e nevoie de barem. '
                .'Documentul se taie la marcajul răspunsului, nu la numerotare: din 2023 Camera nu mai '
                .'numerotează întrebările, iar în acel an marcajul e scris în engleză. Sesiunile '
                .'2016–2019 nu sunt în listă, fiindcă acolo răspunsurile stau pe o fișă cu buline.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documents(): array
    {
        $documents = [];

        foreach ($this->sessions() as $slug => $session) {
            $documents[$slug.'-chestionar.pdf'] = $session['document'];
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

        foreach ($this->sessions() as $slug => $session) {
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
                'seo_title' => 'Grile '.$this->examName().' — '.mb_strtolower($session['name']).' | e-test.ro',
                'seo_description' => 'Chestionarul de la examenul de '.$this->examName().', '
                    .mb_strtolower($session['name']).', cu răspunsurile corecte publicate de Camera '
                    .'Consultanților Fiscali.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'CCF — Camera Consultanților Fiscali',
                        'source_url' => $session['document'],
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

        foreach ($this->sessions() as $slug => $session) {
            $path = $paths[$slug.'-chestionar.pdf'] ?? null;

            if ($path === null) {
                $rejected[] = [
                    'code' => $session['name'],
                    'reason' => 'Lipsește chestionarul sesiunii.',
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
        $sessions = $this->sessions();
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

            $rows[] = [
                'source_key' => $this->key().':'.$session.':'
                    .substr(sha1($prompt."\n".implode("\n", (array) $question['options'])), 0, 12),
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $prompt,
                'explanation' => '',
                'taxonomy_slug' => $session,
                'taxonomy_parent_slug' => $this->exam(),
                'source_label' => 'CCF — chestionar de examen '.$this->examName().', '
                    .($sessions[$session]['name'] ?? $session),
                'source_url' => $sessions[$session]['document'] ?? $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'session' => $session,
                    'exam' => $this->exam(),
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe sesiune, plus unul care le amestecă.
     *
     * Legislația fiscală se schimbă în fiecare an, iar o întrebare din 2020
     * poate avea azi alt răspuns — de asta sesiunea rămâne vizibilă, ca cineva
     * să știe pe ce an se pregătește.
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
                $this->exam().'-'.$session->slug,
                $this->examName().' — '.mb_strtolower($session->name),
                'Chestionarul dat la examenul de '.$this->examName().', '.mb_strtolower($session->name)
                    .': patruzeci de întrebări cu patru variante și un singur răspuns corect.',
                40,
            );

            $built += $test === null ? 0 : 1;
        }

        $mixed = $this->tests->build(
            $parent,
            $this->exam().'-simulare',
            'Simulare examen '.$this->examName(),
            'Patruzeci de întrebări trase din toate sesiunile publicate de Camera Consultanților Fiscali, '
                .'în formatul examenului: patru variante, un singur răspuns corect.',
            40,
            includeChildren: true,
        );

        return $built + ($mixed === null ? 0 : 1);
    }
}
