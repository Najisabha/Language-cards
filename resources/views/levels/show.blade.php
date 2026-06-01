@extends('layouts.app')

@section('title', $level->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        @if ($level->language)
            <a href="{{ route('languages.show', $level->language) }}" class="text-indigo-600 hover:underline">{{ $level->language->name }}</a>
            <span class="mx-1">/</span>
        @endif
        <span>{{ $level->name }}</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">{{ $level->language?->name ?? 'Level' }}</p>
            <h1 class="page-title">{{ $level->name }}</h1>
            @if ($level->title)
                <p class="mt-1 text-lg font-semibold text-slate-700">{{ $level->title }}</p>
            @endif
            <p class="page-subtitle">اختر نوع المحتوى (تحيات، أرقام…) ثم اختر كلمات أو جمل لعرض البطاقات.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if (! $level->decks->isEmpty())
                <a href="{{ route('levels.print.options', $level) }}" class="btn btn-secondary">طباعة</a>
            @endif
            @admin
                <a href="{{ route('levels.edit', $level) }}" class="btn btn-secondary">تعديل</a>
                <a href="{{ route('decks.create', ['level_id' => $level->id]) }}" class="btn btn-primary">+ نوع جديد</a>
            @endadmin
        </div>
    </section>

    <section class="stats-grid mb-8">
        <div class="stat-card">
            <span class="stat-label">الأنواع</span>
            <strong class="stat-value">{{ $stats['decks'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">البطاقات</span>
            <strong class="stat-value">{{ $stats['cards'] }}</strong>
        </div>
    </section>

    @if ($level->decks->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد أنواع في هذا المستوى بعد.</p>
            @admin
                <a href="{{ route('decks.create', ['level_id' => $level->id]) }}" class="btn btn-primary">أضف أول نوع</a>
            @endadmin
        </div>
    @else
        <h2 class="mb-4 text-lg font-bold text-slate-900">اختر النوع</h2>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($level->decks as $deck)
                <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-lg">
                    <a href="{{ route('decks.show', $deck) }}"
                       class="absolute inset-0 z-10 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                       aria-label="فتح نوع {{ $deck->name }}"></a>
                    <div class="pointer-events-none relative p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <span class="inline-flex h-12 w-12 rounded-2xl shadow-inner" style="background-color: {{ $deck->color }}"></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">{{ $deck->cards_count }} بطاقة</span>
                        </div>
                        <h3 class="mb-1 text-lg font-bold text-slate-900">{{ $deck->name }}</h3>
                        @if ($deck->description)
                            <p class="line-clamp-2 text-sm text-slate-500">{{ $deck->description }}</p>
                        @endif
                        <p class="mt-4 text-sm font-semibold text-indigo-600">فتح النوع ←</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
