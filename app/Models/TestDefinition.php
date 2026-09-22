<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\TestMode;
use Database\Factories\TestDefinitionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property TestMode $mode
 * @property PublicationStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $reviewed_at
 */
class TestDefinition extends Model
{
    /** @use HasFactory<TestDefinitionFactory> */
    use HasFactory;

    protected $table = 'tests';

    protected $fillable = [
        'vertical_id',
        'taxonomy_node_id',
        'title',
        'slug',
        'description',
        'seo_title',
        'seo_description',
        'instructions',
        'mode',
        'status',
        'question_limit',
        'duration_seconds',
        'passing_percentage',
        'randomize_questions',
        'randomize_options',
        'allow_review',
        'show_explanations',
        'metadata',
        'published_at',
        'reviewed_by',
        'reviewed_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'mode' => TestMode::class,
            'status' => PublicationStatus::class,
            'passing_percentage' => 'decimal:2',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'allow_review' => 'boolean',
            'show_explanations' => 'boolean',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    /** @return BelongsToMany<Question, $this> */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'test_question', 'test_id', 'question_id')
            ->withPivot(['position', 'points', 'required', 'settings'])
            ->orderByPivot('position');
    }

    /** @return HasMany<TestAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class, 'test_id');
    }

    /**
     * @param  Builder<TestDefinition>  $query
     * @return Builder<TestDefinition>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PublicationStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
