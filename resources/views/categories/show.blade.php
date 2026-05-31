@extends('layouts.app')

@section('title', $category->name.' — '.$deck->name)

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
        <a href="{{ route('decks.show', $deck) }}" class="text-indigo-600 hover:underline">{{ $deck->name }}</a>
        <span class="mx-1">/</span>
        <span>{{ $category->name }}</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">{{ $deck->name }} · {{ $deck->level?->name }}</p>
            <h1 class="page-title">{{ $category->name }}</h1>
            <p class="page-subtitle">بطاقات {{ $category->name }} ضمن نوع «{{ $deck->name }}».</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('decks.show', $deck) }}" class="btn btn-secondary">رجوع للنوع</a>
            <a href="{{ route('categories.cards.create', $category) }}" class="btn btn-primary">+ بطاقة جديدة</a>
        </div>
    </section>

    @php
        $allCount = $totalCardsCount ?? $cards->count();
        $currentQ = $q ?? request('q');
    @endphp

    @if ($allCount === 0)
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد بطاقات في «{{ $category->name }}» بعد.</p>
            <a href="{{ route('categories.cards.create', $category) }}" class="btn btn-primary">أضف أول بطاقة</a>
        </div>
    @else
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900">البطاقات</h2>
                <span class="text-sm text-slate-500">
                    {{ $cards->count() }} بطاقة
                    @if(isset($totalCardsCount) && $totalCardsCount !== $cards->count())
                        <span class="mx-1">·</span>
                        <span>من أصل {{ $totalCardsCount }}</span>
                    @endif
                </span>
            </div>

            <form method="GET" action="{{ route('categories.show', $category) }}" class="mb-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <label for="card-search" class="sr-only">بحث</label>
                        <input
                            id="card-search"
                            name="q"
                            value="{{ $currentQ }}"
                            placeholder="ابحث بالكلمة أو المعنى أو الشرح..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/60"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-secondary">بحث</button>
                        @if(! empty($currentQ))
                            <a href="{{ route('categories.show', $category) }}" class="btn btn-secondary">مسح</a>
                        @endif
                    </div>
                </div>
            </form>

            @if($cards->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <p class="text-slate-500">لا توجد نتائج مطابقة.</p>
                </div>
            @else
            <div class="deck-cards-grid mx-auto grid max-w-5xl gap-5 sm:grid-cols-2 sm:gap-6">
                @foreach ($cards as $card)
                    <article class="deck-card-item rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">بطاقة</span>
                            <div class="flex items-center gap-1 text-xs">
                                <a href="{{ route('cards.edit', $card) }}" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">تعديل</a>
                                <form method="POST" action="{{ route('cards.destroy', $card) }}" onsubmit="return confirm('حذف البطاقة؟');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md px-2 py-1 text-red-600 hover:bg-red-50">حذف</button>
                                </form>
                            </div>
                        </div>

                        <div class="deck-card-faces grid grid-cols-2 gap-2 sm:gap-3">
                            <x-flashcard-front :card="$card" class="deck-card-face aspect-[3/2] rounded-lg" />
                            <x-flashcard-back :card="$card" class="deck-card-face aspect-[3/2] rounded-lg" />
                        </div>

                        <div class="mt-3 text-center">
                            <p class="text-sm font-semibold text-slate-900">{{ $card->word }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
            @endif
        </section>
    @endif
@endsection
