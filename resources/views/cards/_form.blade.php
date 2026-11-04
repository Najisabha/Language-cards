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
    $iconImageUrl = $card->iconImageUrl();
    $selectedMediaImageId = old('media_image_id', $card->media_image_id);
    $deck->loadMissing('level.language');
    $studyLanguageName = $deck->level?->language?->name ?? 'اللغة المختارة';
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
            @error('front_bg_type') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            @error('front_bg_value') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <input type="hidden" name="front_bg_type" value="{{ $initialFrontType }}">
        <input type="hidden" name="front_bg_value" id="front_bg_value" value="{{ $initialFrontValue }}">

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-2 text-sm font-semibold">الوجه الخلفي</legend>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="en_meaning" class="block text-sm font-medium mb-1">نطق الكلمة بالعربية ({{ $studyLanguageName }})</label>
                    <input type="text" id="en_meaning" name="en_meaning" maxlength="255" dir="rtl"
                           value="{{ old('en_meaning', $card->en_meaning) }}"
                           placeholder="مثال: ويلكم"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-slate-500 mt-1">اكتب نطق الكلمة في الوجه الأمامي بحروف عربية لتسهيل القراءة.</p>
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
                        <div data-ai-icon-url="{{ route('cards.ai-icon-image') }}"
                             data-media-search-url="{{ route('media-images.search') }}"
                             data-media-folder-search-url="{{ route('media-folders.search') }}"
                             data-media-upload-base="{{ url('/media-folders') }}">
                            <p class="block text-xs font-medium text-slate-600 mb-2">بحث في مكتبة الصور</p>

                            <div class="space-y-2">
                                <div>
                                    <label for="media_folder_search" class="mb-1 block text-xs text-slate-500">اسم المجلد</label>
                                    <input type="search" id="media_folder_search" autocomplete="off"
                                           placeholder="ابحث عن اسم المجلد"
                                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div id="media-folder-results" class="mt-2 hidden max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-white shadow-sm"></div>
                                    <input type="hidden" id="media_folder_id" name="media_folder_id" value="">
                                </div>

                                <div>
                                    <label for="media_image_search" class="mb-1 block text-xs text-slate-500">اسم الصورة</label>
                                    <div class="flex items-center gap-2">
                                        <input type="search" id="media_image_search" autocomplete="off" disabled
                                               placeholder="اختر مجلداً أولاً ثم ابحث عن الصورة"
                                               class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                                        <div id="icon-upload-actions" class="hidden shrink-0">
                                            <input type="file" id="icon_image" name="icon_image" accept="image/*" class="sr-only">
                                            <button type="button" id="icon-upload-btn"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-1"
                                                    style="background-color:#22c55e; --tw-ring-color:#4ade80;"
                                                    aria-label="إدراج صورة"
                                                    title="إدراج صورة">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                    <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p id="media-image-search-hint" class="mt-1 text-[11px] text-amber-600">اختر مجلداً من نتائج البحث أعلاه أولاً.</p>
                                    <div id="media-image-results" class="mt-2 hidden max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-white shadow-sm"></div>
                                    <p id="ai-icon-error" class="text-xs text-red-600 mt-1 hidden" role="alert"></p>
                                    @error('icon_image') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    @error('media_image_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <input type="hidden" id="media_image_id" name="media_image_id" value="{{ $selectedMediaImageId }}">

                            <div id="selected-media-preview" class="mt-2 {{ $iconImageUrl ? 'flex' : 'hidden' }} items-center gap-2">
                                <img id="selected-media-thumb" src="{{ $iconImageUrl }}" alt="" class="h-10 w-10 rounded object-cover border border-slate-200">
                                <span id="selected-media-label" class="text-xs text-slate-600">{{ $card->mediaImage?->displayName() }}</span>
                                <button type="button" id="clear-selected-media" class="text-xs text-red-600 hover:underline">إلغاء الاختيار</button>
                            </div>

                            @if ($iconImageUrl)
                                <div class="mt-2 flex items-center gap-2">
                                    <label class="inline-flex items-center gap-1 text-xs text-red-600 cursor-pointer">
                                        <input type="checkbox" name="remove_icon_image" value="1"> إزالة الصورة من هذه البطاقة
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid sm:grid-cols-2 gap-2 text-sm">
                <label class="inline-flex items-center gap-2"><input type="checkbox" id="show_en" name="show_en" value="1" {{ old('show_en', $card->show_en) ? 'checked' : '' }}> إظهار النطق بالعربية</label>
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
                        <p id="preview-en" class="flashcard-back-en {{ old('show_en', $card->show_en) && old('en_meaning', $card->en_meaning) ? '' : 'hidden' }}" dir="rtl">{{ old('en_meaning', $card->en_meaning) }}</p>
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
    const frontBgTypeInput = qs('input[name="front_bg_type"]');

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
    let selectedLibraryImageUrl = initialIconImageUrl;
    const mediaImageIdInput = byId('media_image_id');
    const mediaFolderIdInput = byId('media_folder_id');
    const mediaFolderSearchInput = byId('media_folder_search');
    const mediaSearchInput = byId('media_image_search');
    const mediaFolderResults = byId('media-folder-results');
    const mediaResults = byId('media-image-results');
    const selectedMediaPreview = byId('selected-media-preview');
    const selectedMediaThumb = byId('selected-media-thumb');
    const selectedMediaLabel = byId('selected-media-label');
    const clearSelectedMediaBtn = byId('clear-selected-media');
    const mediaSearchRoot = qs('[data-media-search-url]');
    const mediaSearchUrl = mediaSearchRoot?.getAttribute('data-media-search-url');
    const mediaFolderSearchUrl = mediaSearchRoot?.getAttribute('data-media-folder-search-url');
    const mediaUploadBase = (mediaSearchRoot?.getAttribute('data-media-upload-base') || '').replace(/\/$/, '');
    const iconUploadError = byId('ai-icon-error');
    let mediaSearchTimer = null;
    let mediaFolderSearchTimer = null;
    let mediaSearchSeq = 0;
    let mediaFolderSearchSeq = 0;
    let iconUploadInFlight = false;

    const setIconUploadError = (msg) => {
        if (!iconUploadError) return;
        if (msg) {
            iconUploadError.textContent = msg;
            iconUploadError.classList.remove('hidden');
        } else {
            iconUploadError.textContent = '';
            iconUploadError.classList.add('hidden');
        }
    };

    const isHex = (value) => /^#[0-9A-Fa-f]{6}$/.test(value);
    const isGradient = (value) => /^(linear|radial|conic)-gradient\s*\(.+\)$/i.test(value);
    const isColorKeyword = (value) => /^[a-zA-Z]{3,25}$/.test((value || '').trim());
    const setVisible = (el, visible) => el && el.classList.toggle('hidden', !visible);
    const currentFrontType = () => frontBgTypeInput?.value || 'color';

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
        } else if (isColorKeyword(value)) {
            previewFront.style.backgroundColor = value.trim();
        } else {
            previewFront.style.backgroundColor = '#ffffff';
        }
        setVisible(previewFrontOverlay, false);
    };

    const computeIconImageUrl = () => {
        if (removeIconImage?.checked) return '';
        if (uploadedIconImageUrl) return uploadedIconImageUrl;
        if (selectedLibraryImageUrl) return selectedLibraryImageUrl;
        return '';
    };

    const updateSelectedMediaPreview = (url, label) => {
        if (selectedMediaThumb) {
            if (url) selectedMediaThumb.setAttribute('src', url);
            else selectedMediaThumb.removeAttribute('src');
        }
        if (selectedMediaLabel) selectedMediaLabel.textContent = label || '';
        if (selectedMediaPreview) {
            selectedMediaPreview.classList.toggle('hidden', !url);
            selectedMediaPreview.classList.toggle('flex', !!url);
        }
    };

    const clearLibrarySelection = () => {
        if (mediaImageIdInput) mediaImageIdInput.value = '';
        selectedLibraryImageUrl = '';
        updateSelectedMediaPreview('', '');
    };

    const selectLibraryImage = (item) => {
        if (mediaImageIdInput) mediaImageIdInput.value = String(item.id);
        selectedLibraryImageUrl = item.url || '';
        updateSelectedMediaPreview(item.url || '', item.display_name || '');
        if (uploadedIconImageUrl) {
            URL.revokeObjectURL(uploadedIconImageUrl);
            uploadedIconImageUrl = '';
        }
        if (iconImage) iconImage.value = '';
        if (removeIconImage) removeIconImage.checked = false;
        if (showIcon) showIcon.checked = true;
        if (mediaFolderResults) {
            mediaFolderResults.innerHTML = '';
            mediaFolderResults.classList.add('hidden');
        }
        if (mediaResults) {
            mediaResults.innerHTML = '';
            mediaResults.classList.add('hidden');
        }
        if (mediaSearchInput) mediaSearchInput.value = item.display_name || '';
        render();
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

    [word, enMeaning, arMeaning, explanation, icon, showEn, showAr, showExplanation, showIcon]
        .filter(Boolean)
        .forEach((el) => el.addEventListener('input', render));

    if (iconImage) {
        iconImage.addEventListener('change', async () => {
            const file = iconImage.files && iconImage.files[0];
            setIconUploadError('');

            if (uploadedIconImageUrl) {
                URL.revokeObjectURL(uploadedIconImageUrl);
                uploadedIconImageUrl = '';
            }

            if (!file) {
                render();
                return;
            }

            const folderId = (mediaFolderIdInput?.value || '').trim();
            if (!folderId || !mediaUploadBase) {
                setIconUploadError('اختر مجلداً أولاً قبل رفع الصورة.');
                iconImage.value = '';
                render();
                return;
            }

            if (iconUploadInFlight) return;
            iconUploadInFlight = true;
            if (iconUploadBtn) iconUploadBtn.disabled = true;

            const localPreviewUrl = URL.createObjectURL(file);
            uploadedIconImageUrl = localPreviewUrl;
            if (mediaImageIdInput) mediaImageIdInput.value = '';
            selectedLibraryImageUrl = '';
            updateSelectedMediaPreview(localPreviewUrl, file.name);
            if (removeIconImage) removeIconImage.checked = false;
            if (showIcon) showIcon.checked = true;
            render();

            try {
                const token = document.querySelector('input[name="_token"]')?.value;
                if (!token) {
                    throw new Error('تعذر التحقق من الجلسة. حدّث الصفحة.');
                }

                const formData = new FormData();
                formData.append('image', file);
                formData.append('_token', token);

                const res = await fetch(`${mediaUploadBase}/${encodeURIComponent(folderId)}/images`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: formData,
                });

                let data = {};
                try {
                    data = await res.json();
                } catch {
                    data = {};
                }

                if (!res.ok) {
                    const msg = data.message
                        || (data.errors && data.errors.image && data.errors.image[0])
                        || 'تعذر رفع الصورة.';
                    throw new Error(msg);
                }

                const item = data.data || {};
                if (!item.id) {
                    throw new Error('استجابة غير صالحة من الخادم.');
                }

                selectLibraryImage({
                    id: item.id,
                    url: item.url || localPreviewUrl,
                    display_name: item.display_name || file.name,
                });
            } catch (err) {
                setIconUploadError(err?.message || 'تعذر رفع الصورة.');
                if (uploadedIconImageUrl) {
                    URL.revokeObjectURL(uploadedIconImageUrl);
                    uploadedIconImageUrl = '';
                }
                iconImage.value = '';
                clearLibrarySelection();
                render();
            } finally {
                iconUploadInFlight = false;
                if (iconUploadBtn) iconUploadBtn.disabled = false;
            }
        });
    }

    if (removeIconImage) {
        removeIconImage.addEventListener('change', () => {
            if (removeIconImage.checked) {
                clearLibrarySelection();
                if (iconImage) iconImage.value = '';
                if (uploadedIconImageUrl) {
                    URL.revokeObjectURL(uploadedIconImageUrl);
                    uploadedIconImageUrl = '';
                }
            }
            render();
        });
    }

    const hideResults = (el) => {
        if (!el) return;
        el.innerHTML = '';
        el.classList.add('hidden');
    };

    clearSelectedMediaBtn?.addEventListener('click', () => {
        clearLibrarySelection();
        if (mediaFolderSearchInput) mediaFolderSearchInput.value = '';
        if (mediaFolderIdInput) mediaFolderIdInput.value = '';
        if (mediaSearchInput) mediaSearchInput.value = '';
        hideResults(mediaFolderResults);
        hideResults(mediaResults);
        setImageSearchEnabled(false);
        render();
    });

    const mediaImageSearchHint = byId('media-image-search-hint');
    const iconUploadActions = byId('icon-upload-actions');
    const iconUploadBtn = byId('icon-upload-btn');

    const setImageSearchEnabled = (enabled) => {
        if (mediaSearchInput) {
            mediaSearchInput.disabled = !enabled;
            mediaSearchInput.placeholder = enabled
                ? 'ابحث عن اسم الصورة أو الامتداد داخل المجلد المختار'
                : 'اختر مجلداً أولاً ثم ابحث عن الصورة';
        }
        if (mediaImageSearchHint) {
            mediaImageSearchHint.classList.toggle('hidden', enabled);
        }
        if (iconUploadActions) {
            iconUploadActions.classList.toggle('hidden', !enabled);
        }
        if (!enabled) {
            hideResults(mediaResults);
        }
    };

    iconUploadBtn?.addEventListener('click', () => {
        iconImage?.click();
    });

    const selectFolder = (folder) => {
        if (mediaFolderIdInput) mediaFolderIdInput.value = String(folder.id || '');
        if (mediaFolderSearchInput) mediaFolderSearchInput.value = folder.name || '';
        hideResults(mediaFolderResults);
        hideResults(mediaResults);
        setImageSearchEnabled(true);
        if (mediaSearchInput) {
            mediaSearchInput.value = '';
            mediaSearchInput.focus();
        }
    };

    const renderFolderResults = (items) => {
        if (!mediaFolderResults) return;
        if (!items.length) {
            mediaFolderResults.innerHTML = '<p class="px-3 py-2 text-xs text-slate-500">لا توجد مجلدات مطابقة.</p>';
            mediaFolderResults.classList.remove('hidden');
            return;
        }

        mediaFolderResults.innerHTML = items.map((item) => `
            <button type="button"
                    class="media-folder-result-item flex w-full items-center justify-between gap-2 px-3 py-2 text-right text-sm hover:bg-indigo-50 border-b border-slate-100 last:border-0"
                    data-id="${item.id}"
                    data-name="${(item.name || '').replace(/"/g, '&quot;')}">
                <span class="font-medium truncate">${item.name || ''}</span>
                <span class="shrink-0 text-[11px] text-slate-400">${Number(item.images_count || 0)} صورة</span>
            </button>
        `).join('');
        mediaFolderResults.classList.remove('hidden');

        qsa('.media-folder-result-item', mediaFolderResults).forEach((btn) => {
            btn.addEventListener('click', () => {
                selectFolder({
                    id: Number(btn.getAttribute('data-id')),
                    name: btn.getAttribute('data-name') || '',
                });
            });
        });
    };

    const renderMediaResults = (items) => {
        if (!mediaResults) return;
        if (!items.length) {
            mediaResults.innerHTML = '<p class="px-3 py-2 text-xs text-slate-500">لا توجد صور مطابقة.</p>';
            mediaResults.classList.remove('hidden');
            return;
        }

        mediaResults.innerHTML = items.map((item) => {
            const isUsed = !!item.is_used || Number(item.cards_count || 0) > 0;
            const usedStyle = isUsed ? 'background-color:#86efac;' : '';
            const usedLabel = isUsed
                ? `<span class="mr-auto shrink-0 text-[11px] font-semibold" style="color:#000;">مستخدمة${Number(item.cards_count || 0) > 0 ? ` (${Number(item.cards_count)})` : ''}</span>`
                : '';
            return `
            <button type="button"
                    class="media-result-item flex w-full items-center gap-2 px-3 py-2 text-right text-sm border-b border-slate-100 last:border-0 ${isUsed ? '' : 'hover:bg-indigo-50'}"
                    style="${usedStyle}"
                    data-id="${item.id}"
                    data-url="${item.url || ''}"
                    data-name="${(item.display_name || '').replace(/"/g, '&quot;')}">
                <img src="${item.url || ''}" alt="" class="h-8 w-8 rounded object-cover border border-slate-200 bg-slate-50">
                <span class="truncate" style="${isUsed ? 'color:#000;' : ''}">${item.display_name || ''}</span>
                ${usedLabel}
            </button>`;
        }).join('');
        mediaResults.classList.remove('hidden');

        qsa('.media-result-item', mediaResults).forEach((btn) => {
            btn.addEventListener('click', () => {
                selectLibraryImage({
                    id: Number(btn.getAttribute('data-id')),
                    url: btn.getAttribute('data-url') || '',
                    display_name: btn.getAttribute('data-name') || '',
                });
            });
        });
    };

    const runFolderSearch = async () => {
        if (!mediaFolderSearchUrl || !mediaFolderSearchInput) return;
        const q = (mediaFolderSearchInput.value || '').trim();
        if (!q) {
            if (mediaFolderIdInput) mediaFolderIdInput.value = '';
            hideResults(mediaFolderResults);
            return;
        }

        const seq = ++mediaFolderSearchSeq;
        try {
            const res = await fetch(`${mediaFolderSearchUrl}?q=${encodeURIComponent(q)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok || seq !== mediaFolderSearchSeq) return;
            const data = await res.json();
            renderFolderResults(Array.isArray(data.data) ? data.data : []);
        } catch {
            // Keep form usable if search fails.
        }
    };

    const runImageSearch = async () => {
        if (!mediaSearchUrl) return;
        const folderId = (mediaFolderIdInput?.value || '').trim();
        if (!folderId) {
            setImageSearchEnabled(false);
            hideResults(mediaResults);
            return;
        }

        const q = (mediaSearchInput?.value || '').trim();
        if (!q) {
            hideResults(mediaResults);
            return;
        }

        const seq = ++mediaSearchSeq;
        try {
            const params = new URLSearchParams();
            params.set('folder_id', folderId);
            params.set('q', q);
            const res = await fetch(`${mediaSearchUrl}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok || seq !== mediaSearchSeq) return;
            const data = await res.json();
            renderMediaResults(Array.isArray(data.data) ? data.data : []);
        } catch {
            // Keep form usable if search fails.
        }
    };

    setImageSearchEnabled(false);

    mediaFolderSearchInput?.addEventListener('input', () => {
        if (mediaFolderIdInput) mediaFolderIdInput.value = '';
        setImageSearchEnabled(false);
        hideResults(mediaResults);
        if (mediaFolderSearchTimer) window.clearTimeout(mediaFolderSearchTimer);
        mediaFolderSearchTimer = window.setTimeout(runFolderSearch, 250);
    });

    mediaSearchInput?.addEventListener('input', () => {
        if (!(mediaFolderIdInput?.value || '').trim()) {
            setImageSearchEnabled(false);
            hideResults(mediaResults);
            return;
        }
        hideResults(mediaFolderResults);
        if (mediaSearchTimer) window.clearTimeout(mediaSearchTimer);
        mediaSearchTimer = window.setTimeout(runImageSearch, 250);
    });

    mediaFolderSearchInput?.addEventListener('focus', () => {
        if ((mediaFolderSearchInput.value || '').trim() && !mediaFolderIdInput?.value) runFolderSearch();
    });
    mediaSearchInput?.addEventListener('focus', () => {
        const q = (mediaSearchInput.value || '').trim();
        if (q && (mediaFolderIdInput?.value || '').trim()) runImageSearch();
    });

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
                    body: JSON.stringify({
                        word: w,
                        deck_id: duplicateDeckId ? Number(duplicateDeckId) : null,
                    }),
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
