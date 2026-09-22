<?php

namespace App\Services\Seo;

use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;

final class PublicUrlGenerator
{
    public function vertical(Vertical $vertical): string
    {
        return route('verticals.show', $vertical);
    }

    public function taxonomy(TaxonomyNode $node): string
    {
        return url($node->vertical->slug.'/'.$this->taxonomyPath($node));
    }

    public function test(TestDefinition $test): string
    {
        $segments = [$test->vertical->slug];

        if ($test->taxonomyNode !== null) {
            $segments[] = $this->taxonomyPath($test->taxonomyNode);
        }

        $segments[] = $test->slug;

        return url(implode('/', $segments));
    }

    public function taxonomyPath(TaxonomyNode $node): string
    {
        $segments = [];
        $current = $node;

        while ($current !== null) {
            array_unshift($segments, $current->slug);
            $current = $current->parent()->first();
        }

        return implode('/', $segments);
    }
}
