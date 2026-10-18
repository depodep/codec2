@extends('layouts.app')

@section('title', 'Evaluate Faculty | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    <header class="flex flex-col justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 sm:flex-row sm:items-center lg:px-10">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-[#8b1818]">Principal Evaluation · {{ $activeCycle->school_year }}</p>
            <h1 class="mt-1 text-xl font-black text-slate-900">{{ $teacher->last_name }}, {{ $teacher->first_name }}</h1>
            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $form->title ?? "Principal's Evaluation" }}</p>
        </div>
        <a href="{{ route('management.evaluations.index') }}" class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-arrow-left mr-1"></i> Faculty List</a>
    </header>
    <main class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6">
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>
        @endif
        @if(!empty($form?->instructions))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-semibold leading-relaxed text-amber-950">{{ $form->instructions }}</div>
        @endif
        <form action="{{ route('management.evaluations.store', $teacher->id) }}" method="POST" class="space-y-5">
            @csrf
            @foreach($questions as $question)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    @if($question->category)
                        <p class="mb-2 text-[10px] font-black uppercase tracking-wide text-[#8b1818]">{{ $question->category }}</p>
                    @endif
                    <p class="text-sm font-bold text-slate-900">{{ $loop->iteration }}. {{ $question->question }}</p>
                    <div class="mt-4 grid grid-cols-5 gap-2">
                        @for($rating = 1; $rating <= 5; $rating++)
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2 py-3 text-xs font-black text-slate-700 hover:border-[#8b1818] hover:bg-red-50">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $rating }}" @checked(old('answers.'.$question->id) == $rating) @required((bool) $question->is_required) class="accent-[#8b1818]">
                                <span>{{ $rating }}</span>
                            </label>
                        @endfor
                    </div>
                </section>
            @endforeach
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <label for="comments" class="text-xs font-black uppercase tracking-wide text-slate-700">Comments / Feedback (Optional)</label>
                <textarea id="comments" name="comments" rows="4" maxlength="2000" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm focus:border-[#8b1818] focus:outline-none">{{ old('comments') }}</textarea>
            </section>
            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-[#590d0d] px-6 py-3 text-xs font-black text-white shadow-xs hover:bg-[#43090c]"><i class="fa-solid fa-paper-plane mr-1"></i> Submit Principal Evaluation</button>
            </div>
        </form>
    </main>
</div>
@endsection
