<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\StructuredData;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(
        StructuredData $structuredData,
        TenantContext $tenantContext,
        PublicUrlGenerator $urls,
    ): View {
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $verticals = Vertical::query()
            ->active()
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
            ->withCount([
                'tests as published_tests_count' => fn ($query) => $query
                    ->where('status', PublicationStatus::Published->value)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()),
                'questions as published_questions_count' => fn ($query) => $query
                    ->where('status', PublicationStatus::Published->value),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Popularitatea se măsoară în încercări, nu în vizualizări: un test pe
        // care oamenii chiar îl dau spune mai mult decât unul pe care îl deschid.
        $popularTests = TestDefinition::query()
            ->published()
            ->with(['vertical', 'taxonomyNode'])
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
            ->withCount('attempts')
            ->orderByDesc('attempts_count')
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        $recentTests = TestDefinition::query()
            ->published()
            ->with(['vertical', 'taxonomyNode'])
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $brandDescription = (string) $tenantContext->brand(
            'seo_description',
            'Teste online gratuite pentru examene, certificări și evaluări. Exersează pe domenii, urmărește progresul și revino la punctele slabe.',
        );

        return view('home', [
            'verticals' => $verticals,
            'popularTests' => $popularTests,
            'recentTests' => $recentTests,
            'publishedTestsCount' => TestDefinition::query()
                ->published()
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->count(),
            'publishedQuestionsCount' => Question::query()
                ->where('status', PublicationStatus::Published->value)
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->count(),
            'urlGenerator' => $urls,
            'canonical' => route('home'),
            'seoTitle' => (string) $tenantContext->brand(
                'seo_title',
                $brandName.' – teste online gratuite pentru examene și certificări',
            ),
            'seoDescription' => $brandDescription,
            'structuredData' => [$structuredData->website()],
        ]);
    }
}
