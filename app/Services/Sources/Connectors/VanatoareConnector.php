<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\SingleDocumentSource;
use App\Services\Sources\Parsers\HuntingLicenceParser;
use RuntimeException;

/**
 * Ministerul Mediului — setul de întrebări pentru examenul de vânător.
 *
 * O mie de întrebări cu câte trei variante, singura sursă din catalog unde
 * răspunsul corect e marcat prin scrierea îngroșată și prin nimic altceva. De
 * aceea documentul trece prin `antiword`, nu printr-o extragere de text
 * obișnuită: fără formatare, întrebările ar veni fără răspunsuri.
 *
 * Documentul poartă în antet referințe la Ordinul 302/2014 și mențiunea
 * „valabil din 25 februarie 2016”, dar pagina ministerului îl publică drept
 * actualizat conform Legii nr. 171/2022. Versiunea pe care o afișăm e cea de
 * pe pagină, fiindcă aceea e ce învață candidatul; discordanța rămâne scrisă
 * în notele sursei, ca să nu fie descoperită din nou peste un an.
 */
final class VanatoareConnector implements SingleDocumentSource
{
    private const SOURCE_KEY_PREFIX = 'mmap-vanator';

    public function __construct(
        private readonly HuntingLicenceParser $parser,
        private readonly PracticeTestBuilder $tests,
    ) {}

    public function key(): string
    {
        return 'vanatoare-permis';
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'Ministerul Mediului, Apelor și Pădurilor',
            'title' => 'Set de întrebări și răspunsuri privind examenul de vânător',
            'exam' => 'Permis de vânător',
            'specialty' => null,
            'vertical_slug' => 'vanatoare',
            'taxonomy_slug' => 'permis-de-vanator',
            'source_page_url' => 'https://mmediu.ro/domenii/mediu/management-cinegetic/permis-de-vanatoare/',
            'document_url' => 'https://www.mmediu.ro/app/webroot/uploads/files/Set%20de%20%C3%AEntreb%C4%83ri%20%C8%99i%20r%C4%83spunsuri%20privind%20examenul%20de%20v%C3%A2n%C4%83tor%20actualizate%20conform%20Legii%20nr.%20171%20din%202022.doc',
            'license_url' => null,
            'version_label' => 'Legea 171/2022',
            'file_format' => 'doc',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'embedded',
            'explanations' => 'none',
            'import_difficulty' => 'mixed',
            'count_status' => 'counted_exact',
            'notes' => 'Răspunsul corect e marcat exclusiv prin scriere îngroșată, deci importul are '
                .'nevoie de `antiword`. Antetul documentului trimite la Ordinul 302/2014 și la data '
                .'de 25 februarie 2016, în timp ce pagina ministerului îl publică drept actualizat '
                .'conform Legii nr. 171/2022 — discordanța e a sursei, nu a importului. Cincizeci de '
                .'întrebări se sprijină pe imagini pe care documentul nu le dă separat.',
        ];
    }

    public function prepare(Source $source): void
    {
        $parent = $source->taxonomyNode;

        if ($parent === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }

        foreach (HuntingLicenceParser::chapters() as $index => $chapter) {
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
                'seo_title' => $chapter['name'].' — întrebări examen de vânător | e-test.ro',
                'seo_description' => 'Întrebări oficiale din capitolul „'.$chapter['name']
                    .'” al setului publicat de Ministerul Mediului pentru examenul de vânător.',
                'metadata' => array_replace($node->metadata ?? [], [
                    'catalog' => [
                        'authority' => 'Ministerul Mediului, Apelor și Pădurilor',
                        'source_url' => $this->definition()['document_url'],
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
        $label = 'Ministerul Mediului — set de întrebări pentru examenul de vânător, '
            .'actualizat conform Legii nr. 171/2022';
        $rows = [];

        foreach ($questions as $question) {
            $prompt = (string) $question['prompt'];
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => $index + 1 === (int) $question['correct'],
                ];
            }

            $rows[] = [
                // Documentul nu numerotează întrebările într-un fel care să
                // supraviețuiască extragerii, iar poziția se schimbă la prima
                // adăugare din capitol. Rămâne amprenta textului — a enunțului
                // împreună cu variantele, fiindcă douăsprezece enunțuri se
                // repetă în document cu variante diferite, iar o amprentă doar
                // pe enunț ar face paisprezece întrebări să dispară una peste
                // alta.
                'source_key' => self::SOURCE_KEY_PREFIX.':'.$this->fingerprint($prompt, (array) $question['options']),
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $prompt,
                'taxonomy_slug' => $question['chapter_slug'],
                'source_label' => $label,
                'source_url' => $source->document_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'chapter' => $question['chapter_slug'],
                    'legal_basis' => 'Legea nr. 171/2022',
                ],
            ];
        }

        return $rows;
    }

    /**
     * Un test pe capitol, plus unul care acoperă tot setul.
     *
     * Examenul are o structură publicată în regulament, dar documentul de față
     * conține doar întrebările, deci nu construim o simulare cu reguli
     * inventate.
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
                'Întrebări oficiale din capitolul „'.$chapter->name.'” al setului pentru examenul de vânător.',
                20,
            );

            $built += $test === null ? 0 : 1;
        }

        $all = $this->tests->build(
            $parent,
            'toate-capitolele',
            'Examenul de vânător — test de exercițiu din toate capitolele',
            'Întrebări din tot setul oficial: biologia speciilor, legislație, etică, chinologie, boli, arme și organizarea vânătorii.',
            40,
            includeChildren: true,
        );

        return $built + ($all === null ? 0 : 1);
    }

    /**
     * Textul adus la o formă pe care spațiile, punctuația și diacriticele
     * scrise altfel nu o schimbă.
     *
     * @param  array<int, mixed>  $options
     */
    private function fingerprint(string $prompt, array $options): string
    {
        $parts = [$prompt, ...array_map(strval(...), $options)];
        $normalised = array_map(function (string $part): string {
            $folded = strtr(mb_strtolower($part, 'UTF-8'), [
                'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ş' => 's', 'ș' => 's', 'ţ' => 't', 'ț' => 't',
            ]);

            return trim((string) preg_replace('/[^a-z0-9]+/u', ' ', $folded));
        }, $parts);

        return substr(sha1(implode('|', $normalised)), 0, 16);
    }
}
