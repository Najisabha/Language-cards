@props(['level' => null, 'selectedLanguage'])

@php
    $level = $level ?? new \App\Models\Level();
    $currentLanguageId = old('language_id', $level->language_id ?? $selectedLanguage->id);
@endphp

<div class="grid gap-5">
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <span class="block text-sm font-medium text-slate-500">اللغة</span>
        <p class="mt-1 font-semibold text-slate-900">{{ $selectedLanguage->name }}{{ $selectedLanguage->code ? ' ('.$selectedLanguage->code.')' : '' }}</p>
        <input type="hidden" name="language_id" value="{{ $currentLanguageId }}">
        @error('language_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-medium mb-1">رمز المستوى <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required maxlength="50" value="{{ old('name', $level->name) }}" placeholder="A1.1"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="title" class="block text-sm font-medium mb-1">عنوان اختياري</label>
        <input type="text" id="title" name="title" maxlength="120" value="{{ old('title', $level->title) }}" placeholder="المستوى التأسيسي"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="position" class="block text-sm font-medium mb-1">الترتيب</label>
        <input type="number" id="position" name="position" min="0" max="9999" value="{{ old('position', $level->position ?? 0) }}"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('position') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
</div>
