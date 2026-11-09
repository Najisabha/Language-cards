@extends('layouts.app')

@section('title', 'خيارات طباعة اللغة - ' . $language->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        <a href="{{ route('languages.show', $language) }}" class="text-indigo-600 hover:underline">{{ $language->name }}</a>
        <span class="mx-1">/</span>
        <span>طباعة</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">طباعة اللغة</p>
            <h1 class="page-title">{{ $language->name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <x-icon-action :href="route('languages.show', $language)" title="رجوع" color="#64748b">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M4 12h11" stroke-linecap="round"></path>
                </svg>
            </x-icon-action>
        </div>
    </section>

    @php
        $allDecks = $language->levels->flatMap(fn ($level) => $level->decks->map(function ($deck) use ($level) {
            $deck->setAttribute('level_label', $level->name);
            return $deck;
        }));
    @endphp

    @if ($allDecks->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm">
            لا توجد مجموعات في هذه اللغة للطباعة.
        </div>
    @else
        <section class="mb-8 rounded-3xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50/40 p-6 shadow-sm">
            <form action="{{ route('languages.print', $language) }}" method="GET" target="_blank" class="space-y-4" id="language-print-selected-form">
                <input type="hidden" name="selection" value="1">

                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1 rounded-2xl border border-indigo-200 bg-white p-3 shadow-sm">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span id="selected-count" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">محدد: {{ $allDecks->count() }}</span>
                        </div>
                        <div class="flex w-full flex-nowrap items-center gap-2" dir="ltr">
                            <x-icon-action id="clear-all-decks" type="button" title="حذف الكل" color="#ef4444">
                                <x-icons.trash />
                            </x-icon-action>
                            <x-icon-action id="select-all-decks" type="button" title="تحديد الكل" color="#22c55e">
                                <x-icons.select-all />
                            </x-icon-action>
                            <div class="shrink-0">
                                <label for="level-filter" class="sr-only">تحديد المستوى</label>
                                <select
                                    id="level-filter"
                                    class="h-10 min-w-[8.5rem] rounded-full border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                >
                                    <option value="">كل المستويات</option>
                                    @foreach ($language->levels as $level)
                                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0 flex-1">
                                <input
                                    type="text"
                                    id="deck-search-input"
                                    placeholder="بحث..."
                                    class="h-10 w-full rounded-full border border-slate-300 bg-white px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                >
                            </div>
                            <x-icon-action type="submit" name="mode" value="default" title="طباعة افتراضية" color="#4f46e5">
                                <x-icons.print />
                            </x-icon-action>
                            <x-icon-action type="submit" name="mode" value="custom" title="طباعة مخصصة" color="#8b5cf6">
                                <x-icons.print-custom />
                            </x-icon-action>
                        </div>
                    </div>
                </div>

                <div id="decks-list" class="max-h-72 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-3 text-sm shadow-inner">
                    @foreach ($allDecks as $deck)
                        <label
                            class="deck-item flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 transition hover:border-indigo-300 hover:bg-indigo-50/40"
                            data-deck-name="{{ Str::lower($deck->level_label.' '.$deck->name) }}"
                            data-level-id="{{ $deck->level_id }}"
                        >
                            <input type="checkbox" name="deck_ids[]" value="{{ $deck->id }}" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="flex-1 font-medium text-slate-800">
                                <span class="text-indigo-600">{{ $deck->level_label }}</span>
                                <span class="mx-1 text-slate-300">·</span>
                                {{ $deck->name }}
                            </span>
                            <span class="rounded-full bg-white px-2 py-1 text-xs text-slate-600">{{ $deck->cards_count }} بطاقة</span>
                        </label>
                    @endforeach
                </div>
            </form>
        </section>

        <script>
            (() => {
                const form = document.getElementById('language-print-selected-form');
                if (!form) return;

                const searchInput = document.getElementById('deck-search-input');
                const levelFilter = document.getElementById('level-filter');
                const selectAllBtn = document.getElementById('select-all-decks');
                const clearAllBtn = document.getElementById('clear-all-decks');
                const selectedCount = document.getElementById('selected-count');
                const items = Array.from(form.querySelectorAll('.deck-item'));

                const normalize = (value) => (value || '').toLowerCase().trim();

                const applyFilter = () => {
                    const q = normalize(searchInput?.value);
                    const levelId = (levelFilter?.value || '').trim();

                    items.forEach((item) => {
                        const name = item.getAttribute('data-deck-name') || '';
                        const itemLevelId = item.getAttribute('data-level-id') || '';
                        const matchesSearch = !q || name.includes(q);
                        const matchesLevel = !levelId || itemLevelId === levelId;
                        const visible = matchesSearch && matchesLevel;

                        item.classList.toggle('hidden', !visible);
                    });

                    updateSelectedCount();
                };

                const updateSelectedCount = () => {
                    const checked = form.querySelectorAll('input[name="deck_ids[]"]:checked').length;
                    if (selectedCount) selectedCount.textContent = `محدد: ${checked}`;
                };

                selectAllBtn?.addEventListener('click', () => {
                    items.forEach((item) => {
                        if (item.classList.contains('hidden')) return;
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        if (checkbox && !checkbox.disabled) checkbox.checked = true;
                    });
                    updateSelectedCount();
                });

                clearAllBtn?.addEventListener('click', () => {
                    items.forEach((item) => {
                        if (item.classList.contains('hidden')) return;
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        if (checkbox && !checkbox.disabled) checkbox.checked = false;
                    });
                    updateSelectedCount();
                });

                searchInput?.addEventListener('input', applyFilter);
                levelFilter?.addEventListener('change', applyFilter);
                form.querySelectorAll('input[name="deck_ids[]"]').forEach((cb) => cb.addEventListener('change', updateSelectedCount));
                updateSelectedCount();

                form.addEventListener('submit', (e) => {
                    const checked = form.querySelectorAll('input[name="deck_ids[]"]:checked');
                    if (!checked.length) {
                        e.preventDefault();
                        alert('اختر مجموعة واحدة على الأقل.');
                    }
                });
            })();
        </script>
    @endif
@endsection
