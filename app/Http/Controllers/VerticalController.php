<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\Question;
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

        // Pagina de domeniu e un index: listează tot ce e publicat în vertical,
        // nu doar testele agățate direct de ea. Mai multe legături interne
        // înseamnă mai multe pagini descoperite de motoare.
        $tests = TestDefinition::query()
            ->with(['vertical', 'taxonomyNode'])
            ->where('vertical_id', $vertical->id)
            ->published()
            ->orderByDesc('published_at')
            ->paginate(24)
            ->withQueryString();

        $popularTests = TestDefinition::query()
            ->with(['vertical', 'taxonomyNode'])
            ->where('vertical_id', $vertical->id)
            ->published()
            ->withCount('attempts')
            ->orderByDesc('attempts_count')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $questionsCount = Question::query()
            ->where('vertical_id', $vertical->id)
            ->where('status', PublicationStatus::Published->value)
            ->count();

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
            'popularTests' => $popularTests,
            'questionsCount' => $questionsCount,
            'canonical' => $canonical,
            'seoTitle' => $vertical->seo_title ?: $vertical->name.' – teste gratuite | '.$brandName,
            'seoDescription' => $description,
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                $structuredData->breadcrumbs($breadcrumbs),
                $structuredData->collection($vertical->name, $description, $canonical),
                $structuredData->itemList($vertical->name, $tests->items()),
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
