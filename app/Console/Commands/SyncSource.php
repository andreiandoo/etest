<?php

namespace App\Console\Commands;

use App\Services\Sources\SourceRegistry;
use App\Services\Sources\SourceSynchronizer;
use App\Services\Sources\SyncResult;
use Illuminate\Console\Command;
use Throwable;

/**
 * `php artisan sources:sync ancom-radioamator`
 *
 * Fără argument, arată ce conectori există. Cu `--fisier`, sare peste
 * descărcare și folosește un PDF sau un text de pe disc — util când sursa e
 * momentan inaccesibilă sau când vrem să verificăm parserul pe o extragere
 * făcută cu alt instrument.
 *
 * Ce iese curat din parser se publică. Pentru surse unde răspunsul corect nu
 * vine marcat de autoritate, `--ciorne` lasă totul în coada de revizuire.
 */
class SyncSource extends Command
{
    protected $signature = 'sources:sync
        {sursa? : Cheia conectorului}
        {--forteaza : Reimportă chiar dacă documentul nu s-a schimbat}
        {--fisier= : Folosește un fișier local în loc să descarce}
        {--ciorne : Lasă întrebările ca ciorne, pentru revizuire manuală}';

    protected $description = 'Descarcă o sursă oficială, o parsează și publică întrebările';

    public function handle(SourceRegistry $registry, SourceSynchronizer $synchronizer): int
    {
        $key = $this->argument('sursa');

        if ($key === null) {
            $this->info('Conectori disponibili:');

            foreach ($registry->all() as $connector) {
                $definition = $connector->definition();
                $this->line('  · '.$connector->key().' — '.$definition['authority'].', '.$definition['title']);
            }

            return self::SUCCESS;
        }

        try {
            $connector = $registry->get((string) $key);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Sincronizez '.$connector->key().'…');

        try {
            $result = $synchronizer->sync(
                $connector,
                force: (bool) $this->option('forteaza'),
                localFile: $this->option('fisier') === null ? null : (string) $this->option('fisier'),
                publish: ! $this->option('ciorne'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result->isUnchanged()) {
            $this->info('Documentul nu s-a schimbat (SHA-256 '.substr($result->sha256, 0, 12).'…).');

            if ($result->published['questions'] > 0 || $result->published['tests'] > 0) {
                $this->line('Am publicat ce rămăsese ciornă: '
                    .$result->published['questions'].' întrebări, '
                    .$result->published['tests'].' teste.');
            }

            $this->line('Reimportă cu --forteaza dacă vrei totuși să treci rândurile din nou.');
            $this->reminder($result);

            return self::SUCCESS;
        }

        $import = $result->import;
        $rejected = $result->rejected;

        $this->newLine();
        $this->table(['', ''], [
            ['Subiecte găsite în document', (string) $result->total],
            ['Întrebări create', (string) $import?->created_rows],
            ['Întrebări actualizate', (string) $import?->updated_rows],
            ['Rânduri respinse', (string) $import?->failed_rows],
            ['Teste de exercițiu', (string) $result->tests],
            ['Întrebări publicate', (string) $result->published['questions']],
            ['Teste publicate', (string) $result->published['tests']],
            ['Amprentă SHA-256', $result->sha256],
        ]);

        if ($rejected !== []) {
            $this->newLine();
            $this->warn('Respinse — se repară de mână în /admin/importuri:');

            foreach (array_slice($rejected, 0, 20) as $row) {
                $this->line('  · '.$row['code'].' — '.$row['reason']);
            }
        }

        $this->newLine();

        if ($this->option('ciorne')) {
            $this->info('Întrebările au intrat ca ciorne. Trimite-le în revizuire și publică-le din /admin/revizuire.');
        } else {
            $this->info('Întrebările și testele sunt publicate.');
        }

        $this->reminder($result);

        return self::SUCCESS;
    }

    /**
     * Conținutul publicat nu se vede cât timp domeniul e inactiv, iar asta e o
     * decizie separată: activarea îl bagă în sitemap și pe prima pagină.
     */
    private function reminder(SyncResult $result): void
    {
        $vertical = $result->source->vertical;

        if ($vertical === null || $vertical->is_active) {
            return;
        }

        $this->newLine();
        $this->warn('Domeniul „'.$vertical->name.'” e încă inactiv, deci nimic din ce s-a publicat nu apare pe site.');
        $this->line('Ca să îl publici: php artisan content:activate '.$vertical->slug);
    }
}
