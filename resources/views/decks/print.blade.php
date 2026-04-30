<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة - {{ $deck->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700|inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --print-page-width: {{ $printSettings['page_width_mm'] }}mm;
            --print-page-height: {{ $printSettings['page_height_mm'] }}mm;
            --print-padding: {{ $printSettings['padding_mm'] }}mm;
            --print-gap: {{ $printSettings['gap_mm'] }}mm;
            --print-card-width: {{ $printSettings['card_width_mm'] }}mm;
            --print-card-height: {{ $printSettings['card_height_mm'] }}mm;
            --print-border-width: {{ $printSettings['border_width_mm'] ?? 0.3 }}mm;
            --print-border-style: {{ $printSettings['border_style'] ?? 'solid' }};
        }

        @page {
            size: {{ $printSettings['css_page_size'] }};
            margin: 0;
        }

        @media print {
            html,
            body {
                width: {{ $printSettings['page_width_mm'] }}mm;
                height: {{ $printSettings['page_height_mm'] }}mm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">
    @php
        $isCustom = ($printSettings['mode'] ?? 'default') === 'custom';
        $cards = $deck->categories->flatMap->cards->values();
        $perPage = max(1, $printSettings['per_page']);
        $cols = $printSettings['cols'];
        $rows = $printSettings['rows'];
        $pages = $cards->chunk($perPage);
    @endphp

    <div class="print-toolbar no-print mx-auto max-w-5xl px-4 py-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold">طباعة "{{ $deck->name }}"</h1>
            <p class="text-xs text-slate-500">
                المستوى: {{ $deck->level?->name ?? '-' }}
                &middot; الورقة: {{ $printSettings['paper_size'] }}
                &middot; {{ $perPage }} بطاقة/صفحة ({{ $cols }} × {{ $rows }})
                &middot; {{ number_format($printSettings['page_width_mm'], 0) }}×{{ number_format($printSettings['page_height_mm'], 0) }}مم
                &middot; حجم البطاقة: {{ number_format($printSettings['card_width_mm'], 2) }}×{{ number_format($printSettings['card_height_mm'], 2) }}مم (عرض × طول)
                @if ($isCustom)
                    &middot; <span class="text-violet-700 font-semibold">وضع التخصيص</span>
                @endif
            </p>
            <p class="text-[11px] text-amber-700 mt-1">
                للطباعة الفعلية اجعل الهوامش "بلا" والقياس "100%".
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if (! $isCustom)
                <form action="{{ route('decks.print', $deck) }}" method="GET" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1 shadow-sm">
                    <label class="sr-only" for="print-paper-size">حجم الورقة</label>
                    <select id="print-paper-size" name="paper_size" class="rounded-md border-0 bg-transparent px-1 py-1 text-sm focus:ring-0">
                        <option value="A4" @selected($printSettings['paper_size'] === 'A4')>A4 — 9 بطاقات</option>
                        <option value="A3" @selected($printSettings['paper_size'] === 'A3')>A3 — 18 بطاقة</option>
                        <option value="A2" @selected($printSettings['paper_size'] === 'A2')>A2 — 36 بطاقة</option>
                        <option value="A1" @selected($printSettings['paper_size'] === 'A1')>A1 — 72 بطاقة</option>
                        <option value="A0" @selected($printSettings['paper_size'] === 'A0')>A0 — 144 بطاقة</option>
                    </select>
                    <button type="submit" class="px-3 py-2 rounded-md text-sm border border-slate-300 hover:bg-slate-50">تطبيق</button>
                    <button type="submit" name="print_now" value="1" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">طباعة الآن</button>
                </form>
            @else
                <button type="button" onclick="window.print()" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">طباعة الآن</button>
            @endif
            <a href="{{ route('decks.print.options', $deck) }}" class="px-3 py-2 rounded-md text-sm border border-slate-300 hover:bg-white">خيارات الطباعة</a>
            <a href="{{ route('decks.show', $deck) }}" class="px-3 py-2 rounded-md text-sm border border-slate-300 hover:bg-white">رجوع</a>
        </div>
    </div>

    @if ($isCustom)
        <div class="no-print mx-auto max-w-5xl px-4 pb-4">
            <form id="custom-print-form" action="{{ route('decks.print', $deck) }}" method="GET"
                  class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">
                <input type="hidden" name="mode" value="custom">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">تخصيص الطباعة</h2>
                        <span class="text-[11px] text-slate-500">القيم تطبق لمرة واحدة فقط</span>
                    </div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-[12px] text-violet-800">
                        حجم البطاقة الحالي:
                        <strong><span id="card-size-current-width">{{ number_format($printSettings['card_width_mm'], 2) }}</span> × <span id="card-size-current-height">{{ number_format($printSettings['card_height_mm'], 2) }}</span> مم</strong>
                        <span class="text-violet-700">(عرض × طول)</span>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h3 class="mb-3 text-sm font-bold text-slate-900">الإعدادات الأساسية</h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex flex-col gap-1 text-xs font-medium text-slate-600">
                                حجم الورقة
                                <select name="paper_size" class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                                    @foreach (['A4', 'A3', 'A2', 'A1', 'A0'] as $size)
                                        <option value="{{ $size }}" @selected($printSettings['paper_size'] === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-slate-600">
                                عدد الصفوف
                                <input type="number" name="rows" min="1" max="20" value="{{ $rows }}"
                                       class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-slate-600">
                                عدد الأعمدة
                                <input type="number" name="cols" min="1" max="20" value="{{ $cols }}"
                                       class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-slate-600">
                                المسافة بين البطاقات (مم)
                                <input type="number" name="card_gap_mm" min="0" max="30" step="0.5"
                                       value="{{ $printSettings['gap_mm'] }}"
                                       class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-slate-600 sm:col-span-2">
                                الحاشية (مم)
                                <input type="number" name="page_padding_mm" min="0" max="50" step="0.5"
                                       value="{{ $printSettings['padding_mm'] }}"
                                       class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                            </label>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-4">
                        <h3 class="mb-3 text-sm font-bold text-slate-900">الخيارات المتقدمة</h3>

                        <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                            الحجم المتوقع بعد التطبيق:
                            <strong><span id="card-size-live-width">{{ number_format($printSettings['card_width_mm'], 2) }}</span> × <span id="card-size-live-height">{{ number_format($printSettings['card_height_mm'], 2) }}</span> مم</strong>
                            <span class="text-emerald-700">(عرض × طول)</span>
                        </div>

                        <details class="rounded-lg border border-slate-200 bg-slate-50 p-3" open>
                            <summary class="cursor-pointer text-xs font-semibold text-slate-700">حدود البطاقة</summary>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label class="flex flex-col gap-1 text-xs font-medium text-slate-600">
                                    سُمك حدود البطاقة (مم)
                                    <input type="number" name="border_width_mm" min="0" max="5" step="0.1"
                                           value="{{ $printSettings['border_width_mm'] ?? 0.3 }}"
                                           class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                                </label>
                                <p class="text-[11px] text-slate-500 sm:col-span-2">إذا كان السمك 0 ستختفي الحدود. لاستخدام بدون إطار اختر "بدون" من نمط الحدود.</p>

                                <label class="flex flex-col gap-1 text-xs font-medium text-slate-600">
                                    نمط الحدود
                                    <select name="border_style" class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm">
                                        @foreach (['solid' => 'متصل', 'dashed' => 'متقطع', 'dotted' => 'منقط', 'double' => 'مزدوج', 'none' => 'بدون'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($printSettings['border_style'] ?? 'solid') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </details>
                    </section>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2" style="direction: rtl;">
                    <button type="submit" name="apply_only" value="1" title="تطبيق الإعدادات فقط"
                            class="px-4 py-2 rounded-md text-sm font-semibold"
                            style="min-width:96px;background:#059669;color:#fff;border:1px solid #047857;">
                        تطبيق
                    </button>
                    <button type="button" onclick="window.print()" title="فتح نافذة الطباعة"
                            class="px-4 py-2 rounded-md text-sm font-semibold"
                            style="min-width:96px;background:#4f46e5;color:#fff;border:1px solid #4338ca;">
                        طباعة
                    </button>
                    <button type="button" id="reset-custom-form" title="حذف التخصيص والعودة للوضع الافتراضي"
                            class="px-3 py-2 rounded-md text-sm"
                            style="min-width:140px;background:#fff;color:#334155;border:1px solid #cbd5e1;">
                        الإعدادات الافتراضية
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($cards->isEmpty())
        <div class="no-print mx-auto max-w-5xl px-4 py-12 text-center text-slate-500">لا توجد بطاقات للطباعة في هذه المجموعة.</div>
    @else
        @foreach ($pages as $pageCards)
            @php
                $pageRows = max(1, (int) ceil($pageCards->count() / $cols));
                $pageSlots = $pageRows * $cols;
                $frontCards = $pageCards->pad($pageSlots, null)->values();
                $pageBackOrder = collect(range(0, $pageSlots - 1))->map(function ($index) use ($cols) {
                    $row = intdiv($index, $cols);
                    $col = $index % $cols;
                    return $row * $cols + ($cols - 1 - $col);
                })->all();
                $backCards = collect($pageBackOrder)->map(fn ($index) => $frontCards[$index]);
            @endphp
            <section class="print-page">
                <div class="print-grid" style="grid-template-columns: repeat({{ $cols }}, {{ $printSettings['card_width_mm'] }}mm); grid-template-rows: repeat({{ $pageRows }}, {{ $printSettings['card_height_mm'] }}mm);">
                    @foreach ($frontCards as $card)
                        @if ($card)
                            <x-flashcard-front :card="$card" class="print-cell" />
                        @else
                            <div class="print-cell print-cell--empty"></div>
                        @endif
                    @endforeach
                </div>
            </section>

            <section class="print-page">
                <div class="print-grid" style="grid-template-columns: repeat({{ $cols }}, {{ $printSettings['card_width_mm'] }}mm); grid-template-rows: repeat({{ $pageRows }}, {{ $printSettings['card_height_mm'] }}mm);">
                    @foreach ($backCards as $card)
                        @if ($card)
                            <x-flashcard-back :card="$card" class="print-cell" />
                        @else
                            <div class="print-cell print-cell--empty"></div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif

    @if (request()->query('print_now') === '1')
        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    @endif

    @if ($isCustom)
        <script>
            (function () {
                const STORAGE_KEY = 'deckPrintCustom:{{ $deck->id }}';
                const form = document.getElementById('custom-print-form');
                if (! form) {
                    return;
                }

                const FIELDS = ['paper_size', 'rows', 'cols', 'page_padding_mm', 'card_gap_mm', 'border_width_mm', 'border_style'];
                const paperSizes = {
                    A4: { w: 210, h: 297 },
                    A3: { w: 420, h: 297 },
                    A2: { w: 420, h: 594 },
                    A1: { w: 841, h: 594 },
                    A0: { w: 841, h: 1189 },
                };

                function readFormValues() {
                    const data = {};
                    FIELDS.forEach((name) => {
                        const field = form.elements.namedItem(name);
                        if (field) {
                            data[name] = field.value;
                        }
                    });
                    return data;
                }

                function updateLiveCardSize() {
                    const sizeField = form.elements.namedItem('paper_size');
                    const rowsField = form.elements.namedItem('rows');
                    const colsField = form.elements.namedItem('cols');
                    const paddingField = form.elements.namedItem('page_padding_mm');
                    const gapField = form.elements.namedItem('card_gap_mm');
                    const liveWidth = document.getElementById('card-size-live-width');
                    const liveHeight = document.getElementById('card-size-live-height');

                    if (! sizeField || ! rowsField || ! colsField || ! paddingField || ! gapField || ! liveWidth || ! liveHeight) {
                        return;
                    }

                    const paper = paperSizes[sizeField.value] ?? paperSizes.A4;
                    const rows = Math.max(1, parseInt(rowsField.value || '1', 10));
                    const cols = Math.max(1, parseInt(colsField.value || '1', 10));
                    const padding = Math.max(0, parseFloat(paddingField.value || '0'));
                    const gap = Math.max(0, parseFloat(gapField.value || '0'));

                    const usableWidth = Math.max(0, paper.w - 2 * padding - Math.max(0, cols - 1) * gap);
                    const usableHeight = Math.max(0, paper.h - 2 * padding - Math.max(0, rows - 1) * gap);
                    const cardWidth = usableWidth / cols;
                    const cardHeight = usableHeight / rows;

                    liveWidth.textContent = cardWidth.toFixed(2);
                    liveHeight.textContent = cardHeight.toFixed(2);
                }

                function saveCurrent() {
                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(readFormValues()));
                    } catch (error) {
                        // localStorage may be unavailable (private mode/quota), fail silently
                    }
                }

                function clearSaved() {
                    try {
                        localStorage.removeItem(STORAGE_KEY);
                    } catch (error) {
                        // ignore
                    }
                }

                const params = new URLSearchParams(window.location.search);
                const hasUserValues = FIELDS.some((name) => params.has(name));
                let saved = null;
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    saved = raw ? JSON.parse(raw) : null;
                } catch (error) {
                    saved = null;
                }

                if (hasUserValues) {
                    saveCurrent();
                } else if (saved && Object.keys(saved).length > 0) {
                    const wantsRestore = window.confirm('هل تريد استخدام تخصيص الطباعة السابق؟');
                    if (wantsRestore) {
                        const restoreUrl = new URL(window.location.href);
                        FIELDS.forEach((name) => {
                            if (saved[name] !== undefined && saved[name] !== null && saved[name] !== '') {
                                restoreUrl.searchParams.set(name, saved[name]);
                            }
                        });
                        restoreUrl.searchParams.set('mode', 'custom');
                        window.location.replace(restoreUrl.toString());
                        return;
                    } else {
                        clearSaved();
                    }
                }

                const resetButton = document.getElementById('reset-custom-form');
                if (resetButton) {
                    resetButton.addEventListener('click', () => {
                        clearSaved();
                        const url = new URL(window.location.href);
                        FIELDS.forEach((name) => url.searchParams.delete(name));
                        url.searchParams.set('mode', 'custom');
                        window.location.assign(url.toString());
                    });
                }

                ['paper_size', 'rows', 'cols', 'page_padding_mm', 'card_gap_mm'].forEach((fieldName) => {
                    const field = form.elements.namedItem(fieldName);
                    if (field) {
                        field.addEventListener('input', updateLiveCardSize);
                        field.addEventListener('change', updateLiveCardSize);
                    }
                });

                updateLiveCardSize();
            })();
        </script>
    @endif
</body>
</html>
