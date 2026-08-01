<?php $__env->startSection('title', 'Event log'); ?>

<?php
    $levelStyles = [
        'info' => 'text-slate-400',
        'warning' => 'text-flag',
        'error' => 'text-hazard',
    ];
?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-100">Event log</h1>
    <p class="text-slate-400 text-sm mt-1">Every logged import event, most recent first. Mirrors <code class="font-mono text-xs text-slate-500">storage/logs/shopify_import.log</code>.</p>
</div>

<div class="border border-white/5 rounded-lg overflow-hidden bg-deck">
    <div class="divide-y divide-white/5 font-mono text-xs">
        <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="px-4 py-3 flex items-start gap-4 ledger-row">
                <span class="text-slate-600 whitespace-nowrap"><?php echo e($log->created_at->format('H:i:s')); ?></span>
                <span class="uppercase font-semibold w-16 shrink-0 <?php echo e($levelStyles[$log->level]); ?>"><?php echo e($log->level); ?></span>
                <span class="text-slate-500 w-56 shrink-0 truncate"><?php echo e($log->event); ?></span>
                <span class="text-slate-300 flex-1"><?php echo e($log->message); ?></span>
                <?php if($log->upload): ?>
                    <a href="<?php echo e(route('dashboard.show', $log->upload)); ?>" class="text-manifest hover:underline whitespace-nowrap">#<?php echo e($log->upload_id); ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="px-4 py-10 text-center text-slate-500">No events logged yet.</div>
        <?php endif; ?>
    </div>
</div>
<div class="mt-4"><?php echo e($logs->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/vishal/ibt/laravel-project-task/new/resources/views/dashboard/logs.blade.php ENDPATH**/ ?>