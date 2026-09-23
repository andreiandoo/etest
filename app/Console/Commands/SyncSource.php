<?php

namespace App\Console\Commands;

use App\Services\Sources\SourceRegistry;
use App\Services\Sources\SourceSynchronizer;
use Illuminate\Console\Command;
use Throwable;

/**
 * `php artisan sources:sync ancom-radioamator`
 *
 * Fără argument, arată ce conectori există. Cu `--fisier`, sare peste
 * descărcare și folosește un PDF sau un text de pe disc — util când sursa e
 * momentan inaccesibilă sau când vrem să verificăm parserul pe o extragere
 * făcută cu alt instrument.
 */
class SyncSource extends Command
{
    protected $signature = 'sources:sync
        {sursa? : Cheia conectorului}
        {--forteaza : Reimportă chiar dacă documentul nu s-a schimbat}
        {--fisier= : Folosește un fișier local în loc să descarce}';

    protected $description = 'Descarcă o sursă oficială, o parsează și aduce întrebările ca ciorne';

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
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result->isUnchanged()) {
            $this->info('Documentul nu s-a schimbat (SHA-256 '.substr($result->sha256, 0, 12).'…).');
            $this->line('Reimportă cu --forteaza dacă vrei totuși să treci rândurile din nou.');

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
        $this->info('Întrebările au intrat ca ciorne. Trimite-le în revizuire și publică-le din /admin/revizuire.');

        return self::SUCCESS;
    }
}
