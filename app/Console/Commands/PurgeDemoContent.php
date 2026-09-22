<?php

namespace App\Console\Commands;

use App\Models\Vertical;
use Illuminate\Console\Command;

/**
 * Șterge conținutul demonstrativ.
 *
 * Handoff-ul de producție cere ca setul temporar de QA să dispară înainte de
 * publicare. Ștergerea manuală din admin e ușor de făcut pe jumătate, așa că
 * o facem dintr-o singură comandă, pe baza marcajului `metadata.demo`.
 *
 * Verticalele au ștergere în cascadă către taxonomie, teste și întrebări, deci
 * ștergerea lor curăță tot arborele.
 */
class PurgeDemoContent extends Command
{
    protected $signature = 'content:purge-demo {--force : Șterge fără confirmare}';

    protected $description = 'Șterge conținutul marcat ca demonstrativ (metadata.demo = true)';

    public function handle(): int
    {
        $verticals = Vertical::query()
            ->whereJsonContains('metadata->demo', true)
            ->get();

        if ($verticals->isEmpty()) {
            $this->info('Nu există conținut demonstrativ. Nimic de șters.');

            return self::SUCCESS;
        }

        $this->warn('Urmează să fie șterse '.$verticals->count().' verticale, împreună cu toată taxonomia, testele și întrebările lor:');

        foreach ($verticals as $vertical) {
            $this->line('  · '.$vertical->name.' ('.$vertical->slug.')');
        }

        if (! $this->option('force') && ! $this->confirm('Continui?', false)) {
            $this->info('Anulat. Nu s-a șters nimic.');

            return self::SUCCESS;
        }

        foreach ($verticals as $vertical) {
            $vertical->delete();
        }

        $this->info('Conținutul demonstrativ a fost șters.');
        $this->line('Verifică sitemap.xml și reconstruiește cache-ul: php artisan optimize:clear && php artisan optimize');

        return self::SUCCESS;
    }
}
