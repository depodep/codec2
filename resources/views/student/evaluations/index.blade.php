@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <!-- Header Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Faculty Performance Evaluation</h1>
            <p class="text-xs text-slate-500 font-semibold mt-1">Select an instructor below to evaluate their teaching performance for this academic period.</p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-2xl transition inline-flex items-center gap-2 shadow-2xs w-fit">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Faculty List Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($facultyMembers ?? [] as $teacher)
            @php
                $isEvaluated = in_array($teacher->id, $evaluatedTeacherIds ?? []);
            @endphp

            <div class="bg-white rounded-3xl border-2 {{ $isEvaluated ? 'border-emerald-200 bg-emerald-50/10' : 'border-slate-200' }} p-6 shadow-xs transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl {{ $isEvaluated ? 'bg-emerald-600' : 'bg-[#8b1818]' }} text-white text-sm font-black flex items-center justify-center shrink-0 shadow-sm">
                            {{ strtoupper(substr($teacher->first_name, 0, 1)) }}{{ strtoupper(substr($teacher->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Prof. {{ $teacher->first_name }} {{ $teacher->last_name }}</h3>
                            <p class="text-[11px] text-slate-500 font-semibold">{{ $teacher->email }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 rounded-2xl p-3 mb-4 border border-slate-100 text-[11px] font-semibold text-slate-600 flex items-center justify-between">
                        <span>Status:</span>
                        @if($isEvaluated)
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[10px] border border-emerald-300 uppercase">Evaluated</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 font-black text-[10px] border border-amber-200 uppercase">Ready for Evaluation</span>
                        @endif
                    </div>
                </div>

                @if($isEvaluated)
                    <button disabled class="w-full py-3 bg-slate-200 text-slate-500 text-xs font-black rounded-2xl cursor-not-allowed flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check-circle"></i> Completed
                    </button>
                @else
                    <a href="{{ route('student.evaluations.take', $teacher->id) }}" class="w-full py-3 bg-[#8b1818] hover:bg-opacity-90 text-white text-xs font-black rounded-2xl transition flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa-solid fa-clipboard-list"></i> Start Evaluation
                    </a>
                @endif
            </div>
        @empty
            <div class="col-span-full py-16 bg-white rounded-3xl border-2 border-slate-200 text-center">
                <i class="fa-solid fa-users-slash text-3xl text-slate-300 mb-3 block"></i>
                <p class="text-xs font-black text-slate-600">No faculty members found for evaluation.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection