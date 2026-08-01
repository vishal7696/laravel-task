<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'stored_path',
        'size_in_bytes',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function productImports(): HasMany
    {
        return $this->hasMany(ProductImport::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    /**
     * Percentage of rows processed so far, for the dashboard progress bar.
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'finished_at' => now(),
        ]);
    }
}
