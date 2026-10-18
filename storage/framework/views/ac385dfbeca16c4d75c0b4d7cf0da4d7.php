

<?php $__env->startSection('title', 'Absence Notifications | SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex min-h-screen w-full flex-col bg-slate-50/70" x-data="telegramDispatch()">
    <header class="sticky top-0 z-20 flex w-full flex-col justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 shadow-xs md:flex-row md:items-center lg:px-10">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#590d0d] text-base text-amber-300 shadow-xs">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Absence Notifications</h1>
                <p class="mt-0.5 text-xs font-bold text-slate-500">Send absence and custom messages through linked Telegram accounts</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if($isAdmin): ?>
                <button type="button" @click="templateOpen = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-black text-slate-700 hover:bg-slate-200">
                    <i class="fa-solid fa-pen-to-square"></i> Absence template
                </button>
                <a href="<?php echo e(route('admin.telegram.index')); ?>"
                   class="inline-flex items-center gap-2 rounded-xl bg-[#590d0d] px-3.5 py-2 text-xs font-black text-amber-300 hover:bg-[#43090c]">
                    <i class="fa-solid fa-link"></i> Manage Telegram links
                </a>
            <?php endif; ?>
        </div>
    </header>

<main class="mx-auto w-full max-w-[1700px] flex-1 space-y-6 p-6 lg:p-10">
    <?php if(session('success')): ?>
        <div class="rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-4 text-sm font-bold text-emerald-900" role="status"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="rounded-xl border-l-4 border-rose-500 bg-rose-50 p-4 text-sm font-bold text-rose-900" role="alert"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <div x-show="result" x-cloak class="rounded-xl border p-4 text-sm font-semibold"
         :class="result?.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
         role="status" aria-live="polite">
        <span x-text="result?.message"></span>
    </div>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <article class="rounded-2xl border-2 border-slate-200 bg-white p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">Telegram notifications</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500" x-text="'For ' + absenceDate"></p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-800">
                    <i class="fa-solid fa-paper-plane"></i>
                </span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-emerald-50 p-3">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Sent</p>
                    <p class="mt-1 text-2xl font-black text-emerald-900" x-text="dashboard.sent"></p>
                </div>
                <div class="rounded-xl bg-rose-50 p-3">
                    <p class="text-[10px] font-black uppercase tracking-wider text-rose-700">Failed</p>
                    <p class="mt-1 text-2xl font-black text-rose-900" x-text="dashboard.failed"></p>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold text-slate-500">
                <span class="font-black text-slate-700" x-text="dashboard.not_sent"></span> linked absent student(s) not yet notified. Failed deliveries can be retried.
            </p>
        </article>

        <article class="rounded-2xl border-2 border-slate-200 bg-white p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">Absent students</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">For the selected date</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-800">
                    <i class="fa-solid fa-link"></i>
                </span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-rose-50 p-3">
                    <p class="text-[10px] font-black uppercase tracking-wider text-rose-700">Absent</p>
                    <p class="mt-1 text-2xl font-black text-rose-900" x-text="dashboard.absent"></p>
                </div>
                <button type="button" @click="unlinkedOpen = true" :disabled="dashboard.unlinked === 0"
                        class="rounded-xl p-3 text-left transition"
                        :class="dashboard.unlinked > 0 ? 'bg-amber-50 hover:bg-amber-100' : 'bg-slate-100'">
                    <p class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider"
                       :class="dashboard.unlinked > 0 ? 'text-amber-800' : 'text-slate-600'">
                        <span x-show="dashboard.unlinked > 0" aria-hidden="true">(!)</span> Unlinked
                    </p>
                    <p class="mt-1 text-2xl font-black" :class="dashboard.unlinked > 0 ? 'text-amber-900' : 'text-slate-800'" x-text="dashboard.unlinked"></p>
                    <span x-show="dashboard.unlinked > 0" class="text-[10px] font-bold text-amber-800">Click to view names</span>
                </button>
                <div class="col-span-2 flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Linked</p>
                    <p class="text-lg font-black text-emerald-900" x-text="dashboard.linked"></p>
                </div>
            </div>
        </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">Absent students by section</h2>
                <p class="mt-1 text-xs text-slate-500">Choose which sections to notify. Successful alerts are skipped; failed alerts can be retried.</p>
                <div class="mt-2 flex gap-3 text-xs font-bold">
                    <button type="button" @click="selectedSections = dashboard.sections.map(section => section.name)" class="text-[#8b1818] hover:underline">Select all</button>
                    <button type="button" @click="selectedSections = []" class="text-slate-500 hover:underline">Clear selection</button>
                </div>
            </div>
            <label class="text-xs font-bold text-slate-600">
                Attendance date
                <input type="date" x-model="absenceDate" max="<?php echo e(today()->toDateString()); ?>"
                       @change="changeDate()"
                       class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3" x-show="dashboard.sections.length > 0">
            <template x-for="section in dashboard.sections" :key="section.name">
                <article class="rounded-2xl border-2 bg-white p-4 shadow-xs transition"
                         :class="selectedSections.includes(section.name) ? 'border-[#8b1818]' : 'border-slate-200'">
                    <div class="flex items-start justify-between gap-3">
                        <label class="flex min-w-0 cursor-pointer items-start gap-3">
                            <input type="checkbox" :checked="selectedSections.includes(section.name)"
                                   @change="toggleSection(section.name)"
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-[#590d0d] focus:ring-[#8b1818]">
                            <span class="min-w-0">
                                <span class="block truncate font-black text-slate-900" x-text="section.name"></span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500">
                                    <span x-text="section.student_count"></span> absent student(s)
                                </span>
                            </span>
                        </label>
                        <button type="button" @click="openSection(section)"
                                class="shrink-0 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-[#590d0d]"
                                :aria-label="'View absent students in ' + section.name" title="View students">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-black uppercase">
                        <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-emerald-800">Linked students <span x-text="section.linked"></span></span>
                        <span class="rounded-lg bg-blue-100 px-2.5 py-1 text-blue-800">Will notify <span x-text="section.eligible_links"></span> accounts</span>
                        <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-emerald-800">Sent <span x-text="section.sent"></span></span>
                        <span class="rounded-lg bg-rose-100 px-2.5 py-1 text-rose-800">Failed <span x-text="section.failed"></span></span>
                        <button type="button" x-show="section.unlinked > 0" @click="openSection(section, true)"
                                class="rounded-lg bg-amber-100 px-2.5 py-1 text-amber-900 hover:bg-amber-200">
                            (!) Unlinked <span x-text="section.unlinked"></span>
                        </button>
                    </div>
                </article>
            </template>
        </div>
        <div x-show="dashboard.sections.length === 0" class="mt-5 rounded-xl border border-dashed border-slate-300 p-8 text-center">
            <p class="font-bold text-slate-700">No absent students for this date.</p>
            <p class="mt-1 text-xs text-slate-500">Students with present or late attendance are not included.</p>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
            <button type="button" @click="openSendModal()" :disabled="loading || selectedSections.length === 0 || selectedEligibleLinks() === 0"
                    class="rounded-xl bg-[#590d0d] px-4 py-2.5 text-sm font-black text-white hover:bg-[#43090c] disabled:cursor-not-allowed disabled:opacity-50">
                Send linked / retry failed (<span x-text="selectedEligibleLinks()"></span> accounts)
            </button>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-black text-slate-900">Send a custom message</h2>
        <p class="mt-1 text-sm text-slate-600">
            Only students with an approved, enabled Telegram link can be selected.
        </p>
        <form class="mt-4 space-y-4" @submit.prevent="sendCustom($event)">
            <div class="flex flex-wrap items-center gap-5">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="radio" value="all_linked" x-model="recipientMode">
                    All linked parent/guardian accounts
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="radio" value="selected_students" x-model="recipientMode">
                    Selected students
                </label>
            </div>
            <div x-show="recipientMode === 'selected_students'" x-cloak class="max-h-56 overflow-y-auto rounded-xl border border-slate-200 p-3">
                <?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <label class="flex items-center gap-2 border-b border-slate-100 py-2 text-sm last:border-0">
                        <input type="checkbox" name="student_ids[]" value="<?php echo e($student->id); ?>" class="telegram-student">
                        <span class="font-semibold text-slate-800"><?php echo e($student->first_name); ?> <?php echo e($student->last_name); ?></span>
                        <span class="ml-auto text-xs text-slate-500"><?php echo e($student->section ?: '—'); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="py-4 text-center text-sm text-slate-500">There are no approved Telegram-linked recipients in your scope.</p>
                <?php endif; ?>
            </div>
            <label class="block text-xs font-bold text-slate-600">
                Custom message
                <textarea name="message" x-model="message" required maxlength="4000" rows="4"
                          class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-normal"
                          placeholder="Write the message to send through Telegram."></textarea>
            </label>
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs text-slate-500" x-text="message.length + ' / 4000 characters'"></span>
                <button type="submit" :disabled="loading"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">
                    <span x-text="loading ? 'Sending…' : 'Send Telegram message'"></span>
                </button>
            </div>
        </form>
    </section>

    <div x-show="unlinkedOpen" x-cloak @keydown.escape.window="unlinkedOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
         @click.self="unlinkedOpen = false" role="dialog" aria-modal="true" aria-labelledby="unlinked-title">
        <section class="max-h-[85vh] w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <header class="flex items-center justify-between bg-amber-700 px-5 py-4 text-white">
                <div>
                    <h2 id="unlinked-title" class="font-black">Unlinked absent students</h2>
                    <p class="text-xs text-amber-100">These guardians cannot receive Telegram notifications yet.</p>
                </div>
                <button type="button" @click="unlinkedOpen = false" class="rounded-lg px-3 py-2 hover:bg-white/10" aria-label="Close">✕</button>
            </header>
            <ul class="max-h-[65vh] divide-y divide-slate-100 overflow-y-auto p-5">
                <template x-for="student in unlinkedStudents()" :key="student.id">
                    <li class="flex items-center justify-between gap-3 py-3">
                        <span class="font-bold text-slate-800" x-text="student.name"></span>
                        <span class="shrink-0 rounded-md bg-amber-50 px-2 py-1 text-xs font-bold text-amber-800" x-text="student.section"></span>
                    </li>
                </template>
                <li x-show="unlinkedStudents().length === 0" class="py-6 text-center text-sm text-slate-500">No unlinked absent students.</li>
            </ul>
            <footer class="flex justify-end border-t border-slate-100 p-4">
                <button type="button" @click="unlinkedOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Close</button>
            </footer>
        </section>
    </div>

    <div x-show="sectionOpen" x-cloak @keydown.escape.window="sectionOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
         @click.self="sectionOpen = false" role="dialog" aria-modal="true" aria-labelledby="section-title">
        <section class="max-h-[85vh] w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <header class="flex items-center justify-between bg-[#590d0d] px-5 py-4 text-white">
                <div>
                    <h2 id="section-title" class="font-black" x-text="viewingSection?.name"></h2>
                    <p class="text-xs text-white/70"><span x-text="viewingSection?.student_count"></span> absent student(s)</p>
                </div>
                <button type="button" @click="sectionOpen = false" class="rounded-lg px-3 py-2 hover:bg-white/10" aria-label="Close">✕</button>
            </header>
            <ul class="max-h-[65vh] divide-y divide-slate-100 overflow-y-auto px-5">
                <template x-for="student in sectionStudents()" :key="student.id">
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <span class="font-bold text-slate-800" x-text="student.name"></span>
                        <div class="flex items-center gap-2 text-[10px] font-black">
                            <span class="rounded-md px-2 py-1" :class="student.linked ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                  x-text="student.linked ? 'Linked' : '(!) Unlinked'"></span>
                            <span class="rounded-md px-2 py-1" :class="student.failed > 0 ? 'bg-rose-50 text-rose-700' : (student.sent > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600')"
                                  x-text="student.status"></span>
                        </div>
                    </li>
                </template>
                <li x-show="sectionStudents().length === 0" class="py-6 text-center text-sm text-slate-500">No students to display.</li>
            </ul>
            <footer class="flex justify-end border-t border-slate-100 p-4">
                <button type="button" @click="sectionOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Close</button>
            </footer>
        </section>
    </div>

    <div x-show="sendModalOpen" x-cloak @keydown.escape.window="if (!loading) sendModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
         @click.self="if (!loading) sendModalOpen = false" role="dialog" aria-modal="true" aria-labelledby="send-modal-title">
        <section class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <header class="flex items-center justify-between bg-[#590d0d] px-5 py-4 text-white">
                <div>
                    <h2 id="send-modal-title" class="font-black" x-text="sendModalState === 'confirm' ? 'Confirm absence notifications' : (sendModalState === 'sending' ? 'Sending Telegram notifications' : 'Dispatch result')"></h2>
                    <p class="text-xs text-white/70" x-show="sendModalState === 'confirm'">Only the checked sections will be processed.</p>
                </div>
                <button type="button" x-show="!loading" @click="sendModalOpen = false" class="rounded-lg px-3 py-2 hover:bg-white/10" aria-label="Close">✕</button>
            </header>
            <div class="space-y-4 p-5">
                <div x-show="sendModalState === 'confirm'" class="space-y-3">
                    <p class="text-sm text-slate-600">Send unnotified alerts and retry failed deliveries for these selected sections:</p>
                    <ul class="space-y-2">
                        <template x-for="section in selectedSectionCards()" :key="section.name">
                            <li class="rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800" x-text="section.name"></span>
                                    <span class="text-xs font-semibold text-slate-600"><span x-text="section.student_count"></span> absent</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2 text-[10px] font-black">
                                    <span class="rounded-md bg-blue-100 px-2 py-1 text-blue-800">Eligible linked accounts <span x-text="section.eligible_links"></span></span>
                                    <span class="rounded-md bg-amber-100 px-2 py-1 text-amber-900">(!) Unlinked skipped <span x-text="section.unlinked"></span></span>
                                    <span class="rounded-md bg-emerald-100 px-2 py-1 text-emerald-800">Already sent <span x-text="section.already_sent"></span></span>
                                </div>
                            </li>
                        </template>
                    </ul>
                    <p class="text-xs text-slate-500">
                        Only approved Telegram links with alerts enabled and a new or failed notification are attempted.
                        Unlinked students are not sent a message. Successfully sent notifications are not repeated.
                    </p>
                    <p x-show="selectedEligibleLinks() === 0" class="rounded-lg bg-amber-50 p-3 text-sm font-bold text-amber-900">
                        No eligible linked Telegram accounts in the selected sections. Nothing will be sent.
                    </p>
                </div>
                <div x-show="sendModalState === 'sending'" class="py-6 text-center">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-[#590d0d]"></i>
                    <p class="mt-3 font-bold text-slate-700" x-text="dispatchKind === 'custom' ? 'Sending custom message…' : 'Sending selected notifications…'"></p>
                </div>
                <div x-show="sendModalState === 'result'" class="rounded-xl border p-4"
                     :class="result?.success ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900'">
                    <div class="flex items-start gap-3">
                        <i class="mt-0.5 text-lg" :class="result?.success ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'"></i>
                        <div>
                            <p class="font-black" x-text="result?.success ? 'Dispatch completed' : 'Dispatch failed or partially failed'"></p>
                            <p class="mt-1 text-sm" x-text="result?.message"></p>
                        </div>
                    </div>
                </div>
                <div x-show="sendModalState === 'result' && result?.summary" class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-emerald-50 p-2"><span class="block text-[10px] font-black uppercase text-emerald-700">Sent</span><span class="font-black text-emerald-900" x-text="result?.summary?.sent"></span></div>
                    <div class="rounded-lg bg-rose-50 p-2"><span class="block text-[10px] font-black uppercase text-rose-700">Failed</span><span class="font-black text-rose-900" x-text="result?.summary?.failed"></span></div>
                    <div class="rounded-lg bg-slate-100 p-2"><span class="block text-[10px] font-black uppercase text-slate-600">Skipped</span><span class="font-black text-slate-800" x-text="result?.summary?.skipped"></span></div>
                </div>
            </div>
            <footer class="flex justify-end gap-2 border-t border-slate-100 p-4">
                <button type="button" x-show="sendModalState !== 'sending'" @click="sendModalOpen = false"
                        class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700" x-text="sendModalState === 'result' ? 'Done' : 'Cancel'"></button>
                <button type="button" x-show="sendModalState === 'confirm'" @click="sendAbsence()" :disabled="loading || selectedEligibleLinks() === 0"
                        class="rounded-xl bg-[#590d0d] px-4 py-2 text-sm font-black text-white disabled:opacity-50">
                    Send now
                </button>
            </footer>
        </section>
    </div>

    <?php if($isAdmin): ?>
        <div x-show="templateOpen" x-cloak @keydown.escape.window="templateOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
             @click.self="templateOpen = false" role="dialog" aria-modal="true" aria-labelledby="template-title">
            <form method="POST" action="<?php echo e(route('admin.telegram.absence-template.update')); ?>"
                  class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <?php echo csrf_field(); ?>
                <header class="flex items-center justify-between bg-[#590d0d] px-5 py-4 text-white">
                    <div>
                        <h2 id="template-title" class="font-black">Edit Telegram absence template</h2>
                        <p class="text-xs text-white/70">This message is sent to linked guardians of absent students.</p>
                    </div>
                    <button type="button" @click="templateOpen = false" class="rounded-lg px-3 py-2 hover:bg-white/10" aria-label="Close">✕</button>
                </header>
                <div class="space-y-3 p-5">
                    <textarea name="absence_template" required maxlength="4000" rows="5"
                              class="w-full rounded-xl border-2 border-slate-200 p-3 text-sm text-slate-800 focus:border-[#8b1818] focus:outline-none"><?php echo e(old('absence_template', $absenceTemplate)); ?></textarea>
                    <p class="text-xs text-slate-500">
                        Placeholders: <code>{student_name}</code>, <code>{date}</code>, <code>{section}</code>, <code>{school_name}</code>.
                    </p>
                </div>
                <footer class="flex justify-end gap-2 border-t border-slate-100 p-4">
                    <button type="button" @click="templateOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#590d0d] px-4 py-2 text-sm font-black text-white">Save template</button>
                </footer>
            </form>
        </div>
    <?php endif; ?>
</main>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function telegramDispatch() {
    return {
        absenceDate: <?php echo json_encode($absenceDate, 15, 512) ?>,
        dashboard: <?php echo json_encode($dashboard, 15, 512) ?>,
        selectedSections: <?php echo json_encode(array_column($dashboard['sections'], 'name'), 512) ?>,
        recipientMode: 'all_linked',
        message: '',
        loading: false,
        result: null,
        unlinkedOpen: false,
        sectionOpen: false,
        viewingSection: null,
        sectionFilter: 'all',
        sendModalOpen: false,
        sendModalState: 'confirm',
        dispatchKind: 'absence',
        templateOpen: false,

        toggleSection(name) {
            this.selectedSections = this.selectedSections.includes(name)
                ? this.selectedSections.filter(section => section !== name)
                : [...this.selectedSections, name];
        },

        selectedSectionCards() {
            return this.dashboard.sections.filter(section => this.selectedSections.includes(section.name));
        },

        selectedEligibleLinks() {
            return this.selectedSectionCards().reduce((total, section) => total + section.eligible_links, 0);
        },

        unlinkedStudents() {
            return this.dashboard.sections.flatMap(section =>
                section.students
                    .filter(student => !student.linked)
                    .map(student => ({ ...student, section: section.name }))
            );
        },

        openSection(section, onlyUnlinked = false) {
            this.viewingSection = section;
            this.sectionFilter = onlyUnlinked ? 'unlinked' : 'all';
            this.sectionOpen = true;
        },

        sectionStudents() {
            const students = this.viewingSection?.students || [];
            return this.sectionFilter === 'unlinked'
                ? students.filter(student => !student.linked)
                : students;
        },

        openSendModal() {
            if (this.selectedSections.length === 0) {
                this.result = { success: false, message: 'Select at least one section to send absence notifications.' };
                return;
            }
            this.result = null;
            this.dispatchKind = 'absence';
            this.sendModalState = 'confirm';
            this.sendModalOpen = true;
        },

        async post(url, payload) {
            this.loading = true;
            this.result = null;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': <?php echo json_encode(csrf_token(), 15, 512) ?>,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.dashboard) {
                    this.dashboard = data.dashboard;
                }
                this.result = {
                    success: response.ok && Boolean(data.success),
                    message: data.message || 'Telegram dispatch completed.',
                    summary: data.summary || null
                };
            } catch (error) {
                this.result = { success: false, message: 'Unable to complete the Telegram request. Check the server logs and try again.' };
            } finally {
                this.loading = false;
            }
        },

        async sendAbsence() {
            this.sendModalState = 'sending';
            await this.post(<?php echo json_encode($isAdmin ? route('admin.telegram.send-absence-notifications') : route('teacher.telegram.send-absence-notifications'), 15, 512) ?>, {
                absence_date: this.absenceDate,
                selected_sections: this.selectedSections
            });
            this.sendModalState = 'result';
        },

        changeDate() {
            window.location.search = '?absence_date=' + encodeURIComponent(this.absenceDate);
        },

        async sendCustom() {
            const studentIds = Array.from(document.querySelectorAll('.telegram-student:checked')).map(input => Number(input.value));
            this.dispatchKind = 'custom';
            this.sendModalState = 'sending';
            this.sendModalOpen = true;
            await this.post(<?php echo json_encode($isAdmin ? route('admin.telegram.send-message') : route('teacher.telegram.send-message'), 15, 512) ?>, {
                recipient_mode: this.recipientMode,
                student_ids: studentIds,
                message: this.message
            });
            this.sendModalState = 'result';
        }
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/notifications/dispatch.blade.php ENDPATH**/ ?>