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
                <p class="page-eyebrow">{{ $deck->level?->name ?? 'Deck' }}</p>
                <h1 class="page-title">{{ $deck->name }}</h1>
                @if ($deck->description)
                    <p class="page-subtitle max-w-xl">{{ $deck->description }}</p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="rounded-full bg-slate-100 px-3 py-1">{{ $cards->count() }} بطاقة</span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('decks.edit', $deck) }}" class="btn btn-secondary">تعديل</a>
            <a href="{{ route('decks.cards.reorder.form', $deck) }}" class="btn btn-secondary">ترتيب البطاقات</a>
            <a href="{{ route('decks.print.options', $deck) }}" class="btn btn-secondary">طباعة</a>
            <a href="{{ route('decks.cards.create', $deck) }}" class="btn btn-primary">+ بطاقة جديدة</a>
        </div>
    </section>

    @if ($cards->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد بطاقات داخل هذه المجموعة بعد.</p>
            <a href="{{ route('decks.cards.create', $deck) }}" class="btn btn-primary">أضف أول بطاقة</a>
        </div>
    @else
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900">بطاقات المجموعة</h2>
                <span class="text-sm text-slate-500">{{ $cards->count() }} بطاقة</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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

                        <div class="grid grid-cols-2 gap-2">
                            <x-flashcard-front :card="$card" class="aspect-[3/2] rounded-lg" />
                            <x-flashcard-back :card="$card" class="aspect-[3/2] rounded-lg" />
                        </div>

                        <div class="mt-3 text-center">
                            <p class="text-sm font-semibold text-slate-900">{{ $card->word }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
