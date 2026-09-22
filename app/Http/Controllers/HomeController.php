<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Seo\StructuredData;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(StructuredData $structuredData, TenantContext $tenantContext): View
    {
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $verticals = Vertical::query()
            ->active()
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
            ->withCount([
                'tests as published_tests_count' => fn ($query) => $query
                    ->where('status', PublicationStatus::Published->value)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $brandDescription = (string) $tenantContext->brand(
            'seo_description',
            'Teste online gratuite pentru examene, certificări și evaluări. Exersează pe domenii, urmărește progresul și revino la punctele slabe.',
        );

        return view('home', [
            'verticals' => $verticals,
            'publishedTestsCount' => TestDefinition::query()
                ->published()
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->count(),
            'publishedQuestionsCount' => Question::query()
                ->where('status', PublicationStatus::Published->value)
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->count(),
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
