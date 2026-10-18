@extends('layouts.app')

@section('title', 'Telegram Settings - SIATRACK')

@push('styles')
<style>
    html.sidebar-collapsed .telegram-list-modal {
        left: 5rem;
    }
</style>
@endpush

@section('content')
<div class="flex min-h-screen w-full flex-col bg-slate-50">
    <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 shadow-xs lg:px-10">
        <div class="flex items-center gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#590d0d] text-base text-amber-300 shadow-xs">
                <i class="fa-brands fa-telegram"></i>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-900">Telegram Settings</h1>
                <p class="mt-0.5 text-xs font-bold text-slate-500">Parent links, student codes, and notification history</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <span class="hidden items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold sm:flex {{ $botEnabled ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                <span class="h-2 w-2 rounded-full {{ $botEnabled ? 'animate-pulse bg-emerald-500' : 'bg-rose-500' }}"></span>
                {{ $botEnabled ? 'Bot Enabled' : 'Bot Disabled' }}
            </span>
            @if($botEnabled)
                <form id="telegram-test-form" method="POST" action="{{ route('admin.telegram.test-connection') }}">
                    @csrf
                    <button id="telegram-test-button" type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#8b1818] px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-[#731414] disabled:cursor-wait disabled:opacity-70">
                        <i class="fa-solid fa-plug-circle-check"></i>
                        <span>Test Connection</span>
                    </button>
                </form>
            @endif
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1600px] flex-1 space-y-5 p-5 lg:px-10 lg:py-7">
        <div id="telegram-feedback" class="hidden rounded-xl border p-3 text-sm font-semibold" role="status" aria-live="polite"></div>
        <div id="telegram-admin-content" aria-live="polite">
            @include('admin.telegram.partials.content')
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

    const activeListModalId = () => content.querySelector('[data-list-modal]:not(.hidden)')?.id;

    const openListModal = id => {
        const modal = content.querySelector(`#${id}`);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeListModal = modal => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!content.querySelector('[data-list-modal]:not(.hidden)')) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    const refreshContent = async (url = window.location.href, updateHistory = true, reopenModalId = activeListModalId()) => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        content.setAttribute('aria-busy', 'true');

        // Only dim the scrollable table area inside the active modal, not the whole content
        // div (which contains the modal backdrop itself), to prevent the flicker.
        const activeModal = reopenModalId ? content.querySelector(`#${reopenModalId}`) : null;
        const dimTarget = activeModal?.querySelector('.overflow-auto') ?? content;
        dimTarget.classList.add('opacity-60', 'pointer-events-none');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: activeRequest.signal
            });
            if (!response.ok) throw new Error('Could not update Telegram records.');
            content.innerHTML = await response.text();
            // Immediately reopen the modal before any paint so there's no visible flicker.
            if (reopenModalId) openListModal(reopenModalId);
            if (updateHistory) window.history.replaceState({}, '', url);
        } finally {
            content.removeAttribute('aria-busy');
            // dimTarget was replaced by innerHTML swap, remove from new element if still present.
            content.querySelector('.opacity-60.pointer-events-none')
                ?.classList.remove('opacity-60', 'pointer-events-none');
        }
    };

    const filterUrl = form => {
        const url = new URL(window.location.href);
        const prefix = form.id === 'student-link-filters' ? 'student_' : 'parent_';
        new FormData(form).forEach((value, key) => {
            const normalizedValue = String(value).trim();
            if (normalizedValue) url.searchParams.set(key, normalizedValue);
            else url.searchParams.delete(key);
        });
        url.searchParams.delete(form.id === 'student-link-filters' ? 'student_codes_page' : 'parent_links_page');
        url.searchParams.delete('notifications_page');
        if (prefix === 'student_') url.searchParams.delete('codes_page');
        return url.toString();
    };

    const updateFilters = form => refreshContent(filterUrl(form), true, form.closest('[data-list-modal]').id).catch(error => {
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

    const regeneratedTelegramCode = @json(session('regeneratedTelegramCode'));
    if (regeneratedTelegramCode?.success) {
        activeCode = regeneratedTelegramCode.code;
        document.getElementById('telegram-code-student').textContent = regeneratedTelegramCode.student_name;
        document.getElementById('telegram-code-value').textContent = activeCode;
        document.getElementById('telegram-code-loading').classList.add('hidden');
        document.getElementById('telegram-code-result').classList.remove('hidden');
        codeModal.classList.remove('hidden');
        codeModal.classList.add('flex');
        showMessage(regeneratedTelegramCode.message, true);
    }

    content.addEventListener('click', async event => {
        if (!(event.target instanceof Element)) return;
        const openButton = event.target.closest('[data-open-list-modal]');
        if (openButton) {
            const modalId = openButton.dataset.openListModal;
            openListModal(modalId);
            if (openButton.dataset.studentLinkStatus) {
                const form = content.querySelector('#student-link-filters');
                form.elements.student_status.value = openButton.dataset.studentLinkStatus;
                updateFilters(form);
            }
            return;
        }
        const closeButton = event.target.closest('[data-close-list-modal]');
        if (closeButton) {
            closeListModal(closeButton.closest('[data-list-modal]'));
            return;
        }
        const modalBackdrop = event.target.closest('[data-list-modal]');
        if (modalBackdrop && event.target === modalBackdrop) {
            closeListModal(modalBackdrop);
            return;
        }

        const clearButton = event.target.closest('[data-clear-modal-filters]');
        if (clearButton) {
            const form = clearButton.closest('form');
            form.reset();
            updateFilters(form);
            return;
        }

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
                    'X-CSRF-TOKEN': @json(csrf_token()),
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
        const form = event.target.closest('#student-link-filters, #parent-link-filters');
        if (form && event.target.matches('input')) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => updateFilters(form), 300);
        }
    });

    content.addEventListener('submit', event => {
        if (!event.target.matches('#student-link-filters, #parent-link-filters')) return;
        event.preventDefault();
        clearTimeout(searchTimer);
        updateFilters(event.target);
    });

    content.addEventListener('change', event => {
        const form = event.target.closest('#student-link-filters, #parent-link-filters');
        if (form && event.target.matches('select')) {
            updateFilters(form);
        }
    });

    content.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        const link = event.target.closest('.telegram-pagination a[href]');
        if (!link) return;
        event.preventDefault();
        refreshContent(link.href, true, activeListModalId()).catch(error => {
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
        const modalId = activeListModalId();
        refreshContent(window.location.href, false).catch(error => {
            if (error.name !== 'AbortError') showMessage(error.message, false);
        });
        if (!modalId) document.body.classList.remove('overflow-hidden');
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const modal = content.querySelector('[data-list-modal]:not(.hidden)');
        if (modal) closeListModal(modal);
    });
})();
</script>
@endsection
