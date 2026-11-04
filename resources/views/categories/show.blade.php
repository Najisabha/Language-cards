@extends('layouts.app')

@section('title', $deck->name.' — '.$category->name)

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
            <p class="page-eyebrow">{{ $category->name }}</p>
            <h1 class="page-title">{{ $deck->name }}</h1>
        </div>
        @php
            $soleCategoryInDeck = $soleCategoryInDeck ?? false;
            $backUrl = $soleCategoryInDeck && $deck->level
                ? route('levels.show', $deck->level)
                : route('decks.show', ['deck' => $deck, 'manage' => 1]);
            $backTitle = $soleCategoryInDeck ? 'رجوع للمستوى' : 'رجوع للنوع';
        @endphp
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            {{-- رجوع --}}
            <a href="{{ $backUrl }}"
               title="{{ $backTitle }}"
               aria-label="{{ $backTitle }}"
               class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90"
               style="background-color:#64748b;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M4 12h11" stroke-linecap="round"></path>
                </svg>
            </a>
            @if ($soleCategoryInDeck)
                <x-icon-action :href="route('decks.print.options', $deck)" title="طباعة" color="#0ea5e9">
                    <x-icons.print />
                </x-icon-action>
                @admin
                    <x-icon-action :href="route('decks.edit', $deck)" title="تعديل النوع" color="#8b5cf6">
                        <x-icons.edit />
                    </x-icon-action>
                @endadmin
            @endif
            @admin
                {{-- بطاقة جديدة --}}
                <a href="{{ route('categories.cards.create', $category) }}"
                   title="بطاقة جديدة"
                   aria-label="بطاقة جديدة"
                   class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90"
                   style="background-color:#6366f1;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" class="h-5 w-5" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round"></path>
                    </svg>
                </a>
                {{-- ربط صور من مجلد --}}
                <button type="button"
                        id="match-media-folder-open"
                        title="ربط صور من مجلد"
                        aria-label="ربط صور من مجلد"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-45"
                        style="background-color:#d946ef;"
                        @if(($totalCardsCount ?? $cards->count()) === 0) disabled @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                        <path d="M3 8a2 2 0 012-2h3.2l1.4 1.8H19a2 2 0 012 2V17a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" stroke-linejoin="round"></path>
                        <rect x="8.2" y="11.2" width="7.6" height="5.6" rx="1"></rect>
                        <circle cx="10.4" cy="13.4" r="0.85" fill="#fff" stroke="none"></circle>
                        <path d="M15.6 16.2l-2.1-2.1-3.3 2.7" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
            @endadmin
        </div>
    </section>

    @admin
        <div id="match-media-folder-modal"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4"
             role="dialog"
             aria-modal="true"
             aria-labelledby="match-media-folder-title">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 id="match-media-folder-title" class="text-lg font-bold text-slate-900">ربط صور من مجلد</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            اكتب اسم مجلد من مكتبة الصور. سيتم مطابقة أسماء الصور مع كلمات البطاقات تلقائيًا.
                        </p>
                    </div>
                    <button type="button" id="match-media-folder-close" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="إغلاق">×</button>
                </div>

                <form method="POST" action="{{ route('categories.match-media-folder', $category) }}" class="space-y-4" id="match-media-folder-form">
                    @csrf
                    <input type="hidden" name="folder_id" id="match-media-folder-id" value="">
                    <div class="relative">
                        <label for="match-media-folder-name" class="mb-1 block text-sm font-medium text-slate-700">اسم المجلد</label>
                        <input type="text"
                               id="match-media-folder-name"
                               name="folder_name"
                               required
                               maxlength="120"
                               autocomplete="off"
                               placeholder="مثال: Countries"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <ul id="match-media-folder-suggestions"
                            class="absolute z-10 mt-1 hidden max-h-48 w-full overflow-auto rounded-md border border-slate-200 bg-white py-1 text-sm shadow-lg"></ul>
                        <p class="mt-1 text-xs text-slate-500">المقارنة بدون ذكاء اصطناعي: اسم الصورة ≈ الكلمة / المعنى الإنجليزي / العربي.</p>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" id="match-media-folder-cancel" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" class="btn btn-primary">ربط تلقائي</button>
                    </div>
                </form>
            </div>
        </div>
    @endadmin

    @php
        $allCount = $totalCardsCount ?? $cards->count();
        $currentQ = $q ?? request('q');
    @endphp

    @if ($allCount === 0)
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد بطاقات في «{{ $category->name }}» بعد.</p>
            @admin
                <a href="{{ route('categories.cards.create', $category) }}" class="btn btn-primary">أضف أول بطاقة</a>
            @endadmin
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
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">{{ $category->name }}</span>
                            @admin
                                <div class="flex items-center gap-1 text-xs">
                                    <a href="{{ route('cards.edit', $card) }}" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">تعديل</a>
                                    <form method="POST" action="{{ route('cards.destroy', $card) }}" onsubmit="return confirm('حذف البطاقة؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md px-2 py-1 text-red-600 hover:bg-red-50">حذف</button>
                                    </form>
                                </div>
                            @endadmin
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

@admin
@push('scripts')
<script>
(() => {
    const openBtn = document.getElementById('match-media-folder-open');
    const modal = document.getElementById('match-media-folder-modal');
    if (!openBtn || !modal) return;

    const closeBtn = document.getElementById('match-media-folder-close');
    const cancelBtn = document.getElementById('match-media-folder-cancel');
    const nameInput = document.getElementById('match-media-folder-name');
    const folderIdInput = document.getElementById('match-media-folder-id');
    const suggestions = document.getElementById('match-media-folder-suggestions');
    const searchUrl = @json(route('media-folders.search'));
    let searchTimer = null;

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        folderIdInput.value = '';
        nameInput.value = '';
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
        setTimeout(() => nameInput.focus(), 0);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
    };

    const pickFolder = (id, name) => {
        folderIdInput.value = String(id);
        nameInput.value = name;
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
    };

    const renderSuggestions = (items) => {
        suggestions.innerHTML = '';
        if (!items.length) {
            suggestions.classList.add('hidden');
            return;
        }

        items.forEach((item) => {
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'flex w-full items-center justify-between gap-2 px-3 py-2 text-start hover:bg-slate-50';

            const nameSpan = document.createElement('span');
            nameSpan.className = 'font-medium text-slate-800';
            nameSpan.textContent = item.name;

            const countSpan = document.createElement('span');
            countSpan.className = 'text-xs text-slate-500';
            countSpan.textContent = `${item.images_count} صورة`;

            btn.appendChild(nameSpan);
            btn.appendChild(countSpan);
            btn.addEventListener('click', () => pickFolder(item.id, item.name));
            li.appendChild(btn);
            suggestions.appendChild(li);
        });
        suggestions.classList.remove('hidden');
    };

    const searchFolders = (q) => {
        const query = q.trim();
        folderIdInput.value = '';
        if (query.length < 1) {
            suggestions.classList.add('hidden');
            suggestions.innerHTML = '';
            return;
        }

        fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' },
        })
            .then((res) => res.ok ? res.json() : { data: [] })
            .then((payload) => renderSuggestions(Array.isArray(payload.data) ? payload.data : []))
            .catch(() => renderSuggestions([]));
    };

    openBtn.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    nameInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => searchFolders(nameInput.value), 220);
    });
})();
</script>
@endpush
@endadmin
