@extends('layouts.app')

@section('title', $deck->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        @if ($deck->level?->language)
            <a href="{{ route('languages.show', $deck->level->language) }}" class="text-indigo-600 hover:underline">{{ $deck->level->language->name }}</a>
            <span class="mx-1">/</span>
        @endif
        @if ($deck->level)
            <a href="{{ route('levels.show', $deck->level) }}" class="text-indigo-600 hover:underline">{{ $deck->level->name }}</a>
            <span class="mx-1">/</span>
        @endif
        <span>{{ $deck->name }}</span>
    </div>

    <section class="page-hero mb-8">
        <div class="flex items-start gap-3">
            <span class="inline-flex h-14 w-14 rounded-2xl shrink-0 shadow-inner" style="background-color: {{ $deck->color }}"></span>
            <div>
                <p class="page-eyebrow">نوع · {{ $deck->level?->name }}</p>
                <h1 class="page-title">{{ $deck->name }}</h1>
                <p class="page-subtitle">اختر «الكلمات» أو «الجمل» لعرض البطاقات وإضافتها.</p>
                @if ($deck->description)
                    <p class="mt-2 text-sm text-slate-500">{{ $deck->description }}</p>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @admin
                <a href="{{ route('decks.edit', $deck) }}" class="btn btn-secondary">تعديل النوع</a>
            @endadmin
            <a href="{{ route('decks.print.options', $deck) }}" class="btn btn-secondary">طباعة</a>
        </div>
    </section>

    <section class="stats-grid mb-8">
        <div class="stat-card">
            <span class="stat-label">إجمالي البطاقات</span>
            <strong class="stat-value">{{ $stats['cards'] }}</strong>
        </div>
    </section>

    <h2 class="mb-4 text-lg font-bold text-slate-900">اختر التصنيف</h2>

    @if ($deck->categories->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center shadow-sm">
            <p class="text-slate-500">لا توجد تصنيفات لهذا النوع.</p>
        </div>
    @else
    <div class="mx-auto grid max-w-3xl gap-5 sm:grid-cols-2">
        @foreach ($deck->categories as $category)
            @php
                $isWords = $category->name === \App\Models\Deck::CATEGORY_WORDS;
                $accent = $isWords ? '#6366f1' : '#10b981';
            @endphp
            <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-lg">
                <a href="{{ route('categories.show', $category) }}"
                   class="absolute inset-0 z-10 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                   aria-label="فتح {{ $category->name }}"></a>
                <div class="pointer-events-none relative p-6">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl text-lg font-bold text-white shadow-inner"
                          style="background-color: {{ $accent }}">
                        {{ $isWords ? 'ك' : 'ج' }}
                    </span>
                    <h3 class="mt-4 text-xl font-bold text-slate-900">{{ $category->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isWords ? 'بطاقات مفردات وكلمات' : 'بطاقات جمل وعبارات' }}
                    </p>
                    <p class="mt-4 text-sm font-semibold text-indigo-600">{{ $category->cards_count }} بطاقة · عرض ←</p>
                </div>
            </article>
        @endforeach
    </div>
    @endif
@endsection
