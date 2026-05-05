@props(['card' => null, 'category', 'deck' => null])

@php
    $deck = $deck ?? $category->deck;
    $card = $card ?? new \App\Models\Card([
        'front_bg_type' => 'color',
        'front_bg_value' => '#ffffff',
        'show_en' => true,
        'show_ar' => true,
        'show_explanation' => false,
        'show_icon' => false,
    ]);

    $initialFrontType = old('front_bg_type', $card->front_bg_type ?? 'color');
    $initialFrontValue = old('front_bg_value', $card->front_bg_value ?? '#ffffff');
    $isGradient = (bool) preg_match('/^(linear|radial|conic)-gradient/i', $initialFrontValue);
    $initialColorMode = old('front_color_mode', $isGradient ? 'gradient' : 'solid');
    $iconImageUrl = $card->iconImageUrl();
@endphp

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="rounded-lg bg-indigo-50 border border-indigo-100 p-3 text-sm text-indigo-700">
            سيتم حفظ هذه البطاقة داخل المجموعة: <strong>{{ $deck->name }}</strong>
        </div>

        <div data-ai-suggest-url="{{ route('cards.ai-suggest') }}"
             data-duplicate-check-url="{{ route('cards.check-duplicate-word') }}"
             data-deck-id="{{ $deck->id }}"
             data-card-id="{{ $card->id ?? '' }}">
            <label for="word" class="block text-sm font-medium mb-1">الكلمة (تظهر في الوجه الأمامي) <span class="text-red-500">*</span></label>
            <div class="flex flex-wrap gap-2 items-stretch">
                <input type="text" id="word" name="word" required maxlength="120" dir="ltr"
                       value="{{ old('word', $card->word) }}"
                       class="flex-1 min-w-[12rem] rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="button" id="ai-suggest-btn"
                        class="shrink-0 px-4 py-2 rounded-md border border-indigo-200 bg-indigo-50 text-indigo-800 text-sm font-semibold hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed">
                    AI
                </button>
            </div>
            <p id="ai-suggest-error" class="text-xs text-red-600 mt-1 hidden" role="alert"></p>
            <p id="duplicate-word-warning" class="text-xs text-amber-600 mt-1 hidden" role="alert">هذه الكلمة موجودة بالفعل في نفس اللغة.</p>
            @error('word') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-2 text-sm font-semibold">الوجه الأمامي</legend>
            <div class="flex flex-wrap items-center gap-4 mb-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="front_bg_type" value="color" {{ $initialFrontType === 'color' ? 'checked' : '' }}>
                    لون خلفية
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="front_bg_type" value="image" {{ $initialFrontType === 'image' ? 'checked' : '' }}>
                    رابط صورة
                </label>
            </div>

            <div data-front-color-options class="{{ $initialFrontType === 'color' ? '' : 'hidden' }} mb-3">
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="front_color_mode" value="solid" {{ $initialColorMode === 'solid' ? 'checked' : '' }}>
                        لون واحد
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="front_color_mode" value="gradient" {{ $initialColorMode === 'gradient' ? 'checked' : '' }}>
                        ألوان مدموجة (تدرج)
                    </label>
                </div>
            </div>

            <div data-gradient-builder class="{{ $initialFrontType === 'color' && $initialColorMode === 'gradient' ? '' : 'hidden' }} mb-3 p-3 rounded-md bg-slate-50 border border-slate-200">
                <p class="text-xs font-semibold text-slate-600 mb-2">منشئ التدرج</p>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">اللون الأول</label>
                        <input type="color" data-gradient-color-1 value="#a78bfa" class="w-full h-10 rounded border border-slate-300 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">اللون الثاني</label>
                        <input type="color" data-gradient-color-2 value="#f472b6" class="w-full h-10 rounded border border-slate-300 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">الاتجاه</label>
                        <select data-gradient-direction class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm">
                            <option value="135deg">قطري (↘)</option>
                            <option value="45deg">قطري (↗)</option>
                            <option value="to right">يسار → يمين</option>
                            <option value="to left">يمين → يسار</option>
                            <option value="to bottom">أعلى → أسفل</option>
                            <option value="to top">أسفل → أعلى</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xs font-semibold text-slate-600 mb-2">تدرجات جاهزة</p>
                    <div class="flex flex-wrap gap-2" data-gradient-presets>
                        <button type="button" class="h-8 w-12 rounded border border-slate-200 cursor-pointer" style="background: linear-gradient(135deg, #a78bfa, #f472b6);" data-preset="linear-gradient(135deg, #a78bfa, #f472b6)" title="بنفسجي وردي"></button>
                        <button type="button" class="h-8 w-12 rounded border border-slate-200 cursor-pointer" style="background: linear-gradient(135deg, #fde68a, #fb7185);" data-preset="linear-gradient(135deg, #fde68a, #fb7185)" title="أصفر مرجاني"></button>
                        <button type="button" class="h-8 w-12 rounded border border-slate-200 cursor-pointer" style="background: linear-gradient(135deg, #60a5fa, #34d399);" data-preset="linear-gradient(135deg, #60a5fa, #34d399)" title="أزرق أخضر"></button>
                        <button type="button" class="h-8 w-12 rounded border border-slate-200 cursor-pointer" style="background: linear-gradient(135deg, #f97316, #ec4899);" data-preset="linear-gradient(135deg, #f97316, #ec4899)" title="برتقالي وردي"></button>
                        <button type="button" class="h-8 w-12 rounded border border-slate-200 cursor-pointer" style="background: linear-gradient(135deg, #0ea5e9, #6366f1);" data-preset="linear-gradient(135deg, #0ea5e9, #6366f1)" title="سماوي بنفسجي"></button>
                        <button type="button" class="h-8 w-12 rounded border border-slate-200 cursor-pointer" style="background: linear-gradient(135deg, #1f2937, #6b7280);" data-preset="linear-gradient(135deg, #1f2937, #6b7280)" title="رمادي داكن"></button>
                    </div>
                </div>
            </div>

            <div>
                <label for="front_bg_value" class="block text-sm font-medium mb-1">قيمة الخلفية</label>
                <input type="text" id="front_bg_value" name="front_bg_value" value="{{ $initialFrontValue }}" dir="ltr"
                       placeholder="#ffffff أو linear-gradient(135deg, #a78bfa, #f472b6) أو رابط صورة"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-slate-500 mt-1">
                    استخدم Hex مثل <code dir="ltr">#fce7f3</code>، أو تدرج CSS مثل
                    <code dir="ltr">linear-gradient(135deg, #a78bfa, #f472b6)</code>.
                </p>
                @error('front_bg_value') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </fieldset>

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-2 text-sm font-semibold">الوجه الخلفي</legend>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="en_meaning" class="block text-sm font-medium mb-1">المعنى بالإنجليزية</label>
                    <input type="text" id="en_meaning" name="en_meaning" maxlength="255" dir="ltr" value="{{ old('en_meaning', $card->en_meaning) }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="ar_meaning" class="block text-sm font-medium mb-1">المعنى بالعربية</label>
                    <input type="text" id="ar_meaning" name="ar_meaning" maxlength="255" value="{{ old('ar_meaning', $card->ar_meaning) }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="explanation" class="block text-sm font-medium mb-1">شرح/مثال</label>
                    <textarea id="explanation" name="explanation" rows="2" maxlength="1000"
                              class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('explanation', $card->explanation) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">إيموجي/أيقونة</label>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <input type="text" id="icon" name="icon" maxlength="16" value="{{ old('icon', $card->icon) }}" placeholder="📚"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="text-xs text-slate-500 mt-1">إيموجي نصي.</p>
                        </div>
                        <div data-ai-icon-url="{{ route('cards.ai-icon-image') }}">
                            <label for="icon_image" class="sr-only">صورة الأيقونة</label>
                            <div class="flex flex-wrap gap-2 items-center">
                                <input type="file" id="icon_image" name="icon_image" accept="image/*"
                                       class="flex-1 min-w-[10rem] text-sm text-slate-700 file:ml-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:text-indigo-700 hover:file:bg-indigo-100">
                                <button type="button" id="ai-icon-btn"
                                        class="shrink-0 px-3 py-2 rounded-md border border-violet-200 bg-violet-50 text-violet-800 text-sm font-semibold hover:bg-violet-100 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                                    صورة من الويب
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">رفع صورة (تأخذ الأولوية على الإيموجي)، أو جلب صورة مجانية من ويكيميديا كومنز بحسب الكلمة في الوجه الأمامي.</p>
                            <p id="ai-icon-error" class="text-xs text-red-600 mt-1 hidden" role="alert"></p>
                            @error('icon_image') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                            @if ($iconImageUrl)
                                <div class="mt-2 flex items-center gap-2">
                                    <img src="{{ $iconImageUrl }}" alt="" class="h-10 w-10 rounded object-cover border border-slate-200">
                                    <label class="inline-flex items-center gap-1 text-xs text-red-600 cursor-pointer">
                                        <input type="checkbox" name="remove_icon_image" value="1"> حذف الصورة الحالية
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid sm:grid-cols-2 gap-2 text-sm">
                <label class="inline-flex items-center gap-2"><input type="checkbox" id="show_en" name="show_en" value="1" {{ old('show_en', $card->show_en) ? 'checked' : '' }}> إظهار المعنى بالإنجليزية</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" id="show_ar" name="show_ar" value="1" {{ old('show_ar', $card->show_ar) ? 'checked' : '' }}> إظهار المعنى بالعربية</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" id="show_explanation" name="show_explanation" value="1" {{ old('show_explanation', $card->show_explanation) ? 'checked' : '' }}> إظهار الشرح</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" id="show_icon" name="show_icon" value="1" {{ old('show_icon', $card->show_icon) ? 'checked' : '' }}> إظهار الأيقونة</label>
            </div>
        </fieldset>
    </div>

    <aside class="lg:sticky lg:top-6 self-start">
        <p class="text-sm font-semibold mb-2">معاينة</p>
        <div class="space-y-3">
            <div>
                <p class="text-xs text-slate-500 mb-1">الوجه الأمامي</p>
                <div id="preview-front" class="flashcard-face relative flex items-center justify-center text-center p-3 border border-slate-200 aspect-[3/2] rounded-lg">
                    <div id="preview-front-overlay" class="absolute inset-0 bg-slate-900/15 hidden"></div>
                    <span id="preview-word" class="relative z-10 text-2xl md:text-3xl font-extrabold text-slate-900 drop-shadow-sm" dir="ltr">{{ old('word', $card->word) ?: 'word' }}</span>
                </div>
            </div>
            <div>
                <p class="text-xs text-slate-500 mb-1">الوجه الخلفي</p>
                <div class="flashcard-back-split flashcard-face bg-white border border-slate-200 aspect-[3/2] rounded-lg overflow-hidden">
                    <div class="flashcard-back-media">
                        <img id="preview-icon-image" src="{{ $iconImageUrl }}" alt="" class="flashcard-back-image {{ $iconImageUrl && old('show_icon', $card->show_icon) ? '' : 'hidden' }}">
                        <div id="preview-icon" class="flashcard-back-emoji {{ ! $iconImageUrl && old('show_icon', $card->show_icon) && old('icon', $card->icon) ? '' : 'hidden' }}">{{ old('icon', $card->icon) }}</div>
                    </div>
                    <div class="flashcard-back-text">
                        <p id="preview-ar" class="flashcard-back-ar {{ old('show_ar', $card->show_ar) && old('ar_meaning', $card->ar_meaning) ? '' : 'hidden' }}">{{ old('ar_meaning', $card->ar_meaning) }}</p>
                        <p id="preview-en" class="flashcard-back-en {{ old('show_en', $card->show_en) && old('en_meaning', $card->en_meaning) ? '' : 'hidden' }}" dir="ltr">{{ old('en_meaning', $card->en_meaning) }}</p>
                        <p id="preview-explanation" class="flashcard-back-explanation {{ old('show_explanation', $card->show_explanation) && old('explanation', $card->explanation) ? '' : 'hidden' }}">{{ old('explanation', $card->explanation) }}</p>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-2">أي تعديل هنا يظهر مباشرة في المعاينة.</p>
    </aside>
</div>

<script>
(() => {
    if (window.__flashcardLivePreviewInitialized) return;
    window.__flashcardLivePreviewInitialized = true;

    const byId = (id) => document.getElementById(id);
    const qs = (sel, root = document) => root.querySelector(sel);
    const qsa = (sel, root = document) => root.querySelectorAll(sel);

    const word = byId('word');
    const frontBgValue = byId('front_bg_value');
    const frontBgTypeInputs = qsa('input[name="front_bg_type"]');
    const colorModeInputs = qsa('input[name="front_color_mode"]');
    const colorOptionsBox = qs('[data-front-color-options]');
    const gradientBuilder = qs('[data-gradient-builder]');
    const gradientColor1 = qs('[data-gradient-color-1]');
    const gradientColor2 = qs('[data-gradient-color-2]');
    const gradientDirection = qs('[data-gradient-direction]');
    const gradientPresets = qsa('[data-gradient-presets] [data-preset]');

    const enMeaning = byId('en_meaning');
    const arMeaning = byId('ar_meaning');
    const explanation = byId('explanation');
    const icon = byId('icon');
    const iconImage = byId('icon_image');
    const removeIconImage = qs('input[name="remove_icon_image"]');
    const showEn = byId('show_en');
    const showAr = byId('show_ar');
    const showExplanation = byId('show_explanation');
    const showIcon = byId('show_icon');

    const previewFront = byId('preview-front');
    const previewFrontOverlay = byId('preview-front-overlay');
    const previewWord = byId('preview-word');
    const previewEn = byId('preview-en');
    const previewAr = byId('preview-ar');
    const previewExplanation = byId('preview-explanation');
    const previewIcon = byId('preview-icon');
    const previewIconImage = byId('preview-icon-image');
    const previewMedia = qs('.flashcard-back-split .flashcard-back-media');

    const initialIconImageUrl = previewIconImage ? previewIconImage.getAttribute('src') || '' : '';
    let uploadedIconImageUrl = '';

    const isHex = (value) => /^#[0-9A-Fa-f]{6}$/.test(value);
    const isGradient = (value) => /^(linear|radial|conic)-gradient\s*\(.+\)$/i.test(value);
    const setVisible = (el, visible) => el && el.classList.toggle('hidden', !visible);
    const currentFrontType = () => (qs('input[name="front_bg_type"]:checked') || {}).value || 'color';
    const currentColorMode = () => (qs('input[name="front_color_mode"]:checked') || {}).value || 'solid';

    const buildGradient = () => {
        const c1 = gradientColor1?.value || '#a78bfa';
        const c2 = gradientColor2?.value || '#f472b6';
        const dir = gradientDirection?.value || '135deg';
        return `linear-gradient(${dir}, ${c1}, ${c2})`;
    };

    const syncBuilderVisibility = () => {
        const type = currentFrontType();
        const mode = currentColorMode();
        setVisible(colorOptionsBox, type === 'color');
        setVisible(gradientBuilder, type === 'color' && mode === 'gradient');
    };

    const applyFrontPreview = () => {
        if (!previewFront) return;
        const type = currentFrontType();
        const value = (frontBgValue?.value || '').trim();

        previewFront.style.background = '';
        previewFront.style.backgroundImage = '';
        previewFront.style.backgroundColor = '';

        if (type === 'image') {
            previewFront.style.backgroundImage = value ? `url('${value.replace(/'/g, "\\'")}')` : 'none';
            previewFront.style.backgroundSize = 'cover';
            previewFront.style.backgroundPosition = 'center';
            previewFront.style.backgroundColor = '#ffffff';
            setVisible(previewFrontOverlay, true);
            return;
        }

        if (isGradient(value)) {
            previewFront.style.background = value;
        } else if (isHex(value)) {
            previewFront.style.backgroundColor = value;
        } else {
            previewFront.style.backgroundColor = '#ffffff';
        }
        setVisible(previewFrontOverlay, false);
    };

    const computeIconImageUrl = () => {
        if (removeIconImage?.checked) return '';
        if (uploadedIconImageUrl) return uploadedIconImageUrl;
        return initialIconImageUrl || '';
    };

    const render = () => {
        if (previewWord) previewWord.textContent = (word?.value || '').trim() || 'word';

        applyFrontPreview();

        const showArVal = !!showAr?.checked && !!(arMeaning?.value || '').trim();
        const showEnVal = !!showEn?.checked && !!(enMeaning?.value || '').trim();
        const showExpVal = !!showExplanation?.checked && !!(explanation?.value || '').trim();
        const showIconVal = !!showIcon?.checked;

        if (previewAr) { previewAr.textContent = arMeaning?.value || ''; setVisible(previewAr, showArVal); }
        if (previewEn) { previewEn.textContent = enMeaning?.value || ''; setVisible(previewEn, showEnVal); }
        if (previewExplanation) { previewExplanation.textContent = explanation?.value || ''; setVisible(previewExplanation, showExpVal); }

        const imgUrl = computeIconImageUrl();
        const hasImage = !!imgUrl;
        const emojiVal = (icon?.value || '').trim();

        if (previewIconImage) {
            if (hasImage) {
                previewIconImage.setAttribute('src', imgUrl);
            } else {
                previewIconImage.removeAttribute('src');
            }
            setVisible(previewIconImage, showIconVal && hasImage);
        }
        if (previewIcon) {
            previewIcon.textContent = emojiVal;
            setVisible(previewIcon, showIconVal && !hasImage && !!emojiVal);
        }
        if (previewMedia) {
            const hasVisibleMedia = showIconVal && (hasImage || !!emojiVal);
            previewMedia.classList.toggle('is-empty', !hasVisibleMedia);
        }
    };

    [word, frontBgValue, enMeaning, arMeaning, explanation, icon, showEn, showAr, showExplanation, showIcon]
        .filter(Boolean)
        .forEach((el) => el.addEventListener('input', render));

    frontBgTypeInputs.forEach((radio) => radio.addEventListener('change', () => {
        syncBuilderVisibility();
        render();
    }));

    colorModeInputs.forEach((radio) => radio.addEventListener('change', () => {
        syncBuilderVisibility();
        if (currentColorMode() === 'gradient' && frontBgValue) {
            frontBgValue.value = buildGradient();
        }
        render();
    }));

    [gradientColor1, gradientColor2, gradientDirection].filter(Boolean).forEach((el) => {
        el.addEventListener('input', () => {
            if (frontBgValue) frontBgValue.value = buildGradient();
            render();
        });
        el.addEventListener('change', () => {
            if (frontBgValue) frontBgValue.value = buildGradient();
            render();
        });
    });

    gradientPresets.forEach((btn) => btn.addEventListener('click', () => {
        const value = btn.getAttribute('data-preset') || '';
        if (frontBgValue) frontBgValue.value = value;
        const colorRadio = qs('input[name="front_color_mode"][value="gradient"]');
        if (colorRadio) colorRadio.checked = true;
        syncBuilderVisibility();
        render();
    }));

    if (iconImage) {
        iconImage.addEventListener('change', () => {
            const file = iconImage.files && iconImage.files[0];
            if (uploadedIconImageUrl) {
                URL.revokeObjectURL(uploadedIconImageUrl);
                uploadedIconImageUrl = '';
            }
            if (file) {
                uploadedIconImageUrl = URL.createObjectURL(file);
                if (removeIconImage) removeIconImage.checked = false;
            }
            render();
        });
    }

    if (removeIconImage) {
        removeIconImage.addEventListener('change', render);
    }

    syncBuilderVisibility();
    render();

    const aiSuggestBtn = byId('ai-suggest-btn');
    const aiSuggestError = byId('ai-suggest-error');
    const aiSuggestUrl = qs('[data-ai-suggest-url]')?.getAttribute('data-ai-suggest-url');
    const duplicateWarning = byId('duplicate-word-warning');
    const duplicateCheckBox = qs('[data-duplicate-check-url]');
    const duplicateCheckUrl = duplicateCheckBox?.getAttribute('data-duplicate-check-url');
    const duplicateDeckId = duplicateCheckBox?.getAttribute('data-deck-id');
    const duplicateCardId = duplicateCheckBox?.getAttribute('data-card-id');
    let duplicateCheckTimer = null;
    let duplicateCheckSeq = 0;

    const setAiError = (msg) => {
        if (!aiSuggestError) return;
        if (msg) {
            aiSuggestError.textContent = msg;
            aiSuggestError.classList.remove('hidden');
        } else {
            aiSuggestError.textContent = '';
            aiSuggestError.classList.add('hidden');
        }
    };

    const setDuplicateWarning = (visible) => {
        if (!duplicateWarning) return;
        duplicateWarning.classList.toggle('hidden', !visible);
    };

    const checkDuplicateWord = async () => {
        if (!duplicateCheckUrl || !word || !duplicateDeckId) return;
        const rawWord = (word.value || '').trim();
        if (!rawWord) {
            setDuplicateWarning(false);
            return;
        }

        const tokenInput = document.querySelector('input[name="_token"]');
        const token = tokenInput?.value;
        if (!token) return;

        const seq = ++duplicateCheckSeq;
        try {
            const res = await fetch(duplicateCheckUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    word: rawWord,
                    deck_id: Number(duplicateDeckId),
                    card_id: duplicateCardId ? Number(duplicateCardId) : null,
                }),
            });
            if (!res.ok || seq !== duplicateCheckSeq) return;
            const data = await res.json();
            setDuplicateWarning(!!data.duplicate);
        } catch {
            // Keep the form usable even if live check fails.
        }
    };

    const scheduleDuplicateCheck = () => {
        if (duplicateCheckTimer) window.clearTimeout(duplicateCheckTimer);
        duplicateCheckTimer = window.setTimeout(() => {
            checkDuplicateWord();
        }, 300);
    };

    word?.addEventListener('input', () => {
        setAiError('');
        setDuplicateWarning(false);
        scheduleDuplicateCheck();
    });

    if (aiSuggestBtn && aiSuggestUrl) {
        aiSuggestBtn.addEventListener('click', async () => {
            setAiError('');
            const w = (word?.value || '').trim();
            if (!w) {
                setAiError('أدخل كلمة في الوجه الأمامي أولًا.');
                return;
            }
            const tokenInput = document.querySelector('input[name="_token"]');
            const token = tokenInput?.value;
            if (!token) {
                setAiError('تعذر التحقق من الجلسة. حدّث الصفحة.');
                return;
            }
            const prevText = aiSuggestBtn.textContent;
            aiSuggestBtn.disabled = true;
            aiSuggestBtn.textContent = '…';
            try {
                const res = await fetch(aiSuggestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ word: w }),
                });
                let data = {};
                try {
                    data = await res.json();
                } catch {
                    data = {};
                }
                if (!res.ok) {
                    setAiError(data.message || 'حدث خطأ.');
                    return;
                }
                if (enMeaning && data.en_meaning != null) enMeaning.value = data.en_meaning;
                if (arMeaning && data.ar_meaning != null) arMeaning.value = data.ar_meaning;
                if (explanation && data.explanation != null) explanation.value = data.explanation;
                if (icon && data.icon != null) icon.value = data.icon;
                const enTrim = String(data.en_meaning ?? '').trim();
                const arTrim = String(data.ar_meaning ?? '').trim();
                const expTrim = String(data.explanation ?? '').trim();
                const iconTrim = String(data.icon ?? '').trim();
                if (showEn && enTrim) showEn.checked = true;
                if (showAr && arTrim) showAr.checked = true;
                if (showExplanation && expTrim) showExplanation.checked = true;
                if (showIcon && iconTrim) showIcon.checked = true;
                render();
            } catch {
                setAiError('تعذر الاتصال بالخادم.');
            } finally {
                aiSuggestBtn.disabled = false;
                aiSuggestBtn.textContent = prevText;
            }
        });
    }

    const aiIconBtn = byId('ai-icon-btn');
    const aiIconError = byId('ai-icon-error');
    const aiIconUrl = qs('[data-ai-icon-url]')?.getAttribute('data-ai-icon-url');

    const setAiIconError = (msg) => {
        if (!aiIconError) return;
        if (msg) {
            aiIconError.textContent = msg;
            aiIconError.classList.remove('hidden');
        } else {
            aiIconError.textContent = '';
            aiIconError.classList.add('hidden');
        }
    };

    const base64ToFile = (base64, mime, filename) => {
        const bin = atob(base64);
        const len = bin.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i += 1) bytes[i] = bin.charCodeAt(i);
        return new File([bytes], filename, { type: mime || 'image/png' });
    };

    word?.addEventListener('input', () => setAiIconError(''));

    if (aiIconBtn && aiIconUrl && iconImage) {
        aiIconBtn.addEventListener('click', async () => {
            setAiIconError('');
            const w = (word?.value || '').trim();
            if (!w) {
                setAiIconError('أدخل كلمة في الوجه الأمامي أولًا.');
                return;
            }
            const tokenInput = document.querySelector('input[name="_token"]');
            const token = tokenInput?.value;
            if (!token) {
                setAiIconError('تعذر التحقق من الجلسة. حدّث الصفحة.');
                return;
            }
            const prevLabel = aiIconBtn.textContent;
            aiIconBtn.disabled = true;
            aiIconBtn.textContent = '…';
            try {
                const res = await fetch(aiIconUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ word: w }),
                });
                let data = {};
                try {
                    data = await res.json();
                } catch {
                    data = {};
                }
                if (!res.ok) {
                    setAiIconError(data.message || 'تعذر توليد الصورة.');
                    return;
                }
                const b64 = data.image_base64;
                const mime = data.mime || 'image/png';
                if (typeof b64 !== 'string' || !b64) {
                    setAiIconError('استجابة غير صالحة من الخادم.');
                    return;
                }
                const file = base64ToFile(b64, mime, `ai-icon-${Date.now()}.png`);
                const dt = new DataTransfer();
                dt.items.add(file);
                iconImage.files = dt.files;
                if (removeIconImage) removeIconImage.checked = false;
                if (showIcon) showIcon.checked = true;
                iconImage.dispatchEvent(new Event('change', { bubbles: true }));
            } catch {
                setAiIconError('تعذر الاتصال بالخادم.');
            } finally {
                aiIconBtn.disabled = false;
                aiIconBtn.textContent = prevLabel;
            }
        });
    }

    scheduleDuplicateCheck();
})();
</script>
