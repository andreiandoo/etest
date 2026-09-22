<?php

namespace App\Http\Controllers;

use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Monetization\MonetizationResolver;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\StructuredData;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;

class VerticalController extends Controller
{
    public function show(
        Vertical $vertical,
        PublicUrlGenerator $urls,
        StructuredData $structuredData,
        MonetizationResolver $monetizationResolver,
        TenantContext $tenantContext,
    ): View {
        abort_unless($vertical->is_active && $tenantContext->allowsVertical($vertical), 404);

        $nodes = TaxonomyNode::query()
            ->where('vertical_id', $vertical->id)
            ->whereNull('parent_id')
            ->active()
            ->with('vertical')
            ->withCount([
                'tests as published_tests_count' => fn ($query) => $query->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tests = TestDefinition::query()
            ->with(['vertical', 'taxonomyNode'])
            ->where('vertical_id', $vertical->id)
            ->whereNull('taxonomy_node_id')
            ->published()
            ->orderByDesc('published_at')
            ->paginate(24);

        $canonical = $this->paginatedCanonical($urls->vertical($vertical));
        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $description = $vertical->seo_description
            ?: $vertical->description
            ?: 'Teste online gratuite pentru '.$vertical->name.'.';

        $breadcrumbs = $structuredData->verticalBreadcrumbs($vertical);

        return view('verticals.show', [
            'vertical' => $vertical,
            'nodes' => $nodes,
            'tests' => $tests,
            'canonical' => $canonical,
            'seoTitle' => $vertical->seo_title ?: $vertical->name.' – teste gratuite | '.$brandName,
            'seoDescription' => $description,
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                $structuredData->breadcrumbs($breadcrumbs),
                $structuredData->collection($vertical->name, $description, $canonical),
            ],
            'urlGenerator' => $urls,
            'monetization' => $monetizationResolver->resolve($vertical),
        ]);
    }

    private function paginatedCanonical(string $base): string
    {
        $page = max(1, (int) request()->query('page', 1));

        return $page > 1 ? $base.'?page='.$page : $base;
    }
}
