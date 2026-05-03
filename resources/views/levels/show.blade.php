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
            <p class="page-subtitle">{{ $level->title ?: 'هذا المستوى يحتوي على مجموعاتك التعليمية، وكل مجموعة تضم التصنيفات والبطاقات الخاصة بها.' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if (! $level->decks->isEmpty())
                <a href="{{ route('levels.print.options', $level) }}" class="btn btn-secondary">طباعة</a>
            @endif
            <a href="{{ route('levels.edit', $level) }}" class="btn btn-secondary">تعديل</a>
            <a href="{{ route('decks.create', ['level_id' => $level->id]) }}" class="btn btn-primary">+ مجموعة جديدة</a>
        </div>
    </section>

    <section class="stats-grid mb-8">
        <div class="stat-card">
            <span class="stat-label">المجموعات</span>
            <strong class="stat-value">{{ $stats['decks'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">التصنيفات</span>
            <strong class="stat-value">{{ $stats['categories'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">البطاقات</span>
            <strong class="stat-value">{{ $stats['cards'] }}</strong>
        </div>
    </section>

    @if ($level->decks->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد مجموعات في هذا المستوى بعد.</p>
            <a href="{{ route('decks.create', ['level_id' => $level->id]) }}" class="btn btn-primary">أضف أول مجموعة</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($level->decks as $deck)
                <a href="{{ route('decks.show', $deck) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <span class="inline-flex h-12 w-12 rounded-2xl shadow-inner" style="background-color: {{ $deck->color }}"></span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">{{ $deck->cards_count }} بطاقة</span>
                    </div>
                    <h3 class="mb-1 text-lg font-bold text-slate-900 group-hover:text-indigo-600">{{ $deck->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $deck->categories_count }} تصنيف</p>
                    @if ($deck->description)
                        <p class="mt-3 line-clamp-2 text-sm text-slate-400">{{ $deck->description }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection
