<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `product_imports` table represents a single product row from the
     * CSV file and tracks its individual import status against Shopify.
     */
    public function up(): void
    {
        Schema::create('product_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();

            // Original CSV row number, useful for error messages ("row 4 failed")
            $table->unsignedInteger('row_number')->nullable();

            // Raw fields mapped from CSV (kept close to Shopify's own vocabulary)
            $table->string('handle')->nullable()->index();
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('published')->default(true);

            $table->string('sku')->nullable()->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedInteger('inventory_qty')->default(0);
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('weight_unit', 10)->nullable();

            $table->string('image_src')->nullable();
            $table->string('image_alt_text')->nullable();

            // Raw CSV row payload kept as a JSON snapshot, so the dashboard can
            // display exactly what was submitted even for unmapped columns.
            $table->json('raw_data')->nullable();

            // status per product row
            // pending -> processing -> successful | failed
            $table->enum('status', ['pending', 'processing', 'successful', 'failed'])
                ->default('pending');

            // "created" or "updated" - filled in once we know whether the
            // product already existed in Shopify (bonus: update existing product)
            $table->string('action')->nullable();

            $table->string('shopify_product_id')->nullable()->index();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_imports');
    }
};
