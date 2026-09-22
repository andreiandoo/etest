<?php

namespace App\Services\Monetization;

use App\Models\AffiliateResource;
use App\Models\LeadCampaign;
use App\Models\SponsorPlacement;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Support\Collection;

final class MonetizationResolver
{
    /**
     * @return array{
     *     sponsor:?SponsorPlacement,
     *     lead:?LeadCampaign,
     *     affiliate_resources:Collection<int, AffiliateResource>,
     *     newsletter:array{interest_key:string,vertical_id:int,taxonomy_node_id:?int,label:string}
     * }
     */
    public function resolve(
        Vertical $vertical,
        ?TaxonomyNode $node = null,
        ?TestDefinition $test = null,
    ): array {
        $taxonomyIds = $this->taxonomyIds($node ?? $test?->taxonomyNode);

        $sponsor = SponsorPlacement::query()
            ->with(['campaign.sponsor'])
            ->where('is_active', true)
            ->where('placement', 'content')
            ->whereHas('campaign', fn ($query) => $query->currentlyActive())
            ->get()
            ->filter(fn (SponsorPlacement $placement): bool => $this->matches(
                $placement->vertical_id,
                $placement->taxonomy_node_id,
                $placement->test_id,
                $vertical->id,
                $taxonomyIds,
                $test?->id,
            ))
            ->sortByDesc(fn (SponsorPlacement $placement): int => $this->score(
                $placement->vertical_id,
                $placement->taxonomy_node_id,
                $placement->test_id,
                $vertical->id,
                $taxonomyIds,
                $test?->id,
                (int) $placement->campaign->priority,
            ))
            ->first();

        $lead = LeadCampaign::query()
            ->currentlyActive()
            ->get()
            ->filter(fn (LeadCampaign $campaign): bool => $this->matches(
                $campaign->vertical_id,
                $campaign->taxonomy_node_id,
                $campaign->test_id,
                $vertical->id,
                $taxonomyIds,
                $test?->id,
            ))
            ->sortByDesc(fn (LeadCampaign $campaign): int => $this->score(
                $campaign->vertical_id,
                $campaign->taxonomy_node_id,
                $campaign->test_id,
                $vertical->id,
                $taxonomyIds,
                $test?->id,
                (int) $campaign->priority,
            ))
            ->first();

        $affiliateResources = AffiliateResource::query()
            ->with('merchant')
            ->currentlyActive()
            ->get()
            ->filter(fn (AffiliateResource $resource): bool => $this->matches(
                $resource->vertical_id,
                $resource->taxonomy_node_id,
                $resource->test_id,
                $vertical->id,
                $taxonomyIds,
                $test?->id,
            ))
            ->sortByDesc(fn (AffiliateResource $resource): int => $this->score(
                $resource->vertical_id,
                $resource->taxonomy_node_id,
                $resource->test_id,
                $vertical->id,
                $taxonomyIds,
                $test?->id,
                (int) $resource->priority,
            ))
            ->take(6)
            ->values();

        $newsletterNode = $node ?? $test?->taxonomyNode;

        return [
            'sponsor' => $sponsor,
            'lead' => $lead,
            'affiliate_resources' => $affiliateResources,
            'newsletter' => $newsletterNode !== null
                ? [
                    'interest_key' => 'taxonomy:'.$newsletterNode->id,
                    'vertical_id' => $vertical->id,
                    'taxonomy_node_id' => $newsletterNode->id,
                    'label' => $newsletterNode->name,
                ]
                : [
                    'interest_key' => 'vertical:'.$vertical->id,
                    'vertical_id' => $vertical->id,
                    'taxonomy_node_id' => null,
                    'label' => $vertical->name,
                ],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function taxonomyIds(?TaxonomyNode $node): array
    {
        $ids = [];
        $current = $node;

        while ($current !== null) {
            $ids[] = (int) $current->id;
            $current = $current->parent()->first();
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $taxonomyIds
     */
    private function matches(
        ?int $targetVertical,
        ?int $targetTaxonomy,
        ?int $targetTest,
        int $verticalId,
        array $taxonomyIds,
        ?int $testId,
    ): bool {
        if ($targetTest !== null) {
            return $testId !== null && $targetTest === $testId;
        }

        if ($targetTaxonomy !== null) {
            return in_array($targetTaxonomy, $taxonomyIds, true);
        }

        if ($targetVertical !== null) {
            return $targetVertical === $verticalId;
        }

        return true;
    }

    /**
     * @param  array<int, int>  $taxonomyIds
     */
    private function score(
        ?int $targetVertical,
        ?int $targetTaxonomy,
        ?int $targetTest,
        int $verticalId,
        array $taxonomyIds,
        ?int $testId,
        int $priority,
    ): int {
        if ($targetTest !== null && $testId !== null && $targetTest === $testId) {
            return 400_000 + $priority;
        }

        if ($targetTaxonomy !== null) {
            $position = array_search($targetTaxonomy, $taxonomyIds, true);

            if ($position !== false) {
                return 300_000 - ((int) $position * 1_000) + $priority;
            }
        }

        if ($targetVertical !== null && $targetVertical === $verticalId) {
            return 200_000 + $priority;
        }

        return 100_000 + $priority;
    }
}
