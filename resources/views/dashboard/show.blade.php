@extends('layouts.app')

@section('title', $upload->original_filename)

@php
    $rowStyles = [
        'pending' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
        'processing' => 'bg-flag/10 text-flag border-flag/30',
        'successful' => 'bg-manifest/10 text-manifest border-manifest/30',
        'failed' => 'bg-hazard/10 text-hazard border-hazard/30',
    ];
@endphp

@section('content')
<div class="mb-6">
    <a href="{{ route('dashboard.index') }}" class="text-xs text-slate-500 hover:text-slate-300">&larr; All uploads</a>
    <h1 class="text-2xl font-semibold text-slate-100 mt-2">{{ $upload->original_filename }}</h1>
    <p class="text-slate-500 text-sm font-mono mt-1">Uploaded {{ $upload->created_at->format('d M Y, H:i') }}</p>
</div>

<div id="progress-panel" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
    <div class="bg-deck border border-white/5 rounded-lg p-4">
        <div class="text-xs text-slate-500 uppercase tracking-wider font-mono">Status</div>
        <div id="stat-status" class="text-lg font-semibold text-slate-100 mt-1">{{ strtoupper($upload->status) }}</div>
    </div>
    <div class="bg-deck border border-white/5 rounded-lg p-4">
        <div class="text-xs text-slate-500 uppercase tracking-wider font-mono">Progress</div>
        <div id="stat-progress" class="text-lg font-semibold text-slate-100 mt-1">{{ $upload->processed_rows }}/{{ $upload->total_rows }}</div>
    </div>
    <div class="bg-deck border border-white/5 rounded-lg p-4">
        <div class="text-xs text-slate-500 uppercase tracking-wider font-mono">Successful</div>
        <div id="stat-success" class="text-lg font-semibold text-manifest mt-1">{{ $upload->productImports->where('status', 'successful')->count() }}</div>
    </div>
    <div class="bg-deck border border-white/5 rounded-lg p-4">
        <div class="text-xs text-slate-500 uppercase tracking-wider font-mono">Failed</div>
        <div id="stat-failed" class="text-lg font-semibold text-hazard mt-1">{{ $upload->productImports->where('status', 'failed')->count() }}</div>
    </div>
</div>

@if ($upload->error_message)
    <div class="mb-6 rounded-md border border-hazard/30 bg-hazard/10 text-hazard px-4 py-3 text-sm font-mono">
        {{ $upload->error_message }}
    </div>
@endif

<div class="border border-white/5 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-plate text-slate-400 text-xs uppercase tracking-wider font-mono">
            <tr>
                <th class="text-left px-4 py-3">Row</th>
                <th class="text-left px-4 py-3">Title</th>
                <th class="text-left px-4 py-3">SKU</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Action</th>
                <th class="text-left px-4 py-3">Shopify ID</th>
                <th class="text-left px-4 py-3">Error</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @foreach ($upload->productImports as $row)
                <tr class="ledger-row">
                    <td class="px-4 py-3 text-slate-500 font-mono">{{ $row->row_number }}</td>
                    <td class="px-4 py-3 text-slate-200">{{ $row->title }}</td>
                    <td class="px-4 py-3 text-slate-400 font-mono">{{ $row->sku ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-mono {{ $rowStyles[$row->status] }}">
                            {{ strtoupper($row->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-400 capitalize">{{ $row->action ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $row->shopify_product_id ?: '—' }}</td>
                    <td class="px-4 py-3 text-hazard text-xs max-w-xs truncate" title="{{ $row->error_message }}">{{ $row->error_message ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    // Poll the status endpoint every 3s while the upload is still in flight,
    // so the dashboard reflects the queue worker's progress without a manual refresh.
    const uploadId = {{ $upload->id }};
    const isDone = {{ in_array($upload->status, ['completed', 'failed']) ? 'true' : 'false' }};

    if (!isDone) {
        const poll = setInterval(async () => {
            const res = await fetch(`/dashboard/uploads/${uploadId}/status`);
            const data = await res.json();
            document.getElementById('stat-status').textContent = data.status.toUpperCase();
            document.getElementById('stat-progress').textContent = `${data.processed_rows}/${data.total_rows}`;
            document.getElementById('stat-success').textContent = data.successful_rows;
            document.getElementById('stat-failed').textContent = data.failed_rows;

            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(poll);
                location.reload();
            }
        }, 3000);
    }
</script>
@endsection
