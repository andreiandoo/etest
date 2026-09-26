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
 * corect e o decizie. Aici nu e: autoritatea publică ea însăși baremul, iar noi
 * nu-l interpretăm, doar îl citim. O coadă de treisprezece mii de rânduri pe
 * care cineva ar apăsa „publică” fără să verifice nimic nu e o verificare, e un
 * obstacol.
 *
 * Ce se publică se alege după locul din taxonomie, nu după adresa
 * documentului. O sursă cu un singur fișier își recunoștea întrebările după
 * adresă, dar CNCAN are câte un PDF pe specialitate, iar întrebările poartă
 * adresa specialității, nu a paginii-sursă: toate treisprezece mii rămâneau
 * ciorne sub un teren de teste publicate. Secțiunea sursei și tot ce e sub ea,
 * pe oricâte niveluri, e regula care ține pentru fiecare conector — unii pun
 * întrebările în copiii secțiunii, Baroul le pune în nepoți.
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
        $nodes = $this->subtree($source);

        if ($nodes === []) {
            return ['questions' => 0, 'tests' => 0];
        }

        $now = now();

        $questions = Question::query()
            ->where('vertical_id', $source->vertical_id)
            ->whereIn('taxonomy_node_id', $nodes)
            ->where('status', PublicationStatus::Draft->value)
            ->update([
                'status' => PublicationStatus::Published->value,
                'reviewed_at' => $now,
                'updated_at' => $now,
            ]);

        $tests = TestDefinition::query()
            ->where('vertical_id', $source->vertical_id)
            ->whereIn('taxonomy_node_id', $nodes)
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
     * Secțiunea sursei și tot ce crește sub ea.
     *
     * Coborârea se face nivel cu nivel, nu recursiv în SQL, ca să meargă la fel
     * pe Postgres și pe baza din teste. Nodurile deja văzute nu se mai caută a
     * doua oară, deci o legătură greșită în date nu învârte bucla la infinit.
     *
     * @return array<int, int>
     */
    private function subtree(Source $source): array
    {
        if ($source->taxonomy_node_id === null) {
            return [];
        }

        $ids = [(int) $source->taxonomy_node_id];
        $level = $ids;

        while ($level !== []) {
            $children = TaxonomyNode::query()
                ->whereIn('parent_id', $level)
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $level = array_values(array_diff($children, $ids));
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }
}
