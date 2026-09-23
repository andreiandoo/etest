<?php

namespace App\Console\Commands;

use App\Models\TaxonomyNode;
use App\Models\Vertical;
use Illuminate\Console\Command;

/**
 * Face publică o verticală împreună cu toată taxonomia ei.
 *
 * Catalogul intră în bază inactiv, fiindcă o pagină fără conținut indexată
 * devine, pentru Google, o pagină subțire pe un domeniu tânăr. Când o
 * verticală primește conținut, comutarea celor douăzeci de secțiuni din admin
 * una câte una e o corvoadă care se face pe jumătate, așa că există comanda
 * asta.
 *
 * Inversul e `--dezactiveaza`, util când o verticală trebuie scoasă din index
 * fără să fie ștearsă.
 */
class ActivateVertical extends Command
{
    protected $signature = 'content:activate
        {vertical : Slug-ul verticalei, de exemplu radio}
        {--sectiune= : Publică doar o secțiune și ce e sub ea}
        {--dezactiveaza : Scoate verticala din site în loc să o publice}';

    protected $description = 'Activează sau dezactivează o verticală și toată taxonomia ei';

    public function handle(): int
    {
        $slug = (string) $this->argument('vertical');
        $vertical = Vertical::query()->where('slug', $slug)->first();

        if ($vertical === null) {
            $this->error('Nu există nicio verticală cu slug-ul „'.$slug.'”.');

            return self::FAILURE;
        }

        $active = ! $this->option('dezactiveaza');
        $section = $this->option('sectiune');

        $vertical->forceFill(['is_active' => $active])->save();

        $query = TaxonomyNode::query()->where('vertical_id', $vertical->id);

        if ($section !== null) {
            $root = (clone $query)->where('slug', (string) $section)->first();

            if ($root === null) {
                $this->error('Verticala „'.$slug.'” nu are secțiunea „'.$section.'”.');

                return self::FAILURE;
            }

            // O secțiune nu se vede dacă părinții ei sunt ascunși, iar copiii
            // ei n-au rost fără ea: se comută tot lanțul, de sus până jos.
            $query->whereIn('id', [...$this->ancestors($root), $root->id, ...$this->descendants($root)]);
        }

        $nodes = $query->update(['is_active' => $active]);

        $this->info(sprintf(
            '%s: %s, împreună cu %d secțiuni%s.',
            $vertical->name,
            $active ? 'publicată' : 'scoasă din site',
            $nodes,
            $section === null ? '' : ' din „'.$section.'”',
        ));

        if ($active) {
            $this->line('Verifică sitemap.xml și golește cache-ul: php artisan optimize:clear');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function ancestors(TaxonomyNode $node): array
    {
        $ids = [];
        $current = $node->parent()->first();

        while ($current !== null) {
            $ids[] = (int) $current->id;
            $current = $current->parent()->first();
        }

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function descendants(TaxonomyNode $node): array
    {
        $ids = [];
        $level = [(int) $node->id];

        while ($level !== []) {
            $level = TaxonomyNode::query()->whereIn('parent_id', $level)->pluck('id')->map(intval(...))->all();
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }
}
