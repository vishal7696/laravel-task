@extends('layouts.app')

@section('title', 'Event log')

@php
    $levelStyles = [
        'info' => 'text-slate-400',
        'warning' => 'text-flag',
        'error' => 'text-hazard',
    ];
@endphp

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-100">Event log</h1>
    <p class="text-slate-400 text-sm mt-1">Every logged import event, most recent first. Mirrors <code class="font-mono text-xs text-slate-500">storage/logs/shopify_import.log</code>.</p>
</div>

<div class="border border-white/5 rounded-lg overflow-hidden bg-deck">
    <div class="divide-y divide-white/5 font-mono text-xs">
        @forelse ($logs as $log)
            <div class="px-4 py-3 flex items-start gap-4 ledger-row">
                <span class="text-slate-600 whitespace-nowrap">{{ $log->created_at->format('H:i:s') }}</span>
                <span class="uppercase font-semibold w-16 shrink-0 {{ $levelStyles[$log->level] }}">{{ $log->level }}</span>
                <span class="text-slate-500 w-56 shrink-0 truncate">{{ $log->event }}</span>
                <span class="text-slate-300 flex-1">{{ $log->message }}</span>
                @if ($log->upload)
                    <a href="{{ route('dashboard.show', $log->upload) }}" class="text-manifest hover:underline whitespace-nowrap">#{{ $log->upload_id }}</a>
                @endif
            </div>
        @empty
            <div class="px-4 py-10 text-center text-slate-500">No events logged yet.</div>
        @endforelse
    </div>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
