<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Models\Upload;
use App\Notifications\ImportFailedNotification;
use App\Services\ImportLogger;
use App\Services\ShopifyGraphQLService;
use App\Services\ShopifyRestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use League\Csv\Reader;
use Throwable;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public Upload $upload)
    {
    }

    public function handle(ShopifyRestService $restService, ShopifyGraphQLService $graphQLService): void
    {
        $this->upload->markAsProcessing();
        ImportLogger::info('upload.processing.started', "Started processing '{$this->upload->original_filename}'", $this->upload);

        try {
            $csvPath = storage_path('app/public/'.$this->upload->stored_path);
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            $records = [...$csv->getRecords()];
            $this->upload->update(['total_rows' => count($records)]);

            $useGraphQl = config('services.shopify.api_mode') === 'graphql';

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2; // +2: header row + 1-indexing
                $row = $this->createProductImportRow($record, $rowNumber);

                $this->processRow($row, $useGraphQl ? $graphQLService : $restService);

                $this->upload->increment('processed_rows');
            }

            $this->upload->refresh();
            $this->upload->update([
                'successful_rows' => $this->upload->productImports()->where('status', 'successful')->count(),
                'failed_rows' => $this->upload->productImports()->where('status', 'failed')->count(),
            ]);
            $this->upload->markAsCompleted();

            ImportLogger::info(
                'upload.processing.completed',
                "Finished processing '{$this->upload->original_filename}': {$this->upload->successful_rows} succeeded, {$this->upload->failed_rows} failed.",
                $this->upload
            );

            if ($this->upload->failed_rows > 0) {
                $this->notifyOfFailures();
            }
        } catch (Throwable $e) {
            $this->upload->markAsFailed($e->getMessage());
            ImportLogger::error('upload.processing.failed', $e->getMessage(), $this->upload);
            $this->notifyOfFailures($e->getMessage());

            throw $e;
        }
    }

    protected function createProductImportRow(array $record, int $rowNumber): ProductImport
    {
        $get = fn (string $key) => trim((string) ($record[$key] ?? ''));

        return $this->upload->productImports()->create([
            'row_number' => $rowNumber,
            'handle' => $get('Handle') ?: null,
            'title' => $get('Title') ?: 'Untitled Product',
            'body_html' => $get('Body HTML') ?: null,
            'vendor' => $get('Vendor') ?: null,
            'product_type' => $get('Product Type') ?: null,
            'tags' => $get('Tags') ?: null,
            'published' => strtoupper($get('Published')) !== 'FALSE',
            'sku' => $get('Variant SKU') ?: null,
            'price' => $get('Variant Price') !== '' ? (float) $get('Variant Price') : null,
            'compare_at_price' => $get('Variant Compare At Price') !== '' ? (float) $get('Variant Compare At Price') : null,
            'inventory_qty' => $get('Variant Inventory Qty') !== '' ? (int) $get('Variant Inventory Qty') : 0,
            'weight' => $get('Variant Weight') !== '' ? (float) $get('Variant Weight') : null,
            'weight_unit' => $get('Variant Weight Unit') ?: null,
            'image_src' => $get('Image Src') ?: null,
            'image_alt_text' => $get('Image Alt Text') ?: null,
            'raw_data' => $record,
            'status' => 'pending',
        ]);
    }

    protected function processRow(ProductImport $row, ShopifyRestService|ShopifyGraphQLService $service): void
    {
        $row->update(['status' => 'processing']);

        if (blank($row->title)) {
            $row->markFailed('Row is missing a required "Title" value.');
            ImportLogger::warning('row.validation.failed', "Row {$row->row_number} skipped: missing title.", $this->upload, $row);

            return;
        }

        try {
            $result = $service->createOrUpdateProduct($row);
            $row->markSuccessful($result['shopify_product_id'], $result['action']);

            ImportLogger::info(
                'shopify.product.'.$result['action'],
                "Row {$row->row_number} ({$row->title}) {$result['action']} in Shopify as product {$result['shopify_product_id']}.",
                $this->upload,
                $row
            );
        } catch (Throwable $e) {
            $row->markFailed($e->getMessage());
            ImportLogger::error('shopify.api.error', "Row {$row->row_number} ({$row->title}) failed: {$e->getMessage()}", $this->upload, $row);
        }
    }

    protected function notifyOfFailures(?string $fatalMessage = null): void
    {
        // Simple in-app/log notification. Swap the `route('log')` notifiable
        // for a real User/Slack/Mail channel in production.
        Notification::route('log', 'admin')
            ->notify(new ImportFailedNotification($this->upload, $fatalMessage));
    }

    public function failed(Throwable $exception): void
    {
        $this->upload->markAsFailed($exception->getMessage());
        ImportLogger::error('upload.job.failed', $exception->getMessage(), $this->upload);
    }
}
