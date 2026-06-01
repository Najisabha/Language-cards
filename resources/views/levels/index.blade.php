@extends('layouts.app')

@section('title', $mode === 'pick_language' ? 'اختر اللغة — المستويات' : 'المستويات — '.$selectedLanguage->name)

@section('content')
    @if ($mode === 'pick_language')
        <div class="mb-4 text-sm text-slate-500">
            <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
            <span class="mx-1">/</span>
            <span>المستويات</span>
        </div>

        <section class="page-hero mb-8">
            <div>
                <p class="page-eyebrow">Levels</p>
                <h1 class="page-title">المستويات</h1>
                <p class="page-subtitle">اختر اللغة أولًا لعرض مستوياتها. كل لغة لها مستوياتها الخاصة.</p>
            </div>
        </section>

        <section class="stats-grid mb-8">
            <div class="stat-card">
                <span class="stat-label">اللغات</span>
                <strong class="stat-value">{{ $stats['languages'] }}</strong>
            </div>
            <div class="stat-card">
                <span class="stat-label">إجمالي المستويات</span>
                <strong class="stat-value">{{ $stats['levels'] }}</strong>
            </div>
        </section>

        @if ($languages->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 mb-4">لا توجد لغات بعد. أضف لغة أولًا لتتمكن من عرض المستويات.</p>
                @admin
                    <a href="{{ route('languages.create') }}" class="btn btn-primary">+ لغة جديدة</a>
                @endadmin
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($languages as $language)
                    <a href="{{ route('levels.index', ['language_id' => $language->id]) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
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
                        <p class="text-sm text-slate-500">عرض مستويات هذه اللغة</p>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <div class="mb-4 text-sm text-slate-500">
            <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
            <span class="mx-1">/</span>
            <a href="{{ route('levels.index') }}" class="text-indigo-600 hover:underline">المستويات</a>
            <span class="mx-1">/</span>
            <a href="{{ route('languages.show', $selectedLanguage) }}" class="text-indigo-600 hover:underline">{{ $selectedLanguage->name }}</a>
            <span class="mx-1">/</span>
            <span>قائمة المستويات</span>
        </div>

        <section class="page-hero mb-8">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-14 w-14 rounded-2xl shrink-0 shadow-inner" style="background-color: {{ $selectedLanguage->color }}"></span>
                <div>
                    <p class="page-eyebrow">{{ $selectedLanguage->name }}{{ $selectedLanguage->code ? ' · '.$selectedLanguage->code : '' }}</p>
                    <h1 class="page-title">مستويات {{ $selectedLanguage->name }}</h1>
                    <p class="page-subtitle">مستويات هذه اللغة فقط. يمكنك إضافة مستوى جديد أو فتح مستوى لإدارة مجموعاته.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('levels.index') }}" class="btn btn-secondary">تغيير اللغة</a>
                @admin
                    <a href="{{ route('levels.create', ['language_id' => $selectedLanguage->id]) }}" class="btn btn-primary">+ مستوى جديد</a>
                @endadmin
            </div>
        </section>

        <section class="stats-grid mb-8">
            <div class="stat-card">
                <span class="stat-label">عدد المستويات</span>
                <strong class="stat-value">{{ $stats['levels'] }}</strong>
            </div>
            <div class="stat-card">
                <span class="stat-label">إجمالي المجموعات</span>
                <strong class="stat-value">{{ $stats['decks'] }}</strong>
            </div>
            <div class="stat-card">
                <span class="stat-label">إجمالي البطاقات</span>
                <strong class="stat-value">{{ $stats['cards'] }}</strong>
            </div>
        </section>

        @if ($levels->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 mb-4">لا توجد مستويات في هذه اللغة بعد.</p>
                @admin
                    <a href="{{ route('levels.create', ['language_id' => $selectedLanguage->id]) }}" class="btn btn-primary">أضف أول مستوى</a>
                @endadmin
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($levels as $level)
                    <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-lg">
                        <a href="{{ route('levels.show', $level) }}"
                           class="absolute inset-0 z-10 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                           aria-label="فتح المستوى {{ $level->name }}"></a>
                        <div class="pointer-events-none relative p-5">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">{{ $level->name }}</p>
                                    <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $level->title ?: 'مستوى دراسي' }}</h3>
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

                            <p class="mt-4 text-sm font-semibold text-indigo-600">فتح المستوى ←</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
@endsection
