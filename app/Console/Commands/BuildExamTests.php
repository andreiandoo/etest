<?php

namespace App\Console\Commands;

use App\Enums\TestMode;
use App\Models\TaxonomyNode;
use App\Models\Vertical;
use App\Services\Content\PracticeTestBuilder;
use Illuminate\Console\Command;

/**
 * `php artisan content:teste-examen auto`
 *
 * Construiește, pentru fiecare secțiune care are regulile probei scrise în
 * catalog, testul în formatul examenului: numărul de întrebări, timpul și
 * pragul de promovare din lege.
 *
 * Regulile nu se inventează aici. Stau în metadata secțiunii, puse de catalog
 * din actul normativ — la permisul de conducere, Ordinul 268/2010 — iar comanda
 * doar le citește. Așa, o probă care se schimbă se schimbă într-un singur loc.
 *
 * Secțiunile fără întrebări se sar: un test fără fond n-are ce să arate.
 */
class BuildExamTests extends Command
{
    protected $signature = 'content:teste-examen {verticala? : Slug-ul domeniului; fără el, toate}';

    protected $description = 'Creează testele în formatul examenului, după regulile din catalog';

    public function handle(PracticeTestBuilder $builder): int
    {
        $slug = $this->argument('verticala');

        $verticals = Vertical::query()
            ->when($slug !== null, fn ($query) => $query->where('slug', (string) $slug))
            ->orderBy('sort_order')
            ->get();

        if ($verticals->isEmpty()) {
            $this->error('Nu am găsit domeniul „'.$slug.'”.');

            return self::FAILURE;
        }

        $built = 0;
        $skipped = 0;

        foreach ($verticals as $vertical) {
            $nodes = TaxonomyNode::query()
                ->where('vertical_id', $vertical->id)
                ->whereNotNull('metadata')
                ->orderBy('sort_order')
                ->get()
                ->filter(static fn (TaxonomyNode $node): bool => is_array(data_get($node->metadata, 'exam')));

            foreach ($nodes as $node) {
                $exam = (array) data_get($node->metadata, 'exam');
                $questions = (int) ($exam['questions'] ?? 0);
                $duration = (int) ($exam['duration_seconds'] ?? 0);
                $passing = (int) ($exam['passing_questions'] ?? 0);

                if ($questions < 1) {
                    continue;
                }

                $test = $builder->build(
                    $node,
                    $node->slug.'-examen',
                    $node->name.' — simulare examen',
                    'Proba teoretică pentru '.mb_strtolower($node->name).', în formatul ei oficial: '
                        .$questions.' întrebări în '.intdiv($duration, 60).' de minute, cu '.$passing
                        .' răspunsuri corecte pentru promovare.',
                    $questions,
                    includeChildren: true,
                    mode: TestMode::Exam,
                    durationSeconds: $duration > 0 ? $duration : null,
                    passingQuestions: $passing > 0 ? $passing : null,
                );

                if ($test === null) {
                    $skipped++;

                    continue;
                }

                $built++;
                $this->line('  · '.$test->title.' — '.$test->question_limit.' întrebări');
            }
        }

        $this->newLine();
        $this->info($built.' teste construite, '.$skipped.' secțiuni sărite fiindcă n-au încă întrebări.');

        if ($built > 0) {
            $this->line('Testele noi intră ca ciorne. Publică-le din /admin/teste.');
        }

        return self::SUCCESS;
    }
}
