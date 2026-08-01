<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadRequest;
use App\Jobs\ProcessCsvImport;
use App\Models\Upload;
use App\Services\ImportLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function create(): View
    {
        return view('uploads.create');
    }

    public function store(StoreUploadRequest $request): RedirectResponse
    {
        $file = $request->file('csv_file');

        // Store on the `public` disk under csv_uploads/ so it can be re-read
        // by the queue worker regardless of which process picks up the job.
        $storedPath = $file->store('csv_uploads', 'public');

        $upload = Upload::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'size_in_bytes' => $file->getSize(),
            'status' => 'pending',
        ]);

        ImportLogger::info('upload.created', "'{$upload->original_filename}' uploaded and queued for processing.", $upload);

        // Dispatch to the queue -> this is what makes processing asynchronous.
        // Run `php artisan queue:work` for this to actually get picked up.
        ProcessCsvImport::dispatch($upload);

        return redirect()
            ->route('dashboard.show', $upload)
            ->with('success', "'{$upload->original_filename}' uploaded successfully and queued for import.");
    }
}
