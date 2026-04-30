@extends('layouts.app')

@section('title', $language->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        <span>{{ $language->name }}</span>
    </div>

    <section class="page-hero mb-8">
        <div class="flex items-start gap-3">
            <span class="inline-flex h-14 w-14 rounded-2xl shrink-0 shadow-inner" style="background-color: {{ $language->color }}"></span>
            <div>
                <p class="page-eyebrow">Language{{ $language->code ? ' · '.$language->code : '' }}</p>
                <h1 class="page-title">{{ $language->name }}</h1>
                <p class="page-subtitle">ابدأ أولًا بإضافة المستويات لهذه اللغة، وبعدها ستتمكن من إضافة المجموعات داخل كل مستوى.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('languages.edit', $language) }}" class="btn btn-secondary">تعديل</a>
            <a href="{{ route('levels.create', ['language_id' => $language->id]) }}" class="btn btn-primary">+ مستوى جديد</a>
        </div>
    </section>

    <section class="stats-grid mb-8">
        <div class="stat-card">
            <span class="stat-label">المستويات</span>
            <strong class="stat-value">{{ $stats['levels'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">المجموعات</span>
            <strong class="stat-value">{{ $stats['decks'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">البطاقات</span>
            <strong class="stat-value">{{ $stats['cards'] }}</strong>
        </div>
    </section>

    @if ($language->levels->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد مستويات في هذه اللغة بعد.</p>
            <a href="{{ route('levels.create', ['language_id' => $language->id]) }}" class="btn btn-primary">أضف أول مستوى</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($language->levels as $level)
                <a href="{{ route('levels.show', $level) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">{{ $level->name }}</p>
                            <h3 class="mt-2 text-xl font-bold text-slate-900 group-hover:text-indigo-600">{{ $level->title ?: 'مستوى دراسي' }}</h3>
                        </div>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">{{ $level->decks_count }} مجموعة</span>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">المجموعات</span>
                            <strong class="text-slate-800">{{ $level->decks_count }}</strong>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span class="text-slate-500">البطاقات</span>
                            <strong class="text-slate-800">{{ $level->cards_count }}</strong>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
