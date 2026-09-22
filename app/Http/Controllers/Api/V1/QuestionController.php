<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Services\Api\ApiAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request, TestDefinition $test, ApiAccess $access): JsonResponse
    {
        $key = $this->key($request);

        $test->loadMissing('vertical');
        abort_unless(
            $test->status === PublicationStatus::Published
            && $test->published_at !== null
            && $test->published_at->isPast()
            && $test->vertical->is_active
            && $access->allowsVertical($key, $test->vertical),
            404,
        );

        $includeAnswers = $key->hasScope('answers:read');

        $questions = $test->questions()
            ->where('questions.status', PublicationStatus::Published->value)
            ->with('options')
            ->paginate(min(100, max(1, (int) $request->integer('per_page', 50))));

        return response()->json([
            'data' => collect($questions->items())
                ->map(fn (Question $question): array => $this->questionPayload($question, $includeAnswers)),
            'meta' => [
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'per_page' => $questions->perPage(),
                'total' => $questions->total(),
                'answers_included' => $includeAnswers,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(Question $question, bool $includeAnswers): array
    {
        $payload = [
            'id' => $question->id,
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'difficulty' => $question->difficulty,
            'source' => [
                'label' => $question->source_label,
                'url' => $question->source_url,
                'checked_at' => $question->source_checked_at?->toDateString(),
            ],
            'options' => $question->options->map(static fn ($option): array => [
                'id' => $option->id,
                'content' => $option->content,
                'position' => $option->position,
            ])->values(),
        ];

        if ($includeAnswers) {
            $payload['answer_config'] = $question->answer_config;
            $payload['explanation'] = $question->explanation;
            $payload['options'] = $question->options->map(static fn ($option): array => [
                'id' => $option->id,
                'content' => $option->content,
                'position' => $option->position,
                'is_correct' => $option->is_correct,
                'feedback' => $option->feedback,
            ])->values();
        }

        return $payload;
    }

    private function key(Request $request): ApiKey
    {
        $key = $request->attributes->get('api_key');
        abort_unless($key instanceof ApiKey, 401);

        return $key;
    }
}
