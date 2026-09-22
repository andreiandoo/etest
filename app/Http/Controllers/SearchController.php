<?php

namespace App\Http\Controllers;

use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const LIMIT = 20;

    public function __invoke(
        Request $request,
        PublicUrlGenerator $urls,
        TenantContext $tenantContext,
    ): View {
        $term = trim((string) $request->query('q', ''));
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $verticals = collect();
        $nodes = collect();
        $tests = collect();

        // Sub două caractere rezultatele sunt zgomot, nu ajutor.
        if (mb_strlen($term) >= 2) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

            $verticals = Vertical::query()
                ->active()
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
                ->where('name', 'ilike', $like)
                ->orderBy('sort_order')
                ->limit(self::LIMIT)
                ->get();

            $nodes = TaxonomyNode::query()
                ->active()
                ->with('vertical')
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->where('name', 'ilike', $like)
                ->orderBy('sort_order')
                ->limit(self::LIMIT)
                ->get();

            $tests = TestDefinition::query()
                ->published()
                ->with(['vertical', 'taxonomyNode'])
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->where('title', 'ilike', $like)
                ->orderByDesc('published_at')
                ->limit(self::LIMIT)
                ->get();
        }

        return view('search', [
            'term' => $term,
            'verticals' => $verticals,
            'nodes' => $nodes,
            'tests' => $tests,
            'resultCount' => $verticals->count() + $nodes->count() + $tests->count(),
            'urlGenerator' => $urls,
            'seoTitle' => $term !== ''
                ? 'Rezultate pentru „'.$term.'” | e-test.ro'
                : 'Caută pe e-test.ro',
            'seoDescription' => 'Caută examene, materii, capitole și teste pe e-test.ro.',
            // Paginile de căutare nu au ce căuta în index.
            'robots' => 'noindex,follow',
        ]);
    }
}
