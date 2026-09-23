<?php

namespace App\Services\Content;

use App\Enums\PublicationStatus;
use App\Enums\TestMode;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * Construiește testele de exercițiu dintr-o secțiune de taxonomie.
 *
 * Un import aduce întrebări, nu pagini pe care cineva chiar poate exersa. Fără
 * pasul ăsta, o verticală cu șase sute de întrebări arată tot goală: catalogul
 * public listează teste, nu întrebări răzlețe.
 *
 * Testele intră ca ciorne, ca tot ce vine din import. Și chiar publicate, un
 * test afișează doar întrebările publicate, deci nu poate scurge în site ceva
 * ce n-a trecut prin revizuire.
 */
final class PracticeTestBuilder
{
    /**
     * @param  callable(Builder<Question>): void|null  $filter
     */
    public function build(
        TaxonomyNode $node,
        string $slug,
        string $title,
        string $description,
        int $questionLimit,
        bool $includeChildren = false,
        ?callable $filter = null,
    ): ?TestDefinition {
        // Un singur nivel de copii e de ajuns pentru structura de acum:
        // examen → capitole. Dacă apare un nivel mai jos, aici se vede.
        $nodeIds = $includeChildren
            ? TaxonomyNode::query()->where('parent_id', $node->id)->pluck('id')->push($node->id)->all()
            : [$node->id];

        $query = Question::query()
            ->where('vertical_id', $node->vertical_id)
            ->whereIn('taxonomy_node_id', $nodeIds);

        if ($filter !== null) {
            $filter($query);
        }

        $ids = $query->orderBy('id')->pluck('id')->all();

        if ($ids === []) {
            return null;
        }

        $test = TestDefinition::query()->firstOrNew([
            'vertical_id' => $node->vertical_id,
            'slug' => $slug,
        ]);

        $isNew = ! $test->exists;

        $test->fill([
            'taxonomy_node_id' => $node->id,
            'title' => $title,
            'description' => $description,
            'seo_title' => $title.' | e-test.ro',
            'seo_description' => $description,
            'mode' => TestMode::Practice,
            // Numărul de întrebări e o limită, nu o selecție fixă: la fiecare
            // încercare se amestecă altele din același fond, ca al doilea tur
            // să nu fie o repetare din memorie.
            'question_limit' => min($questionLimit, count($ids)),
            'randomize_questions' => true,
            'randomize_options' => true,
            'allow_review' => true,
            'show_explanations' => true,
        ]);

        if ($isNew) {
            $test->status = PublicationStatus::Draft;
        }

        $test->save();

        $test->questions()->sync($this->pivot($ids));

        return $test;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function pivot(array $ids): array
    {
        $pivot = [];

        foreach (array_values($ids) as $position => $id) {
            $pivot[$id] = ['position' => $position, 'points' => 1, 'required' => true];
        }

        return $pivot;
    }
}
