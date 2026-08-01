<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'row_number',
        'handle',
        'title',
        'body_html',
        'vendor',
        'product_type',
        'tags',
        'published',
        'sku',
        'price',
        'compare_at_price',
        'inventory_qty',
        'weight',
        'weight_unit',
        'image_src',
        'image_alt_text',
        'raw_data',
        'status',
        'action',
        'shopify_product_id',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'raw_data' => 'array',
        ];
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function markSuccessful(string $shopifyProductId, string $action): void
    {
        $this->update([
            'status' => 'successful',
            'shopify_product_id' => $shopifyProductId,
            'action' => $action,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
