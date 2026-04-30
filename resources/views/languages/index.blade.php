@extends('layouts.app')

@section('title', 'اللغات')

@section('content')
    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">Languages</p>
            <h1 class="page-title">اللغات</h1>
            <p class="page-subtitle">ابدأ بإنشاء لغة، ثم أضف مستوياتها ومجموعاتها وبطاقاتها.</p>
        </div>
        <a href="{{ route('languages.create') }}" class="btn btn-primary">+ لغة جديدة</a>
    </section>

    <section class="stats-grid mb-8">
        <div class="stat-card">
            <span class="stat-label">عدد اللغات</span>
            <strong class="stat-value">{{ $stats['languages'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">إجمالي المستويات</span>
            <strong class="stat-value">{{ $stats['levels'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">إجمالي البطاقات</span>
            <strong class="stat-value">{{ $stats['cards'] }}</strong>
        </div>
    </section>

    @if ($languages->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد لغات بعد.</p>
            <a href="{{ route('languages.create') }}" class="btn btn-primary">أضف أول لغة</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($languages as $language)
                <a href="{{ route('languages.show', $language) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-12 w-12 rounded-2xl shadow-inner" style="background-color: {{ $language->color }}"></span>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600">{{ $language->name }}</h3>
                                @if ($language->code)
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $language->code }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">{{ $language->levels_count }} مستوى</span>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">المجموعات</span>
                            <strong class="text-slate-800">{{ $language->decks_count }}</strong>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span class="text-slate-500">البطاقات</span>
                            <strong class="text-slate-800">{{ $language->cards_count }}</strong>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
