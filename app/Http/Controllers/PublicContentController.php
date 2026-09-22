<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Monetization\MonetizationResolver;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\StructuredData;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PublicContentController extends Controller
{
    public function show(
        Vertical $vertical,
        string $path,
        PublicUrlGenerator $urls,
        StructuredData $structuredData,
        MonetizationResolver $monetizationResolver,
        TenantContext $tenantContext,
    ): View|RedirectResponse {
        abort_unless($vertical->is_active && $tenantContext->allowsVertical($vertical), 404);

        $path = trim($path, '/');
        $segments = array_values(array_filter(explode('/', $path)));
        abort_if($segments === [], 404);

        $slug = end($segments);

        $node = TaxonomyNode::query()
            ->with(['vertical', 'parent'])
            ->where('vertical_id', $vertical->id)
            ->where('slug', $slug)
            ->active()
            ->first();

        if ($node !== null) {
            $expectedPath = $urls->taxonomyPath($node);

            if ($expectedPath === $path) {
                return $this->taxonomy($node, $urls, $structuredData, $monetizationResolver, $tenantContext);
            }
        }

        $test = TestDefinition::query()
            ->with(['vertical', 'taxonomyNode.parent'])
            ->where('vertical_id', $vertical->id)
            ->where('slug', $slug)
            ->published()
            ->first();

        if ($test !== null) {
            $expectedUrl = $urls->test($test);
            $expectedPath = ltrim((string) parse_url($expectedUrl, PHP_URL_PATH), '/');
            $incomingPath = $vertical->slug.'/'.$path;

            if ($expectedPath === $incomingPath) {
                return $this->test($test, $urls, $structuredData, $monetizationResolver, $tenantContext);
            }
        }

        if ($test !== null) {
            return redirect()->to($urls->test($test), 301);
        }

        if ($node !== null) {
            return redirect()->to($urls->taxonomy($node), 301);
        }

        abort(404);
    }

    private function taxonomy(
        TaxonomyNode $node,
        PublicUrlGenerator $urls,
        StructuredData $structuredData,
        MonetizationResolver $monetizationResolver,
        TenantContext $tenantContext,
    ): View {
        $children = $node->children()
            ->with('vertical')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tests = TestDefinition::query()
            ->with(['vertical', 'taxonomyNode'])
            ->where('taxonomy_node_id', $node->id)
            ->published()
            ->orderByDesc('published_at')
            ->paginate(24);

        $canonical = $this->paginatedCanonical($urls->taxonomy($node));
        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $description = $node->seo_description
            ?: $node->description
            ?: 'Teste și materiale de pregătire pentru '.$node->name.'.';

        $breadcrumbs = $structuredData->taxonomyBreadcrumbs($node);

        return view('taxonomy.show', [
            'node' => $node,
            'children' => $children,
            'tests' => $tests,
            'canonical' => $canonical,
            'seoTitle' => $node->seo_title ?: $node->name.' – teste gratuite | '.$brandName,
            'seoDescription' => $description,
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                $structuredData->breadcrumbs($breadcrumbs),
                $structuredData->collection($node->name, $description, $canonical),
                $structuredData->itemList($node->name, $tests->items()),
            ],
            'urlGenerator' => $urls,
            'monetization' => $monetizationResolver->resolve($node->vertical, $node),
        ]);
    }

    private function test(
        TestDefinition $test,
        PublicUrlGenerator $urls,
        StructuredData $structuredData,
        MonetizationResolver $monetizationResolver,
        TenantContext $tenantContext,
    ): View {
        $publishedQuestionCount = $test->questions()
            ->where('questions.status', PublicationStatus::Published->value)
            ->count();
        $questionCount = $test->question_limit !== null
            ? min($test->question_limit, $publishedQuestionCount)
            : $publishedQuestionCount;
        $canonical = $urls->test($test);
        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $description = $test->seo_description
            ?: $test->description
            ?: 'Test online gratuit: '.$test->title.'.';

        $breadcrumbs = $structuredData->testBreadcrumbs($test);

        return view('tests.show', [
            'vertical' => $test->vertical,
            'test' => $test,
            'questionCount' => $questionCount,
            'canonical' => $canonical,
            'seoTitle' => $test->seo_title ?: $test->title.' | '.$brandName,
            'seoDescription' => $description,
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                $structuredData->breadcrumbs($breadcrumbs),
                $structuredData->quiz($test),
            ],
            'urlGenerator' => $urls,
            'monetization' => $monetizationResolver->resolve($test->vertical, $test->taxonomyNode, $test),
        ]);
    }

    private function paginatedCanonical(string $base): string
    {
        $page = max(1, (int) request()->query('page', 1));

        return $page > 1 ? $base.'?page='.$page : $base;
    }
}
