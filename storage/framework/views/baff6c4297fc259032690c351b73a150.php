<?php $__env->startSection('title', 'New upload'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <div class="text-xs font-mono uppercase tracking-widest text-manifest/80 mb-1">Step 1 of 1</div>
        <h1 class="text-2xl font-semibold text-slate-100">Load a manifest</h1>
        <p class="text-slate-400 text-sm mt-1">Upload a Shopify-formatted product CSV. It's queued for background processing the moment it lands — you don't wait for it here.</p>
    </div>

    <form action="<?php echo e(route('uploads.store')); ?>" method="POST" enctype="multipart/form-data" id="upload-form" class="bg-deck border border-white/5 rounded-lg p-6 space-y-5">
        <?php echo csrf_field(); ?>

        <div>
            <label for="csv_file" class="block text-sm font-medium text-slate-300 mb-2">CSV file</label>

            <label id="dropzone" for="csv_file" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-white/10 rounded-lg py-10 cursor-pointer hover:border-manifest/40 hover:bg-plate/40 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span id="dropzone-text" class="text-sm text-slate-400">Drop a .csv file here or <span class="text-manifest">browse</span></span>
                <span class="text-xs text-slate-600 font-mono">Max 10MB &middot; .csv only</span>
            </label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" class="hidden" required>

            <p id="client-error" class="hidden mt-2 text-sm text-hazard"></p>
        </div>

        <button type="submit" id="submit-btn" class="w-full bg-manifest text-hull font-medium rounded-md py-2.5 hover:bg-manifest/90 disabled:opacity-50 disabled:cursor-not-allowed transition">
            Upload &amp; queue import
        </button>
    </form>
</div>

<script>
    const input = document.getElementById('csv_file');
    const dropzoneText = document.getElementById('dropzone-text');
    const clientError = document.getElementById('client-error');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('upload-form');
    const MAX_BYTES = 10 * 1024 * 1024; // 10MB

    function validateFile(file) {
        if (!file) return 'Please choose a file.';
        const isCsv = file.type === 'text/csv' || file.name.toLowerCase().endsWith('.csv');
        if (!isCsv) return 'Only .csv files are accepted.';
        if (file.size > MAX_BYTES) return 'File is larger than 10MB.';
        return null;
    }

    input.addEventListener('change', () => {
        const file = input.files[0];
        const error = validateFile(file);
        if (error) {
            clientError.textContent = error;
            clientError.classList.remove('hidden');
            dropzoneText.textContent = 'Drop a .csv file here or browse';
            submitBtn.disabled = true;
        } else {
            clientError.classList.add('hidden');
            dropzoneText.textContent = `${file.name} (${(file.size / 1024).toFixed(0)} KB) selected`;
            submitBtn.disabled = false;
        }
    });

    form.addEventListener('submit', (e) => {
        const error = validateFile(input.files[0]);
        if (error) {
            e.preventDefault();
            clientError.textContent = error;
            clientError.classList.remove('hidden');
            return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading…';
    });

    // basic drag & drop support
    const dropzone = document.getElementById('dropzone');
    ['dragover', 'dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => e.preventDefault());
    });
    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/vishal/ibt/laravel-project-task/new/resources/views/uploads/create.blade.php ENDPATH**/ ?>