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

        $vertical->forceFill(['is_active' => $active])->save();

        $nodes = TaxonomyNode::query()
            ->where('vertical_id', $vertical->id)
            ->update(['is_active' => $active]);

        $this->info(sprintf(
            '%s: %s, împreună cu %d secțiuni.',
            $vertical->name,
            $active ? 'publicată' : 'scoasă din site',
            $nodes,
        ));

        if ($active) {
            $this->line('Verifică sitemap.xml și golește cache-ul: php artisan optimize:clear');
        }

        return self::SUCCESS;
    }
}
