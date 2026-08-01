<?php

namespace App\Services;

use App\Models\ImportLog;
use App\Models\ProductImport;
use App\Models\Upload;
use Illuminate\Support\Facades\Log;

class ImportLogger
{
    public static function info(string $event, string $message, ?Upload $upload = null, ?ProductImport $productImport = null, array $context = []): void
    {
        self::write('info', $event, $message, $upload, $productImport, $context);
    }

    public static function warning(string $event, string $message, ?Upload $upload = null, ?ProductImport $productImport = null, array $context = []): void
    {
        self::write('warning', $event, $message, $upload, $productImport, $context);
    }

    public static function error(string $event, string $message, ?Upload $upload = null, ?ProductImport $productImport = null, array $context = []): void
    {
        self::write('error', $event, $message, $upload, $productImport, $context);
    }

    protected static function write(string $level, string $event, string $message, ?Upload $upload, ?ProductImport $productImport, array $context): void
    {
        ImportLog::create([
            'upload_id' => $upload?->id,
            'product_import_id' => $productImport?->id,
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context' => $context,
        ]);

        Log::channel('shopify_import')->{$level}($event.': '.$message, array_merge($context, [
            'upload_id' => $upload?->id,
            'product_import_id' => $productImport?->id,
        ]));
    }
}
