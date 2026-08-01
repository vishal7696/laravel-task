<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'upload_id',
        'product_import_id',
        'level',
        'event',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function productImport(): BelongsTo
    {
        return $this->belongsTo(ProductImport::class);
    }
}
