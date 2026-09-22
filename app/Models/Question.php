<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property QuestionType $type
 * @property PublicationStatus $status
 * @property array<string, mixed>|null $answer_config
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $source_checked_at
 * @property Carbon|null $reviewed_at
 */
class Question extends Model
{
    protected $fillable = [
        'vertical_id',
        'taxonomy_node_id',
        'source_key',
        'type',
        'status',
        'prompt',
        'explanation',
        'difficulty',
        'source_label',
        'source_url',
        'source_checked_at',
        'answer_config',
        'metadata',
        'created_by',
        'updated_by',
        'reviewed_by',
        'reviewed_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'status' => PublicationStatus::class,
            'source_checked_at' => 'date',
            'reviewed_at' => 'datetime',
            'answer_config' => 'array',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Vertical, $this> */
    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    /** @return BelongsTo<TaxonomyNode, $this> */
    public function taxonomyNode(): BelongsTo
    {
        return $this->belongsTo(TaxonomyNode::class);
    }

    /** @return HasMany<AnswerOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(AnswerOption::class)->orderBy('position');
    }

    /** @return BelongsToMany<TestDefinition, $this> */
    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(TestDefinition::class, 'test_question', 'question_id', 'test_id')
            ->withPivot(['position', 'points', 'required', 'settings']);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
