<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un fișier descărcat de la o sursă, păstrat așa cum a venit.
 *
 * @property array<int, array<string, mixed>>|null $rejected
 * @property Carbon $retrieved_at
 */
class SourceDocument extends Model
{
    protected $fillable = [
        'source_id',
        'content_import_id',
        'storage_path',
        'text_path',
        'mime_type',
        'sha256',
        'byte_size',
        'version_label',
        'item_count',
        'imported_count',
        'rejected_count',
        'rejected',
        'retrieved_at',
    ];

    protected function casts(): array
    {
        return [
            'rejected' => 'array',
            'retrieved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Source, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** @return BelongsTo<ContentImport, $this> */
    public function contentImport(): BelongsTo
    {
        return $this->belongsTo(ContentImport::class);
    }
}
