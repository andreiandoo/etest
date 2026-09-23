<?php

namespace Database\Seeders;

use App\Models\TaxonomyNode;
use App\Models\Vertical;
use Database\Seeders\Data\Catalog;
use Illuminate\Database\Seeder;

/**
 * Catalogul real de examene.
 *
 * Spre deosebire de `DevContentSeeder`, aici nu se inventează nimic: fiecare
 * verticală și fiecare secțiune vin din documentele de cercetare și poartă
 * instituția, adresa sursei oficiale și statutul drepturilor.
 *
 * Seeder-ul e gândit să fie rulat de mai multe ori. Câmpurile descriptive se
 * suprascriu la fiecare rulare, ca o corectură în `Catalog` să ajungă în
 * producție. `is_active` nu se atinge după creare: o verticală pornește
 * inactivă, fiindcă o pagină fără conținut nu are ce căuta în index, iar
 * momentul în care devine publică e o decizie editorială, nu una de seeder.
 *
 * Rulare:  php artisan db:seed --class=CatalogSeeder
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Setul demonstrativ folosește slug-uri reale — `auto`, `drept`,
        // `medicina`. Dacă e încă în bază, catalogul ar prelua chiar acele
        // rânduri și ar moșteni marcajul `demo`, iar o curățare de mai târziu
        // ar șterge catalogul cu tot cu el. Ordinea corectă e purge, apoi seed.
        if (Vertical::query()->whereJsonContains('metadata->demo', true)->exists()) {
            $this->command?->error('Mai există conținut demonstrativ în bază.');
            $this->command?->line('Șterge-l întâi: php artisan content:purge-demo');

            return;
        }

        foreach (Catalog::verticals() as $index => $data) {
            $vertical = $this->upsertVertical($data, $index);

            $this->upsertNodes($vertical, null, $data['nodes'] ?? [], [
                'authority' => $data['authority'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'rights' => $data['rights'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertVertical(array $data, int $index): Vertical
    {
        $vertical = Vertical::query()->firstOrNew(['slug' => $data['slug']]);
        $isNew = ! $vertical->exists;

        $vertical->fill([
            'name' => $data['name'],
            'description' => $data['description'],
            'sort_order' => $index,
            'seo_title' => $data['seo_title'],
            'seo_description' => $data['seo_description'],
            'metadata' => array_replace($vertical->metadata ?? [], [
                'icon' => $data['icon'],
                'accent' => ['palette' => $data['palette']],
                'catalog' => array_filter([
                    'authority' => $data['authority'] ?? null,
                    'source_url' => $data['source_url'] ?? null,
                    'rights' => $data['rights'] ?? null,
                ], static fn (mixed $value): bool => $value !== null),
            ]),
        ]);

        if ($isNew) {
            $vertical->is_active = false;
        }

        $vertical->save();

        return $vertical;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array{authority: ?string, source_url: ?string, rights: ?string}  $inherited
     */
    private function upsertNodes(Vertical $vertical, ?int $parentId, array $nodes, array $inherited): void
    {
        foreach ($nodes as $index => $data) {
            $context = [
                'authority' => $data['authority'] ?? $inherited['authority'],
                'source_url' => $data['source_url'] ?? $inherited['source_url'],
                'rights' => $data['rights'] ?? $inherited['rights'],
            ];

            $node = $this->upsertNode($vertical, $parentId, $data, $index, $context);

            $this->upsertNodes($vertical, $node->id, $data['children'] ?? [], $context);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{authority: ?string, source_url: ?string, rights: ?string}  $context
     */
    private function upsertNode(Vertical $vertical, ?int $parentId, array $data, int $index, array $context): TaxonomyNode
    {
        $node = TaxonomyNode::query()->firstOrNew([
            'vertical_id' => $vertical->id,
            'parent_id' => $parentId,
            'slug' => $data['slug'],
        ]);

        $isNew = ! $node->exists;
        $description = $data['description'] ?? null;

        $node->fill([
            'type' => $data['type'],
            'name' => $data['name'],
            'description' => $description,
            'sort_order' => $index,
            'seo_title' => $data['seo_title'] ?? $data['name'].' — teste grilă gratuite | e-test.ro',
            'seo_description' => $data['seo_description'] ?? $description,
            'metadata' => array_replace($node->metadata ?? [], [
                'catalog' => array_filter([
                    'authority' => $context['authority'],
                    'source_url' => $context['source_url'],
                    'rights' => $context['rights'],
                    'items_raw' => $data['items'] ?? null,
                ], static fn (mixed $value): bool => $value !== null),
            ]),
        ]);

        if ($isNew) {
            $node->is_active = false;
        }

        $node->save();

        return $node;
    }
}
