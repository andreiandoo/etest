<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * O sursă oficială de conținut.
 *
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $last_synced_at
 */
class Source extends Model
{
    protected $fillable = [
        'key',
        'authority',
        'title',
        'exam',
        'specialty',
        'vertical_id',
        'taxonomy_node_id',
        'source_page_url',
        'document_url',
        'license_url',
        'version_label',
        'file_format',
        'rights_status',
        'answer_key',
        'explanations',
        'import_difficulty',
        'review_status',
        'count_status',
        'item_count_raw',
        'notes',
        'last_synced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
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

    /** @return HasMany<SourceDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(SourceDocument::class)->orderByDesc('retrieved_at');
    }

    /**
     * Ultima versiune descărcată, ca să putem compara amprenta înainte de a
     * mai importa o dată același fișier.
     */
    public function latestDocument(): ?SourceDocument
    {
        return $this->documents()->first();
    }
}
