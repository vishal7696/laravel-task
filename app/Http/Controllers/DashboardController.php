<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $uploads = Upload::withCount([
            'productImports as pending_count' => fn ($q) => $q->where('status', 'pending'),
            'productImports as processing_count' => fn ($q) => $q->where('status', 'processing'),
            'productImports as successful_count' => fn ($q) => $q->where('status', 'successful'),
            'productImports as failed_count' => fn ($q) => $q->where('status', 'failed'),
        ])
            ->latest()
            ->paginate(15);

        return view('dashboard.index', compact('uploads'));
    }

    public function show(Upload $upload): View
    {
        $upload->load(['productImports' => fn ($q) => $q->orderBy('row_number')]);

        return view('dashboard.show', compact('upload'));
    }

    public function status(Upload $upload): JsonResponse
    {
        return response()->json([
            'status' => $upload->status,
            'total_rows' => $upload->total_rows,
            'processed_rows' => $upload->processed_rows,
            'successful_rows' => $upload->productImports()->where('status', 'successful')->count(),
            'failed_rows' => $upload->productImports()->where('status', 'failed')->count(),
            'progress_percent' => $upload->progress_percent,
        ]);
    }

    public function logs(): View
    {
        $logs = ImportLog::with('upload')
            ->latest()
            ->paginate(50);

        return view('dashboard.logs', compact('logs'));
    }
}
