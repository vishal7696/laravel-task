<?php $__env->startSection('title', 'Uploads'); ?>

<?php
    $statusStyles = [
        'pending' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
        'processing' => 'bg-flag/10 text-flag border-flag/30',
        'completed' => 'bg-manifest/10 text-manifest border-manifest/30',
        'failed' => 'bg-hazard/10 text-hazard border-hazard/30',
    ];
?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-100">Uploads</h1>
        <p class="text-slate-400 text-sm mt-1">Every manifest that's come through, newest first.</p>
    </div>
    <a href="<?php echo e(route('uploads.create')); ?>" class="px-4 py-2 rounded-md bg-manifest text-hull text-sm font-medium hover:bg-manifest/90">New upload</a>
</div>

<?php if($uploads->isEmpty()): ?>
    <div class="border border-dashed border-white/10 rounded-lg py-16 text-center">
        <p class="text-slate-400">No uploads yet.</p>
        <a href="<?php echo e(route('uploads.create')); ?>" class="text-manifest text-sm mt-2 inline-block">Upload your first CSV &rarr;</a>
    </div>
<?php else: ?>
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
                <?php $__currentLoopData = $uploads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $upload): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="ledger-row">
                        <td class="px-4 py-3 font-medium text-slate-200"><?php echo e($upload->original_filename); ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-mono <?php echo e($statusStyles[$upload->status]); ?>">
                                <?php echo e(strtoupper($upload->status)); ?>

                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400 font-mono"><?php echo e($upload->processed_rows); ?>/<?php echo e($upload->total_rows); ?></td>
                        <td class="px-4 py-3 text-manifest font-mono"><?php echo e($upload->successful_count); ?></td>
                        <td class="px-4 py-3 <?php echo e($upload->failed_count > 0 ? 'text-hazard' : 'text-slate-600'); ?> font-mono"><?php echo e($upload->failed_count); ?></td>
                        <td class="px-4 py-3 text-slate-500"><?php echo e($upload->created_at->diffForHumans()); ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?php echo e(route('dashboard.show', $upload)); ?>" class="text-manifest hover:underline text-xs font-medium">View &rarr;</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4"><?php echo e($uploads->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/vishal/ibt/laravel-project-task/new/resources/views/dashboard/index.blade.php ENDPATH**/ ?>