<?php

namespace App\Services\Content;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;

/**
 * Publică ce a adus o sincronizare.
 *
 * Coada de revizuire e făcută pentru conținut scris de om, unde răspunsul
 * corect e o decizie. Aici nu e: ANCOM publică el însuși baremul, marcat cu
 * `@` în chiar documentul oficial, iar noi nu-l interpretăm, doar îl citim. O
 * coadă de șase sute de rânduri pe care cineva ar apăsa „publică” fără să
 * verifice nimic nu e o verificare, e un obstacol.
 *
 * Ce nu iese curat din parser nu ajunge oricum aici: rândurile respinse rămân
 * în lista de erori a importului, netrecute în întrebări.
 *
 * `reviewed_by` și `published_by` rămân goale dinadins. Nicio persoană nu a
 * apăsat nimic, iar istoricul nu trebuie să pretindă altceva.
 */
final class ImportPublisher
{
    /**
     * @return array{questions: int, tests: int}
     */
    public function publish(Source $source): array
    {
        $now = now();

        $questions = Question::query()
            ->where('vertical_id', $source->vertical_id)
            ->where('source_url', $source->document_url)
            ->where('status', PublicationStatus::Draft->value)
            ->update([
                'status' => PublicationStatus::Published->value,
                'reviewed_at' => $now,
                'updated_at' => $now,
            ]);

        $tests = TestDefinition::query()
            ->where('vertical_id', $source->vertical_id)
            ->whereIn('taxonomy_node_id', $this->nodeIds($source))
            ->where('status', PublicationStatus::Draft->value)
            ->update([
                'status' => PublicationStatus::Published->value,
                'published_at' => $now,
                'reviewed_at' => $now,
                'updated_at' => $now,
            ]);

        return ['questions' => $questions, 'tests' => $tests];
    }

    /**
     * Secțiunea sursei și capitolele de sub ea.
     *
     * @return array<int, int>
     */
    private function nodeIds(Source $source): array
    {
        if ($source->taxonomy_node_id === null) {
            return [];
        }

        return TaxonomyNode::query()
            ->where('parent_id', $source->taxonomy_node_id)
            ->pluck('id')
            ->push($source->taxonomy_node_id)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
