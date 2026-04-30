@props(['deck' => null, 'selectedLevel'])

@php
    $deck = $deck ?? new \App\Models\Deck(['color' => '#6366f1']);
    $currentLevelId = old('level_id', $deck->level_id ?? $selectedLevel->id);
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
        <label for="name" class="block text-sm font-medium mb-1">اسم المجموعة <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required maxlength="120" value="{{ old('name', $deck->name) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium mb-1">الوصف</label>
        <textarea id="description" name="description" rows="3" maxlength="500" class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $deck->description) }}</textarea>
        @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="color" class="block text-sm font-medium mb-1">لون المجموعة</label>
        <input type="color" id="color" name="color" value="{{ old('color', $deck->color ?? '#6366f1') }}" class="h-10 w-20 rounded-md border border-slate-300 cursor-pointer">
        @error('color') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
</div>
