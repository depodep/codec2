@unless($botEnabled)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
        Telegram commands and outgoing notifications are disabled. Set TELEGRAM_BOT_ENABLED=true only if the bot should be reactivated.
    </div>
@endunless

<div class="grid gap-4 md:grid-cols-2">
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <button type="button" data-open-list-modal="student-links-modal"
                class="flex w-full items-center gap-3 text-left">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-amber-300"><i class="fa-solid fa-ticket"></i></span>
            <span>
                <span class="block text-base font-black text-slate-900">Student Links</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">View school codes and Telegram link status</span>
            </span>
        </button>
        <span class="mt-5 flex flex-wrap gap-2">
            <button type="button" data-open-list-modal="student-links-modal" data-student-link-status="linked"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-800 hover:bg-emerald-100">
                <i class="fa-solid fa-circle-check"></i> Linked {{ number_format($studentLinkCounts['linked']) }}
            </button>
            <button type="button" data-open-list-modal="student-links-modal" data-student-link-status="unlinked"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-100">
                <i class="fa-solid fa-link-slash"></i> Not linked {{ number_format($studentLinkCounts['unlinked']) }}
            </button>
        </span>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <button type="button" data-open-list-modal="parent-links-modal"
                class="flex w-full items-center gap-3 text-left">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-amber-300"><i class="fa-solid fa-link"></i></span>
            <span>
                <span class="block text-base font-black text-slate-900">Parent Links</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">View and manage all parent link registrations</span>
            </span>
        </button>
        <button type="button" data-open-list-modal="parent-links-modal" class="mt-5 block text-left">
            <span class="block text-3xl font-black leading-none text-slate-900">{{ number_format($parentLinkCount) }}</span>
            <span class="mt-1 block text-xs font-semibold text-slate-500">total registrations</span>
        </button>
    </article>
</div>

<div id="student-links-modal" data-list-modal class="telegram-list-modal fixed inset-y-0 left-72 right-0 z-40 hidden items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6"
     role="dialog" aria-modal="true" aria-labelledby="student-links-modal-title">
    <section class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 sm:p-5">
            <div>
                <h2 id="student-links-modal-title" class="text-lg font-black text-slate-900">Student Links</h2>
                <p class="text-xs font-semibold text-slate-500">School-issued codes and Telegram link status</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.telegram.student-link-codes.export', request()->query()) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-[#8b1818] px-3 py-2 text-xs font-black text-white hover:bg-[#731414]">
                    <i class="fa-solid fa-file-csv"></i> Export Codes CSV
                </a>
                <button type="button" data-close-list-modal class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close student links">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </header>
        <form method="GET" action="{{ route('admin.telegram.index') }}" id="student-link-filters" class="flex flex-wrap gap-2 border-b border-slate-200 p-4">
            <input name="student_search" value="{{ request('student_search') }}" placeholder="Search name, LRN, or code" aria-label="Search students"
                   class="min-w-[200px] flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold focus:border-[#8b1818] focus:outline-none">
            <select name="student_grade" aria-label="Filter students by grade" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold">
                <option value="">All Grades</option>
                @foreach($gradeLevels as $gradeLevel)
                    <option value="{{ $gradeLevel }}" @selected(request('student_grade') == $gradeLevel)>Grade {{ $gradeLevel }}</option>
                @endforeach
            </select>
            <select name="student_section" aria-label="Filter students by section" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold">
                <option value="">All Sections</option>
                @foreach($sections as $sectionOption)
                    <option value="{{ $sectionOption->section_name }}" @selected(request('student_section') === $sectionOption->section_name)>
                        {{ $sectionOption->section_name }} · Grade {{ $sectionOption->grade_level }}
                    </option>
                @endforeach
            </select>
            <select name="student_status" aria-label="Filter students by Telegram link status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold">
                <option value="">All Link Statuses</option>
                <option value="linked" @selected(request('student_status') === 'linked')>Linked</option>
                <option value="unlinked" @selected(request('student_status') === 'unlinked')>Not linked</option>
            </select>
            <button type="button" data-clear-modal-filters class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50">Clear</button>
        </form>
        <div class="min-h-0 flex-1 overflow-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="sticky top-0 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">LRN</th><th class="px-4 py-3">Grade / Section</th><th class="px-4 py-3">School Code</th><th class="px-4 py-3">Telegram</th><th class="px-4 py-3">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($studentCodes as $studentCode)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-black text-slate-400">{{ ($studentCodes->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $studentCode->first_name }} {{ $studentCode->last_name }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $studentCode->id_number }}</td>
                            <td class="px-4 py-3 text-slate-700">Grade {{ $studentCode->grade_level ?: '—' }} · {{ $studentCode->section ?: 'Unassigned' }}</td>
                            <td class="px-4 py-3 font-mono font-black tracking-wide text-slate-800">{{ $studentCode->code }}</td>
                            <td class="px-4 py-3">
                                @if($studentCode->has_telegram_link)
                                    <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase text-emerald-800">Linked</span>
                                @else
                                    <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase text-slate-600">Not linked</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" data-regenerate-code="{{ $studentCode->student_id }}" data-student-name="{{ trim($studentCode->first_name.' '.$studentCode->last_name) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-100">
                                    <i class="fa-solid fa-rotate"></i> Regenerate
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No student links match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="telegram-pagination border-t border-slate-100 px-5 py-4">{{ $studentCodes->links() }}</footer>
    </section>
</div>

<div id="parent-links-modal" data-list-modal class="telegram-list-modal fixed inset-y-0 left-72 right-0 z-40 hidden items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6"
     role="dialog" aria-modal="true" aria-labelledby="parent-links-modal-title">
    <section class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 p-4 sm:p-5">
            <div>
                <h2 id="parent-links-modal-title" class="text-lg font-black text-slate-900">Parent Links ({{ number_format($parentLinkCount) }})</h2>
                <p class="text-xs font-semibold text-slate-500">
                    Bot {{ $botEnabled ? 'enabled' : 'disabled' }} · Token {{ $botConfigured ? 'configured' : 'not configured/disabled' }} · Webhook secret {{ $webhookConfigured ? 'configured' : 'not configured' }}
                </p>
            </div>
            <button type="button" data-close-list-modal class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close parent links">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <form method="GET" action="{{ route('admin.telegram.index') }}" id="parent-link-filters" class="flex flex-wrap gap-2 border-b border-slate-200 p-4">
            <input name="parent_search" value="{{ request('parent_search') }}" placeholder="Search name, LRN, or chat ID" aria-label="Search parent links"
                   class="min-w-[200px] flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold focus:border-[#8b1818] focus:outline-none">
            <select name="parent_grade" aria-label="Filter parent links by grade" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold">
                <option value="">All Grades</option>
                @foreach($gradeLevels as $gradeLevel)
                    <option value="{{ $gradeLevel }}" @selected(request('parent_grade') == $gradeLevel)>Grade {{ $gradeLevel }}</option>
                @endforeach
            </select>
            <select name="parent_section" aria-label="Filter parent links by section" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold">
                <option value="">All Sections</option>
                @foreach($sections as $sectionOption)
                    <option value="{{ $sectionOption->section_name }}" @selected(request('parent_section') === $sectionOption->section_name)>
                        {{ $sectionOption->section_name }} · Grade {{ $sectionOption->grade_level }}
                    </option>
                @endforeach
            </select>
            <select name="parent_teacher" aria-label="Filter parent links by teacher" class="max-w-56 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold">
                <option value="">All Teachers</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((int) request('parent_teacher') === (int) $teacher->id)>{{ trim($teacher->first_name.' '.$teacher->last_name) }}</option>
                @endforeach
            </select>
            <button type="button" data-clear-modal-filters class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50">Clear</button>
        </form>
        <div class="min-h-0 flex-1 overflow-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="sticky top-0 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">LRN</th><th class="px-4 py-3">Grade / Section</th><th class="px-4 py-3">Telegram Chat</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Alerts</th><th class="px-4 py-3">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($links as $link)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-black text-slate-400">{{ ($links->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $link->first_name }} {{ $link->last_name }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $link->id_number }}</td>
                            <td class="px-4 py-3 text-slate-700">Grade {{ $link->grade_level ?: '—' }} · {{ $link->section ?: 'Unassigned' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $link->telegram_chat_id }}</td>
                            <td class="px-4 py-3">
                                @if($link->status === 'revoked')
                                    {{ $link->approved_at ? 'Unlinked' : 'Not linked' }}
                                @else
                                    {{ ucfirst($link->status) }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $link->notifications_enabled ? 'On' : 'Off' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if($link->status === 'pending')
                                        <form method="POST" action="{{ route('admin.telegram.links.approve', $link->id) }}" data-telegram-ajax>
                                            @csrf<button class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white">Approve</button>
                                        </form>
                                    @elseif($link->status === 'approved')
                                        <form method="POST" action="{{ route('admin.telegram.links.toggle-notifications', $link->id) }}" data-telegram-ajax>
                                            @csrf<button class="rounded bg-slate-700 px-3 py-1.5 text-xs font-bold text-white">{{ $link->notifications_enabled ? 'Turn alerts off' : 'Turn alerts on' }}</button>
                                        </form>
                                    @endif
                                    @if($link->status !== 'revoked')
                                        <form method="POST" action="{{ route('admin.telegram.links.revoke', $link->id) }}" data-telegram-ajax data-confirm="{{ $link->status === 'pending' ? 'Decline this Telegram link request?' : 'Revoke this Telegram link?' }}">
                                            @csrf<button class="rounded bg-rose-700 px-3 py-1.5 text-xs font-bold text-white">{{ $link->status === 'pending' ? 'Decline' : 'Revoke' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No Telegram parent links match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="telegram-pagination border-t border-slate-100 px-5 py-4">{{ $links->links() }}</footer>
    </section>
</div>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center gap-3 border-b border-slate-200 p-5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <div>
            <h2 class="text-base font-black text-slate-900">Attendance Notification History</h2>
            <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $notifications->total() }} delivery record(s)</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-white text-[10px] font-black uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Telegram chat</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Details</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-black text-slate-400">{{ ($notifications->firstItem() ?? 1) + $loop->index }}</td>
                        <td class="px-4 py-3">{{ $notification->created_at }}</td>
                        <td class="px-4 py-3">{{ $notification->first_name }} {{ $notification->last_name }}</td>
                        <td class="px-4 py-3 font-mono">{{ $notification->telegram_chat_id ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ $notification->status }}</td>
                        <td class="px-4 py-3">{{ $notification->details ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No Telegram attendance alerts recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="telegram-pagination border-t border-slate-100 px-5 py-4">{{ $notifications->links() }}</div>
</section>
