

<?php $__env->startSection('title', 'Telegram Parent Notifications - SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex min-h-screen w-full flex-col bg-slate-50">
    <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 shadow-xs lg:px-10">
        <div class="flex items-center gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#590d0d] text-base text-amber-300 shadow-xs">
                <i class="fa-brands fa-telegram"></i>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-900">Telegram Parent Notifications</h1>
                <p class="mt-0.5 text-xs font-bold text-slate-500">Parent account links & notification delivery history</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <span class="hidden items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold sm:flex <?php echo e($botEnabled ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-700'); ?>">
                <span class="h-2 w-2 rounded-full <?php echo e($botEnabled ? 'animate-pulse bg-emerald-500' : 'bg-rose-500'); ?>"></span>
                <?php echo e($botEnabled ? 'Bot Enabled' : 'Bot Disabled'); ?>

            </span>
            <?php if($botEnabled): ?>
                <form id="telegram-test-form" method="POST" action="<?php echo e(route('admin.telegram.test-connection')); ?>">
                    <?php echo csrf_field(); ?>
                    <button id="telegram-test-button" type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#8b1818] px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-[#731414] disabled:cursor-wait disabled:opacity-70">
                        <i class="fa-solid fa-plug-circle-check"></i>
                        <span>Test Connection</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1600px] flex-1 space-y-5 p-5 lg:px-10 lg:py-7">
        <div id="telegram-feedback" class="hidden rounded-xl border p-3 text-sm font-semibold" role="status" aria-live="polite"></div>
        <div id="telegram-admin-content" aria-live="polite">
            <?php echo $__env->make('admin.telegram.partials.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </main>
</div>

<div id="telegram-code-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-labelledby="telegram-code-modal-title">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="telegram-code-modal-title" class="text-lg font-black text-slate-900">Student linking code</h2>
                <p id="telegram-code-student" class="mt-1 text-sm font-semibold text-slate-600"></p>
            </div>
            <button type="button" id="close-telegram-code-modal" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="telegram-code-loading" class="mt-5 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-600">
            <i class="fa-solid fa-spinner animate-spin text-[#8b1818]"></i> Generating a new code...
        </div>
        <div id="telegram-code-result" class="mt-5 hidden">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-800">New school-issued code</p>
                <code id="telegram-code-value" class="mt-2 block select-all text-2xl font-black tracking-[0.2em] text-slate-900"></code>
            </div>
            <p class="mt-3 text-xs font-semibold text-amber-800">The student’s previous code is now invalid. Give this code only to their guardian.</p>
            <button type="button" id="copy-telegram-code" class="mt-4 w-full rounded-xl bg-[#590d0d] px-4 py-2.5 text-sm font-bold text-white">
                <i class="fa-solid fa-copy mr-1"></i> Copy code
            </button>
        </div>
        <div id="telegram-code-error" class="mt-5 hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800" role="alert"></div>
    </div>
</div>

<script>
(() => {
    const content = document.getElementById('telegram-admin-content');
    const feedback = document.getElementById('telegram-feedback');
    const testForm = document.getElementById('telegram-test-form');
    const codeModal = document.getElementById('telegram-code-modal');
    let activeCode = '';
    let activeRequest;
    let searchTimer;

    const showMessage = (message, success) => {
        feedback.textContent = message;
        feedback.className = `rounded-xl border p-3 text-sm font-semibold ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'}`;
        feedback.classList.remove('hidden');
    };

    const refreshContent = async (url = window.location.href, updateHistory = true) => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        content.setAttribute('aria-busy', 'true');
        content.classList.add('opacity-60');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: activeRequest.signal
            });
            if (!response.ok) throw new Error('Could not update Telegram records.');
            content.innerHTML = await response.text();
            if (updateHistory) window.history.replaceState({}, '', url);
        } finally {
            content.removeAttribute('aria-busy');
            content.classList.remove('opacity-60');
        }
    };

    const filterUrl = () => {
        const form = content.querySelector('#telegram-link-filters');
        const url = new URL(form.action, window.location.origin);
        new FormData(form).forEach((value, key) => {
            const normalizedValue = String(value).trim();
            if (normalizedValue) url.searchParams.set(key, normalizedValue);
            else url.searchParams.delete(key);
        });
        url.searchParams.delete('page');
        url.searchParams.delete('notifications_page');
        return url.toString();
    };

    const updateFilters = () => refreshContent(filterUrl()).catch(error => {
        if (error.name !== 'AbortError') showMessage(error.message, false);
    });

    const closeCodeModal = () => {
        codeModal.classList.add('hidden');
        codeModal.classList.remove('flex');
    };

    document.getElementById('close-telegram-code-modal').addEventListener('click', closeCodeModal);
    codeModal.addEventListener('click', event => {
        if (event.target === codeModal) closeCodeModal();
    });

    content.addEventListener('click', async event => {
        const button = event.target.closest('[data-regenerate-code]');
        if (!button) return;

        const studentId = button.dataset.regenerateCode;
        const studentName = button.dataset.studentName || 'Student';
        const loading = document.getElementById('telegram-code-loading');
        const result = document.getElementById('telegram-code-result');
        const error = document.getElementById('telegram-code-error');
        const value = document.getElementById('telegram-code-value');
        document.getElementById('telegram-code-student').textContent = studentName;
        loading.classList.remove('hidden');
        result.classList.add('hidden');
        error.classList.add('hidden');
        codeModal.classList.remove('hidden');
        codeModal.classList.add('flex');
        button.disabled = true;

        try {
            const response = await fetch(`/admin/telegram/students/${encodeURIComponent(studentId)}/link-code`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': <?php echo json_encode(csrf_token(), 15, 512) ?>,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Could not regenerate the student code.');
            activeCode = data.code;
            value.textContent = activeCode;
            result.classList.remove('hidden');
            await refreshContent();
        } catch (requestError) {
            error.textContent = requestError.message || 'Could not regenerate the student code.';
            error.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
            button.disabled = false;
        }
    });

    document.getElementById('copy-telegram-code').addEventListener('click', async event => {
        try {
            await navigator.clipboard.writeText(activeCode);
            event.currentTarget.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Copied';
        } catch {
            showMessage('Copy is unavailable in this browser. Select the displayed code and copy it manually.', false);
        }
    });

    testForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const button = document.getElementById('telegram-test-button');
        const icon = button.querySelector('i');
        const label = button.querySelector('span');
        button.disabled = true;
        icon.className = 'fa-solid fa-spinner animate-spin';
        label.textContent = 'Testing connection...';
        try {
            const response = await fetch(testForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(testForm)
            });
            const result = await response.json();
            showMessage(result.message || 'Connection test failed.', response.ok && result.success);
        } catch (error) {
            showMessage(error.message || 'Connection test failed.', false);
        } finally {
            button.disabled = false;
            icon.className = 'fa-solid fa-plug-circle-check';
            label.textContent = 'Test Connection';
        }
    });

    content.addEventListener('input', event => {
        if (event.target.matches('#telegram-link-filters [name="search"]')) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(updateFilters, 300);
        }
    });

    content.addEventListener('submit', event => {
        if (!event.target.matches('#telegram-link-filters')) return;
        event.preventDefault();
        clearTimeout(searchTimer);
        updateFilters();
    });

    content.addEventListener('change', event => {
        if (event.target.matches('#telegram-link-filters select')) {
            if (event.target.name === 'grade') {
                const sectionSelect = content.querySelector('#telegram-link-filters [name="section"]');
                if (sectionSelect) sectionSelect.value = '';
            }
            updateFilters();
        }
    });

    content.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        if (event.target.closest('#clear-telegram-filters')) {
            event.preventDefault();
            refreshContent(new URL(window.location.pathname, window.location.origin).toString())
                .catch(error => showMessage(error.message, false));
            return;
        }
        const link = event.target.closest('.telegram-pagination a[href]');
        if (!link) return;
        event.preventDefault();
        refreshContent(link.href).catch(error => {
            if (error.name !== 'AbortError') showMessage(error.message, false);
        });
    });

    content.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-telegram-ajax]');
        if (!form) return;
        event.preventDefault();
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
        try {
            const response = await fetch(form.action, {
                method: form.method,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form)
            });
            const result = await response.json();
            showMessage(result.message || 'The request could not be completed.', response.ok && result.success);
            if (response.ok && result.success) await refreshContent();
        } catch (error) {
            showMessage(error.message || 'The request could not be completed.', false);
        }
    });

    window.addEventListener('popstate', () => {
        refreshContent(window.location.href, false).catch(error => {
            if (error.name !== 'AbortError') showMessage(error.message, false);
        });
    });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/admin/telegram/index.blade.php ENDPATH**/ ?>