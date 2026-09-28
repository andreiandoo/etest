<?php

namespace App\Services\Testing;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use Illuminate\Database\Eloquent\Collection;

/**
 * De unde își ia un test întrebările.
 *
 * Sunt două feluri. Cel vechi ține o listă fixă, legată de test la construire:
 * bun pentru o sesiune de examen care chiar a avut întrebările acelea, cum e
 * grila INM din 2024, unde lista nu trebuie să se schimbe niciodată.
 *
 * Cel nou ține doar locul din care se trage — o secțiune și, dacă e cazul,
 * capitolele de sub ea — iar întrebările se aleg la fiecare încercare, din ce
 * există publicat în clipa aceea. Așa arată chestionarele auto: nu există
 * „testul numărul 7”, există proba, iar întrebările vin de fiecare dată altele.
 * Efectul secundar contează la fel de mult: o întrebare adăugată azi intră în
 * chestionare fără să reconstruiască nimeni nimic.
 */
final class QuestionPool
{
    /**
     * @return Collection<int, Question>
     */
    public function questions(TestDefinition $test): Collection
    {
        $nodeIds = $this->nodeIds($test);

        if ($nodeIds === null) {
            /** @var Collection<int, Question> $fixed */
            $fixed = $test->questions()
                ->with('options')
                ->where('questions.status', PublicationStatus::Published->value)
                ->get();

            return $fixed;
        }

        /** @var Collection<int, Question> $live */
        $live = Question::query()
            ->with('options')
            ->where('vertical_id', $test->vertical_id)
            ->whereIn('taxonomy_node_id', $nodeIds)
            ->where('status', PublicationStatus::Published->value)
            ->orderBy('id')
            ->get();

        return $live;
    }

    public function count(TestDefinition $test): int
    {
        $nodeIds = $this->nodeIds($test);

        if ($nodeIds === null) {
            return $test->questions()
                ->where('questions.status', PublicationStatus::Published->value)
                ->count();
        }

        return Question::query()
            ->where('vertical_id', $test->vertical_id)
            ->whereIn('taxonomy_node_id', $nodeIds)
            ->where('status', PublicationStatus::Published->value)
            ->count();
    }

    /**
     * Secțiunile din care trage testul, sau nimic dacă are listă fixă.
     *
     * @return array<int, int>|null
     */
    private function nodeIds(TestDefinition $test): ?array
    {
        $pool = $test->question_pool;

        if (! is_array($pool)) {
            return null;
        }

        $node = (int) ($pool['node'] ?? 0);

        if ($node < 1) {
            return null;
        }

        if (! (bool) ($pool['include_children'] ?? false)) {
            return [$node];
        }

        $found = TaxonomyNode::query()->find($node);

        return $found === null ? [$node] : $found->subtreeIds();
    }
}
