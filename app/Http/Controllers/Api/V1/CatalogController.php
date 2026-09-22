<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Api\ApiAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $key = $this->key($request);

        return response()->json([
            'data' => [
                'client' => [
                    'id' => $key->client->id,
                    'name' => $key->client->name,
                    'tenant' => $key->client->tenant?->slug,
                ],
                'key' => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'prefix' => $key->key_prefix,
                    'scopes' => $key->scopes,
                    'daily_quota' => $key->daily_quota,
                    'monthly_quota' => $key->monthly_quota,
                    'expires_at' => $key->expires_at?->toIso8601String(),
                ],
            ],
        ]);
    }

    public function verticals(Request $request, ApiAccess $access): JsonResponse
    {
        $key = $this->key($request);

        $verticals = Vertical::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Vertical $vertical): bool => $access->allowsVertical($key, $vertical))
            ->values()
            ->map(static fn (Vertical $vertical): array => [
                'id' => $vertical->id,
                'name' => $vertical->name,
                'slug' => $vertical->slug,
                'description' => $vertical->description,
            ]);

        return response()->json(['data' => $verticals]);
    }

    public function tests(Request $request, Vertical $vertical, ApiAccess $access): JsonResponse
    {
        $key = $this->key($request);

        abort_unless($vertical->is_active && $access->allowsVertical($key, $vertical), 404);

        $tests = TestDefinition::query()
            ->where('vertical_id', $vertical->id)
            ->published()
            ->with(['vertical', 'taxonomyNode'])
            ->orderByDesc('published_at')
            ->paginate(min(100, max(1, (int) $request->integer('per_page', 25))));

        return response()->json([
            'data' => collect($tests->items())->map(fn (TestDefinition $test): array => $this->testPayload($test)),
            'meta' => [
                'current_page' => $tests->currentPage(),
                'last_page' => $tests->lastPage(),
                'per_page' => $tests->perPage(),
                'total' => $tests->total(),
            ],
        ]);
    }

    public function test(Request $request, TestDefinition $test, ApiAccess $access): JsonResponse
    {
        $key = $this->key($request);

        $test->loadMissing(['vertical', 'taxonomyNode']);
        abort_unless(
            $test->status === PublicationStatus::Published
            && $test->published_at !== null
            && $test->published_at->isPast()
            && $test->vertical->is_active
            && $access->allowsVertical($key, $test->vertical),
            404,
        );

        return response()->json([
            'data' => $this->testPayload($test),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(TestDefinition $test): array
    {
        return [
            'id' => $test->id,
            'vertical' => [
                'id' => $test->vertical_id,
                'slug' => $test->vertical?->slug,
            ],
            'taxonomy' => $test->taxonomyNode !== null
                ? [
                    'id' => $test->taxonomyNode->id,
                    'type' => $test->taxonomyNode->type->value,
                    'name' => $test->taxonomyNode->name,
                    'slug' => $test->taxonomyNode->slug,
                ]
                : null,
            'title' => $test->title,
            'slug' => $test->slug,
            'description' => $test->description,
            'instructions' => $test->instructions,
            'mode' => $test->mode->value,
            'question_limit' => $test->question_limit,
            'duration_seconds' => $test->duration_seconds,
            'passing_percentage' => $test->passing_percentage !== null
                ? (float) $test->passing_percentage
                : null,
            'randomize_questions' => $test->randomize_questions,
            'randomize_options' => $test->randomize_options,
            'allow_review' => $test->allow_review,
            'show_explanations' => $test->show_explanations,
            'published_at' => $test->published_at?->toIso8601String(),
        ];
    }

    private function key(Request $request): ApiKey
    {
        $key = $request->attributes->get('api_key');
        abort_unless($key instanceof ApiKey, 401);

        return $key;
    }
}
