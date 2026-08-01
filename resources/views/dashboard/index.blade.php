@extends('layouts.app')

@section('title', 'Uploads')

@php
    $statusStyles = [
        'pending' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
        'processing' => 'bg-flag/10 text-flag border-flag/30',
        'completed' => 'bg-manifest/10 text-manifest border-manifest/30',
        'failed' => 'bg-hazard/10 text-hazard border-hazard/30',
    ];
@endphp

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-100">Uploads</h1>
        <p class="text-slate-400 text-sm mt-1">Every manifest that's come through, newest first.</p>
    </div>
    <a href="{{ route('uploads.create') }}" class="px-4 py-2 rounded-md bg-manifest text-hull text-sm font-medium hover:bg-manifest/90">New upload</a>
</div>

@if ($uploads->isEmpty())
    <div class="border border-dashed border-white/10 rounded-lg py-16 text-center">
        <p class="text-slate-400">No uploads yet.</p>
        <a href="{{ route('uploads.create') }}" class="text-manifest text-sm mt-2 inline-block">Upload your first CSV &rarr;</a>
    </div>
@else
    <div class="border border-white/5 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-plate text-slate-400 text-xs uppercase tracking-wider font-mono">
                <tr>
                    <th class="text-left px-4 py-3">File</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Progress</th>
                    <th class="text-left px-4 py-3">Success</th>
                    <th class="text-left px-4 py-3">Failed</th>
                    <th class="text-left px-4 py-3">Uploaded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach ($uploads as $upload)
                    <tr class="ledger-row">
                        <td class="px-4 py-3 font-medium text-slate-200">{{ $upload->original_filename }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-mono {{ $statusStyles[$upload->status] }}">
                                {{ strtoupper($upload->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400 font-mono">{{ $upload->processed_rows }}/{{ $upload->total_rows }}</td>
                        <td class="px-4 py-3 text-manifest font-mono">{{ $upload->successful_count }}</td>
                        <td class="px-4 py-3 {{ $upload->failed_count > 0 ? 'text-hazard' : 'text-slate-600' }} font-mono">{{ $upload->failed_count }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $upload->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('dashboard.show', $upload) }}" class="text-manifest hover:underline text-xs font-medium">View &rarr;</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $uploads->links() }}</div>
@endif
@endsection
