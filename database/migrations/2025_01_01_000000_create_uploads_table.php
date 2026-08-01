<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `uploads` table represents a single CSV file uploaded by a user.
     * One Upload has many ProductImport rows (one per CSV row/product).
     */
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path'); // path on the `local`/`public` disk
            $table->unsignedBigInteger('size_in_bytes')->default(0);

            // Overall status of the whole upload/batch.
            // pending    -> uploaded, waiting for the queue worker
            // processing -> job has started reading/importing rows
            // completed  -> job finished (some rows may still have failed - see counts)
            // failed     -> the job itself blew up before finishing
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            $table->text('error_message')->nullable(); // top-level failure reason, if any
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
