<!-- ================= Modal: Import CSV / Excel ================= -->
<div id="importScheduleModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 sm:p-6 transition-all duration-300 opacity-0 scale-95">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col">
        <div class="px-8 py-5 border-b-2 border-slate-100 bg-slate-50 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900">Import Class Schedules</h3>
            <button type="button" onclick="closeImportModal()" class="w-9 h-9 rounded-xl hover:bg-slate-200 text-slate-500 font-bold cursor-pointer">✕</button>
        </div>
        <form action="{{ route('admin.schedules.import') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Upload CSV File *</label>
                <input type="file" name="file" accept=".csv, .xlsx" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-red-50 file:text-[#8b1818] hover:file:bg-red-100 cursor-pointer border-2 border-dashed border-slate-300 rounded-2xl p-4">
                <p class="text-[11px] text-slate-400 mt-2 font-medium">Make sure your file follows the standard template format for class schedules.</p>
            </div>
            <div class="pt-4 border-t flex justify-end gap-3">
                <button type="button" onclick="closeImportModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black cursor-pointer transition">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openImportModal() {
        const modal = document.getElementById('importScheduleModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => { modal.classList.remove('opacity-0', 'scale-95'); modal.classList.add('opacity-100', 'scale-100'); }, 10);
            document.body.classList.add('overflow-hidden');
        }
    }
    function closeImportModal() {
        const modal = document.getElementById('importScheduleModal');
        if (modal) {
            modal.classList.remove('opacity-100', 'scale-100');
            modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); }, 200);
        }
    }
</script>