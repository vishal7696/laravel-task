<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `import_logs` table is a structured, queryable audit trail of
     * everything that happens during an import (bonus requirement: logging
     * + in-dashboard log viewer). This is in ADDITION to the Laravel log
     * files written to storage/logs/shopify_import.log.
     */
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_import_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('level', ['info', 'warning', 'error'])->default('info');
            $table->string('event'); // e.g. "csv.parsed", "shopify.product.created", "shopify.api.error"
            $table->text('message');
            $table->json('context')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
