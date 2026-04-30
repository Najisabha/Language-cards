@extends('layouts.app')

@section('title', $mode === 'pick_language' ? 'اختر اللغة — المجموعات' : ($mode === 'pick_level' ? 'اختر المستوى — '.$language->name : 'المجموعات — '.$level->name))

@section('content')
    @if ($mode === 'pick_language')
        <div class="mb-4 text-sm text-slate-500">
            <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
            <span class="mx-1">/</span>
            <span>المجموعات</span>
        </div>

        <section class="page-hero mb-8">
            <div>
                <p class="page-eyebrow">Decks</p>
                <h1 class="page-title">المجموعات</h1>
                <p class="page-subtitle">اختر اللغة أولًا، ثم اختر المستوى لعرض المجموعات التابعة له.</p>
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
            <div class="stat-card">
                <span class="stat-label">إجمالي المجموعات</span>
                <strong class="stat-value">{{ $stats['decks'] }}</strong>
            </div>
        </section>

        @if ($languages->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 mb-4">لا توجد لغات بعد. أضف لغة أولًا لتتمكن من استعراض المجموعات.</p>
                <a href="{{ route('languages.create') }}" class="btn btn-primary">+ لغة جديدة</a>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($languages as $item)
                    <a href="{{ route('decks.index', ['language_id' => $item->id]) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-12 w-12 rounded-2xl shadow-inner" style="background-color: {{ $item->color }}"></span>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600">{{ $item->name }}</h3>
                                    @if ($item->code)
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $item->code }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">{{ $item->levels_count }} مستوى</span>
                        </div>
                        <p class="text-sm text-slate-500">اختيار هذه اللغة للانتقال إلى مستوياتها ثم مجموعاتها.</p>
                    </a>
                @endforeach
            </div>
        @endif
    @elseif ($mode === 'pick_level')
        <div class="mb-4 text-sm text-slate-500">
            <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
            <span class="mx-1">/</span>
            <a href="{{ route('decks.index') }}" class="text-indigo-600 hover:underline">المجموعات</a>
            <span class="mx-1">/</span>
            <span>{{ $language->name }}</span>
        </div>

        <section class="page-hero mb-8">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-14 w-14 rounded-2xl shrink-0 shadow-inner" style="background-color: {{ $language->color }}"></span>
                <div>
                    <p class="page-eyebrow">{{ $language->name }}{{ $language->code ? ' · '.$language->code : '' }}</p>
                    <h1 class="page-title">اختر المستوى</h1>
                    <p class="page-subtitle">اختر مستوى من لغة {{ $language->name }} لعرض المجموعات التابعة له.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('decks.index') }}" class="btn btn-secondary">تغيير اللغة</a>
                <a href="{{ route('levels.create', ['language_id' => $language->id]) }}" class="btn btn-primary">+ مستوى جديد</a>
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
                <strong class="stat-value">{{ $stats['cards'] ?? 0 }}</strong>
            </div>
        </section>

        @if ($levels->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 mb-4">لا توجد مستويات في هذه اللغة بعد.</p>
                <a href="{{ route('levels.create', ['language_id' => $language->id]) }}" class="btn btn-primary">أضف أول مستوى</a>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($levels as $item)
                    <a href="{{ route('decks.index', ['language_id' => $language->id, 'level_id' => $item->id]) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">{{ $item->name }}</p>
                                <h3 class="mt-2 text-xl font-bold text-slate-900 group-hover:text-indigo-600">{{ $item->title ?: 'مستوى دراسي' }}</h3>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">{{ $item->decks_count }} مجموعة</span>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">المجموعات</span>
                                <strong class="text-slate-800">{{ $item->decks_count }}</strong>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-slate-500">البطاقات</span>
                                <strong class="text-slate-800">{{ $item->cards_count }}</strong>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <div class="mb-4 text-sm text-slate-500">
            <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
            <span class="mx-1">/</span>
            <a href="{{ route('decks.index') }}" class="text-indigo-600 hover:underline">المجموعات</a>
            <span class="mx-1">/</span>
            <a href="{{ route('decks.index', ['language_id' => $language->id]) }}" class="text-indigo-600 hover:underline">{{ $language->name }}</a>
            <span class="mx-1">/</span>
            <span>{{ $level->name }}</span>
        </div>

        <section class="page-hero mb-8">
            <div>
                <p class="page-eyebrow">{{ $language->name }} / {{ $level->name }}</p>
                <h1 class="page-title">مجموعات المستوى {{ $level->name }}</h1>
                <p class="page-subtitle">هذه المجموعات تخص المستوى المحدد فقط. يمكنك فتح المجموعة لإدارة التصنيفات والبطاقات.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('decks.index', ['language_id' => $language->id]) }}" class="btn btn-secondary">تغيير المستوى</a>
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

        @if ($decks->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
                <p class="text-slate-500 mb-4">لا توجد مجموعات في هذا المستوى بعد.</p>
                <a href="{{ route('decks.create', ['level_id' => $level->id]) }}" class="btn btn-primary">أضف أول مجموعة</a>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($decks as $deck)
                    <a href="{{ route('decks.show', $deck) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <span class="inline-flex h-12 w-12 rounded-2xl shadow-inner" style="background-color: {{ $deck->color }}"></span>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">{{ $deck->cards_count }} بطاقة</span>
                        </div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $deck->level?->name ?? 'بدون مستوى' }}</p>
                        <h3 class="mb-2 text-lg font-bold text-slate-900 group-hover:text-indigo-600">{{ $deck->name }}</h3>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span>{{ $deck->categories_count }} تصنيف</span>
                            <span>•</span>
                            <span>{{ $deck->cards_count }} بطاقة</span>
                        </div>
                        @if ($deck->description)
                            <p class="mt-3 line-clamp-2 text-sm text-slate-400">{{ $deck->description }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    @endif
@endsection
