<?php

namespace App\Services\Seo;

use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Tenancy\TenantContext;

final readonly class StructuredData
{
    public function __construct(
        private PublicUrlGenerator $urls,
        private TenantContext $tenantContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function website(): array
    {
        $brandName = (string) $this->tenantContext->brand('site_name', 'e-test.ro');
        $description = (string) $this->tenantContext->brand(
            'seo_description',
            'Teste online gratuite pentru examene, certificări și evaluări.',
        );

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => route('home').'#website',
                    'url' => route('home'),
                    'name' => $brandName,
                    'description' => $description,
                    'inLanguage' => 'ro-RO',
                ],
                [
                    '@type' => 'Organization',
                    '@id' => route('home').'#organization',
                    'url' => route('home'),
                    'name' => $brandName,
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{name:string,url:string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                $items,
                array_keys($items),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function collection(string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'inLanguage' => 'ro-RO',
            'isAccessibleForFree' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quiz(TestDefinition $test): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Quiz',
            'name' => $test->title,
            'description' => $test->seo_description ?: $test->description ?: 'Test online gratuit.',
            'url' => $this->urls->test($test),
            'inLanguage' => 'ro-RO',
            'isAccessibleForFree' => true,
            'about' => [
                '@type' => 'Thing',
                'name' => $test->taxonomy_node_id !== null ? $test->taxonomyNode->name : $test->vertical->name,
            ],
            'provider' => [
                '@type' => 'Organization',
                'name' => (string) $this->tenantContext->brand('site_name', 'e-test.ro'),
                'url' => route('home'),
            ],
        ];
    }

    /**
     * @return array<int, array{name:string,url:string}>
     */
    public function verticalBreadcrumbs(Vertical $vertical): array
    {
        return [
            ['name' => (string) $this->tenantContext->brand('site_name', 'e-test.ro'), 'url' => route('home')],
            ['name' => $vertical->name, 'url' => $this->urls->vertical($vertical)],
        ];
    }

    /**
     * @return array<int, array{name:string,url:string}>
     */
    public function taxonomyBreadcrumbs(TaxonomyNode $node): array
    {
        $items = [
            ['name' => (string) $this->tenantContext->brand('site_name', 'e-test.ro'), 'url' => route('home')],
            ['name' => $node->vertical->name, 'url' => $this->urls->vertical($node->vertical)],
        ];

        foreach ($this->ancestors($node) as $ancestor) {
            $items[] = ['name' => $ancestor->name, 'url' => $this->urls->taxonomy($ancestor)];
        }

        $items[] = ['name' => $node->name, 'url' => $this->urls->taxonomy($node)];

        return $items;
    }

    /**
     * @return array<int, array{name:string,url:string}>
     */
    public function testBreadcrumbs(TestDefinition $test): array
    {
        $items = [
            ['name' => (string) $this->tenantContext->brand('site_name', 'e-test.ro'), 'url' => route('home')],
            ['name' => $test->vertical->name, 'url' => $this->urls->vertical($test->vertical)],
        ];

        if ($test->taxonomyNode !== null) {
            foreach ($this->ancestors($test->taxonomyNode) as $ancestor) {
                $items[] = ['name' => $ancestor->name, 'url' => $this->urls->taxonomy($ancestor)];
            }

            $items[] = [
                'name' => $test->taxonomyNode->name,
                'url' => $this->urls->taxonomy($test->taxonomyNode),
            ];
        }

        $items[] = ['name' => $test->title, 'url' => $this->urls->test($test)];

        return $items;
    }

    /**
     * @return array<int, TaxonomyNode>
     */
    private function ancestors(TaxonomyNode $node): array
    {
        $ancestors = [];
        $current = $node->parent()->first();

        while ($current !== null) {
            array_unshift($ancestors, $current);
            $current = $current->parent()->first();
        }

        return $ancestors;
    }
}
