@extends('layouts.app')

@section('title', 'System Audit Logs - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70">

    {{-- ===== HEADER (Matching Attendance and other pages) ===== --}}
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">System Audit Logs</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Comprehensive audit trail of logins, dispatches, changes, and system operations</p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
               class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs">
                <i class="fa-solid fa-file-csv text-emerald-600 text-base"></i> Export CSV
            </a>
            <a href="{{ route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'excel'])) }}"
               class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs">
                <i class="fa-solid fa-file-excel text-base"></i> Export Excel
            </a>
        </div>
    </header>

    <main class="p-6 lg:p-10 max-w-[1700px] mx-auto space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ===== COMPACT KPI SUMMARY CARDS ===== --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Activities</p>
                    <p class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($totalActivities) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Login Activities</p>
                    <p class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($loginActivities) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Data Changes</p>
                    <p class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($dataChanges) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border {{ $failedActivities > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200' }} p-5 shadow-xs flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl {{ $failedActivities > 0 ? 'bg-rose-100 text-rose-600 border border-rose-200 animate-pulse' : 'bg-slate-100 text-slate-400 border border-slate-200' }} flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider {{ $failedActivities > 0 ? 'text-rose-600' : 'text-slate-400' }}">Failed Activities</p>
                    <p class="text-2xl font-black {{ $failedActivities > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-0.5">{{ number_format($failedActivities) }}</p>
                </div>
            </div>
        </div>

        {{-- ===== ACTIVITY FILTER BAR (Compact & Inline) ===== --}}
        <form method="GET" action="{{ route('admin.audit-logs') }}"
              class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Search</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="User, action, description, IP..."
                           class="w-full pl-8 pr-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700 placeholder:text-slate-400">
                </div>
            </div>

            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Role</label>
                <select name="role" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                    <option value="">All Roles</option>
                    <option value="Admin" @selected(request('role') === 'Admin')>Admin</option>
                    <option value="Faculty" @selected(request('role') === 'Faculty')>Faculty</option>
                    <option value="Student" @selected(request('role') === 'Student')>Student</option>
                    <option value="Management" @selected(request('role') === 'Management')>Management</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Module</label>
                <select name="module" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                    <option value="">All Modules</option>
                    @foreach(['Authentication', 'Students', 'Faculty', 'Sections', 'Schedules', 'Attendance', 'Telegram Notifications', 'Users', 'Audit Logs', 'Settings'] as $mod)
                        <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1 min-w-[120px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Status</label>
                <select name="status" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                    <option value="">All Statuses</option>
                    <option value="Success" @selected(request('status') === 'Success')>Success</option>
                    <option value="Failed" @selected(request('status') === 'Failed')>Failed</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="px-4 py-2 text-xs font-black uppercase tracking-wider rounded-xl bg-[#8b1818] hover:bg-[#601010] text-white flex items-center gap-1.5 transition shadow-xs cursor-pointer">
                    <i class="fa-solid fa-filter text-xs"></i> Filter
                </button>
                <a href="{{ route('admin.audit-logs') }}"
                   class="px-3 py-2 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Reset Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        {{-- ===== AUDIT LOGS TABLE ===== --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Recent System Activities</h3>
                    <p class="text-xs text-slate-500 font-semibold mt-0.5">Immutable audit trail of actions performed within SIATRACK</p>
                </div>
                <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-lg text-[10px] font-black uppercase tracking-wider">
                    <i class="fa-solid fa-lock mr-1"></i> Read-Only Audit Trail
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50/90 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-5">Date & Time</th>
                            <th class="py-3.5 px-5">User</th>
                            <th class="py-3.5 px-5">Role</th>
                            <th class="py-3.5 px-5">Action</th>
                            <th class="py-3.5 px-5">Module</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                        @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-5 font-mono text-slate-600 whitespace-nowrap">
                                {{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : 'N/A' }}
                            </td>
                            <td class="py-3.5 px-5 font-extrabold text-slate-900 whitespace-nowrap">
                                {{ $log->user_name }}
                            </td>
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black {{ match($log->role) { 'Admin', 'Super Admin' => 'bg-red-50 text-[#8b1818] border border-red-200', 'Faculty' => 'bg-blue-50 text-blue-700 border border-blue-200', 'Management' => 'bg-purple-50 text-purple-700 border border-purple-200', 'Student' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', default => 'bg-slate-100 text-slate-700 border border-slate-200' } }}">
                                    {{ $log->role }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5 font-bold text-slate-900">
                                <div class="max-w-xs truncate" title="{{ $log->action }}">
                                    {{ $log->action }}
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-slate-600 whitespace-nowrap">
                                {{ $log->module }}
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @if($log->status === 'Success')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check text-[9px] mr-1"></i> Success
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-circle-xmark text-[9px] mr-1"></i> Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <button type="button" 
                                        data-log="{{ e(json_encode([
                                            'user'        => $log->user_name,
                                            'role'        => $log->role,
                                            'action'      => $log->action,
                                            'module'      => $log->module,
                                            'date_time'   => $log->created_at ? $log->created_at->format('F d, Y - h:i A') : 'N/A',
                                            'status'      => $log->status,
                                            'description' => $log->description ?? 'No additional description provided.',
                                            'ip'          => $log->ip_address ?? 'N/A',
                                            'device'      => $log->device ?? 'N/A',
                                        ])) }}"
                                        onclick="openDetailsModal(JSON.parse(this.dataset.log))"
                                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-[#8b1818] hover:text-white text-slate-700 text-xs font-bold transition cursor-pointer shadow-2xs">
                                    <i class="fa-solid fa-eye text-[11px] mr-1"></i> View
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-slate-400 font-bold">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl shadow-2xs">
                                    <i class="fa-solid fa-folder-open"></i>
                                </div>
                                <p class="text-sm font-extrabold text-slate-800">No Audit Logs Found</p>
                                <p class="text-xs text-slate-500 font-semibold mt-1">Try adjusting your date range or search filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $auditLogs->withQueryString()->links() }}
            </div>
            @endif
        </div>

    </main>

</div>

{{-- ===== VIEW DETAILS MODAL ===== --}}
<div id="auditDetailsModal" class="fixed inset-0 z-50 bg-slate-950/60 hidden items-center justify-center p-4" style="backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-5 bg-slate-50 border-b-2 border-slate-100 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-amber-300 flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Activity Details</h3>
                    <p class="text-xs text-slate-500 font-semibold">Complete immutable system audit record</p>
                </div>
            </div>
            <button type="button" onclick="closeDetailsModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 text-xs font-semibold">
            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">User</span>
                    <span id="modalUser" class="text-sm font-extrabold text-slate-900">Admin</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Role</span>
                    <span id="modalRole" class="text-sm font-bold text-[#8b1818]">Administrator</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Action</span>
                    <span id="modalAction" class="text-sm font-bold text-slate-900">Action Name</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Module</span>
                    <span id="modalModule" class="text-sm font-bold text-slate-900">Module Name</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Date & Time</span>
                    <span id="modalDateTime" class="text-slate-800 font-mono text-[11px]">N/A</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Status</span>
                    <span id="modalStatus" class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 mt-1">Success</span>
                </div>
            </div>

            <div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Description</span>
                <p id="modalDescription" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed font-mono text-xs">
                    No description.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">IP Address</span>
                    <span id="modalIp" class="font-mono text-slate-600">N/A</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Device / Browser</span>
                    <span id="modalDevice" class="font-mono text-slate-600">N/A</span>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
            <button type="button" onclick="closeDetailsModal()" class="px-5 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#601010] text-white text-xs font-bold transition cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openDetailsModal(data) {
    document.getElementById('modalUser').innerText = data.user || 'N/A';
    document.getElementById('modalRole').innerText = data.role || 'N/A';
    document.getElementById('modalAction').innerText = data.action || 'N/A';
    document.getElementById('modalModule').innerText = data.module || 'N/A';
    document.getElementById('modalDateTime').innerText = data.date_time || 'N/A';
    document.getElementById('modalDescription').innerText = data.description || 'No additional description provided.';
    document.getElementById('modalIp').innerText = data.ip || 'N/A';
    document.getElementById('modalDevice').innerText = data.device || 'N/A';

    const statusEl = document.getElementById('modalStatus');
    statusEl.innerText = data.status || 'N/A';
    statusEl.className = data.status === 'Success' 
        ? 'inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 mt-1 border border-emerald-200' 
        : 'inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-800 mt-1 border border-rose-200';

    const modal = document.getElementById('auditDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDetailsModal() {
    const modal = document.getElementById('auditDetailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush