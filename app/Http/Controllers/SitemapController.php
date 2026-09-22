<?php

namespace App\Http\Controllers;

use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        return $this->xml($this->sitemapIndex([
            route('sitemaps.verticals'),
            route('sitemaps.taxonomy'),
            route('sitemaps.tests'),
        ]));
    }

    public function verticals(PublicUrlGenerator $urls, TenantContext $tenantContext): Response
    {
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $items = Vertical::query()
            ->active()
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
            ->orderBy('id')
            ->get()
            ->map(fn (Vertical $vertical): array => [
                'loc' => $urls->vertical($vertical),
                'lastmod' => $vertical->updated_at?->toAtomString(),
            ])
            ->all();

        array_unshift($items, [
            'loc' => route('home'),
            'lastmod' => null,
        ]);

        return $this->xml($this->urlSet($items));
    }

    public function taxonomy(PublicUrlGenerator $urls, TenantContext $tenantContext): Response
    {
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $items = TaxonomyNode::query()
            ->with(['vertical', 'parent'])
            ->whereHas('vertical', fn ($query) => $query->active())
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
            ->active()
            ->orderBy('id')
            ->get()
            ->map(fn (TaxonomyNode $node): array => [
                'loc' => $urls->taxonomy($node),
                'lastmod' => $node->updated_at?->toAtomString(),
            ])
            ->all();

        return $this->xml($this->urlSet($items));
    }

    public function tests(PublicUrlGenerator $urls, TenantContext $tenantContext): Response
    {
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $items = TestDefinition::query()
            ->with(['vertical', 'taxonomyNode.parent'])
            ->whereHas('vertical', fn ($query) => $query->active())
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
            ->published()
            ->orderBy('id')
            ->get()
            ->map(fn (TestDefinition $test): array => [
                'loc' => $urls->test($test),
                'lastmod' => ($test->updated_at ?? $test->published_at)?->toAtomString(),
            ])
            ->all();

        return $this->xml($this->urlSet($items));
    }

    /**
     * @param  array<int, string>  $urls
     */
    private function sitemapIndex(array $urls): string
    {
        $items = array_map(
            fn (string $url): string => '<sitemap><loc>'.$this->escape($url).'</loc></sitemap>',
            $urls,
        );

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $items)
            .'</sitemapindex>';
    }

    /**
     * @param  array<int, array{loc:string,lastmod:?string}>  $items
     */
    private function urlSet(array $items): string
    {
        $rows = array_map(function (array $item): string {
            $lastmod = $item['lastmod'] !== null
                ? '<lastmod>'.$this->escape($item['lastmod']).'</lastmod>'
                : '';

            return '<url><loc>'.$this->escape($item['loc']).'</loc>'.$lastmod.'</url>';
        }, $items);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $rows)
            .'</urlset>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xml(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
