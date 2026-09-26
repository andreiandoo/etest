<?php

namespace App\Console\Commands;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\Source;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Console\Command;

/**
 * Unde stăm cu acoperirea, pe verticale.
 *
 * Întrebarea la care răspunde nu e tehnică: „mai am de pregătit ceva pe
 * domeniul ăsta?”. De aceea numără ce vede candidatul — întrebări publicate și
 * teste publicate — nu ce există în bază.
 *
 * Verticalele fără conținut sunt scopul de lucru, nu o eroare, așa că apar
 * separat, la final, în ordinea priorității din catalog.
 */
class ContentStatus extends Command
{
    protected $signature = 'content:status {--tot : Arată și verticalele goale, rând cu rând}';

    protected $description = 'Arată câte întrebări și teste are fiecare domeniu';

    public function handle(): int
    {
        $verticals = Vertical::query()->orderBy('sort_order')->orderBy('name')->get();
        $sources = Source::query()->get()->groupBy('vertical_id');

        $rows = [];
        $empty = [];
        $questions = 0;
        $tests = 0;

        foreach ($verticals as $vertical) {
            $published = Question::query()
                ->where('vertical_id', $vertical->id)
                ->where('status', PublicationStatus::Published->value)
                ->count();

            $available = TestDefinition::query()
                ->where('vertical_id', $vertical->id)
                ->published()
                ->count();

            if ($published === 0 && $available === 0) {
                $empty[] = $vertical->name;

                continue;
            }

            $questions += $published;
            $tests += $available;

            $rows[] = [
                $vertical->name,
                number_format($published, 0, ',', ' '),
                (string) $available,
                (string) ($sources[$vertical->id] ?? collect())->count(),
                $vertical->is_active ? 'pe site' : 'ascuns',
            ];
        }

        if ($rows !== []) {
            $this->table(['Domeniu', 'Întrebări', 'Teste', 'Surse', 'Stare'], $rows);
        }

        $this->newLine();
        $this->info(sprintf(
            '%d domenii cu conținut, %s întrebări și %d teste publicate.',
            count($rows),
            number_format($questions, 0, ',', ' '),
            $tests,
        ));

        if ($empty === []) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn(count($empty).' domenii încă fără conținut:');

        if ($this->option('tot')) {
            foreach ($empty as $name) {
                $this->line('  · '.$name);
            }

            return self::SUCCESS;
        }

        $this->line('  '.implode(', ', $empty));
        $this->line('  (--tot le listează pe rânduri)');

        return self::SUCCESS;
    }
}
