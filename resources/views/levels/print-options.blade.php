@extends('layouts.app')

@section('title', 'خيارات طباعة المستوى - ' . $level->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        @if ($level->language)
            <a href="{{ route('languages.show', $level->language) }}" class="text-indigo-600 hover:underline">{{ $level->language->name }}</a>
            <span class="mx-1">/</span>
        @endif
        <a href="{{ route('levels.show', $level) }}" class="text-indigo-600 hover:underline">{{ $level->name }}</a>
        <span class="mx-1">/</span>
        <span>طباعة</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">طباعة المستوى</p>
            <h1 class="page-title">{{ $level->name }}</h1>
            <p class="page-subtitle max-w-xl">
                اطبع كل البطاقات في المستوى، أو حدد مجموعات معيّنة ثم افتح الطباعة في نافذة جديدة.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('levels.show', $level) }}" class="btn btn-secondary">رجوع</a>
        </div>
    </section>

    @if ($level->decks->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm">
            لا توجد مجموعات في هذا المستوى للطباعة.
        </div>
    @else
        <section class="mb-8 rounded-3xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50/40 p-6 shadow-sm">
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <h2 class="mb-2 text-lg font-bold text-slate-900">طباعة مجموعات محددة</h2>
            <p class="mb-4 text-sm text-slate-500">
                كل الخيارات الآن في شريط واحد: بحث، تحديد الكل/إلغاء الكل، ثم اختيار نوع الطباعة.
            </p>

            <form action="{{ route('levels.print', $level) }}" method="GET" target="_blank" class="space-y-4" id="level-print-selected-form">
                <input type="hidden" name="selection" value="1">

                <div class="rounded-2xl border border-indigo-200 bg-white p-3 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">الشريط السريع</span>
                        <span id="selected-count" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">محدد: {{ $level->decks->count() }}</span>
                    </div>
                    <div class="mt-3 flex w-full flex-nowrap items-stretch gap-2" dir="ltr">
                        <button type="button" id="clear-all-decks" class="w-[110px] shrink-0 rounded-md px-2 py-1 text-sm font-extrabold leading-tight shadow-sm" style="border: 4px solid #111827; background: #ef4444; color: #111827;">
                            حذف الكل
                        </button>
                        <div class="min-w-0 flex-1">
                            <input
                                type="text"
                                id="deck-search-input"
                                placeholder="شريط البحث"
                                class="h-12 w-full rounded-md px-4 py-2 text-center text-2xl font-bold text-black placeholder:text-black focus:outline-none"
                                style="border: 4px solid #111827; background:rgb(255, 255, 255);"
                            >
                        </div>
                        <button type="button" id="select-all-decks" class="w-[110px] shrink-0 rounded-md px-2 py-1 text-sm font-extrabold leading-tight shadow-sm" style="border: 4px solid #111827; background: #22c55e; color: #111827;">
                            تحديد الكل
                        </button>
                        <button type="submit" name="mode" value="default" class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            طباعة افتراضية
                        </button>
                        <button type="submit" name="mode" value="custom" class="shrink-0 rounded-lg border border-violet-300 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-100">
                            طباعة مخصصة
                        </button>
                    </div>
                </div>

                <div class="text-xs text-slate-500">
                    المجموعات الظاهرة في القائمة تتأثر بالبحث، وعمليات تحديد/إلغاء الكل تطبّق على العناصر الظاهرة.
                </div>

                <div id="decks-list" class="max-h-72 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-3 text-sm shadow-inner">
                    @foreach ($level->decks as $deck)
                        <label class="deck-item flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 transition hover:border-indigo-300 hover:bg-indigo-50/40" data-deck-name="{{ Str::lower($deck->name) }}">
                            <input type="checkbox" name="deck_ids[]" value="{{ $deck->id }}" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="flex-1 font-medium text-slate-800">{{ $deck->name }}</span>
                            <span class="rounded-full bg-white px-2 py-1 text-xs text-slate-600">{{ $deck->cards_count }} بطاقة</span>
                        </label>
                    @endforeach
                </div>
            </form>
        </section>

        <script>
            (() => {
                const form = document.getElementById('level-print-selected-form');
                if (!form) return;

                const searchInput = document.getElementById('deck-search-input');
                const selectAllBtn = document.getElementById('select-all-decks');
                const clearAllBtn = document.getElementById('clear-all-decks');
                const selectedCount = document.getElementById('selected-count');
                const items = Array.from(form.querySelectorAll('.deck-item'));

                const normalize = (value) => (value || '').toLowerCase().trim();

                const applyFilter = () => {
                    const q = normalize(searchInput?.value);
                    items.forEach((item) => {
                        const name = item.getAttribute('data-deck-name') || '';
                        const visible = !q || name.includes(q);
                        item.classList.toggle('hidden', !visible);
                    });
                };

                const updateSelectedCount = () => {
                    const checked = form.querySelectorAll('input[name="deck_ids[]"]:checked').length;
                    if (selectedCount) selectedCount.textContent = `محدد: ${checked}`;
                };

                selectAllBtn?.addEventListener('click', () => {
                    items.forEach((item) => {
                        if (item.classList.contains('hidden')) return;
                        item.querySelector('input[type="checkbox"]')?.setAttribute('checked', 'checked');
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        if (checkbox) checkbox.checked = true;
                    });
                    updateSelectedCount();
                });

                clearAllBtn?.addEventListener('click', () => {
                    items.forEach((item) => {
                        if (item.classList.contains('hidden')) return;
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        if (checkbox) checkbox.checked = false;
                    });
                    updateSelectedCount();
                });

                searchInput?.addEventListener('input', applyFilter);
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
