@props(['deck' => null, 'selectedLevel'])

@php
    $deck = $deck ?? new \App\Models\Deck(['color' => '#6366f1']);
    $currentLevelId = old('level_id', $deck->level_id ?? $selectedLevel->id);
    $hasOldInput = session()->hasOldInput();

    if ($hasOldInput) {
        $includeWords = old('include_words') !== null;
        $includeSentences = old('include_sentences') !== null;
    } elseif ($deck->exists) {
        $deck->loadMissing('categories');
        $includeWords = $deck->categories->contains('name', \App\Models\Deck::CATEGORY_WORDS);
        $includeSentences = $deck->categories->contains('name', \App\Models\Deck::CATEGORY_SENTENCES);
    } else {
        $includeWords = true;
        $includeSentences = true;
    }
@endphp

<div class="grid gap-5">
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <span class="block text-sm font-medium text-slate-500">المستوى</span>
        <p class="mt-1 font-semibold text-slate-900">{{ $selectedLevel->name }}{{ $selectedLevel->title ? ' - '.$selectedLevel->title : '' }}</p>
        @if ($selectedLevel->language)
            <p class="mt-1 text-xs text-slate-500">ضمن لغة {{ $selectedLevel->language->name }}</p>
        @endif
        <input type="hidden" name="level_id" value="{{ $currentLevelId }}">
        @error('level_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-medium mb-1">اسم النوع <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required maxlength="120" value="{{ old('name', $deck->name) }}" placeholder="مثال: تحيات، أرقام، ألوان" class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium mb-1">الوصف</label>
        <textarea id="description" name="description" rows="3" maxlength="500" class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $deck->description) }}</textarea>
        @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="color" class="block text-sm font-medium mb-1">لون النوع</label>
        <input type="color" id="color" name="color" value="{{ old('color', $deck->color ?? '#6366f1') }}" class="h-10 w-20 rounded-md border border-slate-300 cursor-pointer">
        @error('color') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <fieldset class="rounded-lg border border-slate-200 p-4">
        <legend class="px-2 text-sm font-semibold text-slate-800">التصنيفات داخل هذا النوع</legend>
        <p class="mb-3 text-xs text-slate-500">اختر ما تريد إضافته لهذا النوع. يمكن اختيار واحد أو كليهما.</p>
        <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                <input type="checkbox" name="include_words" value="1"
                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                       {{ $includeWords ? 'checked' : '' }}>
                كلمات
            </label>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                <input type="checkbox" name="include_sentences" value="1"
                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                       {{ $includeSentences ? 'checked' : '' }}>
                جمل
            </label>
        </div>
        @error('include_words') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
        @error('include_sentences') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
    </fieldset>
</div>
