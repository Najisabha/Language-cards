@props(['language' => null])

@php
    $language = $language ?? new \App\Models\Language(['color' => '#6366f1']);
@endphp

<div class="grid gap-5">
    <div>
        <label for="name" class="block text-sm font-medium mb-1">اسم اللغة <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required maxlength="80" value="{{ old('name', $language->name) }}" placeholder="English"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="code" class="block text-sm font-medium mb-1">رمز اللغة</label>
        <input type="text" id="code" name="code" maxlength="10" value="{{ old('code', $language->code) }}" placeholder="en"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="color" class="block text-sm font-medium mb-1">لون اللغة</label>
        <input type="color" id="color" name="color" value="{{ old('color', $language->color ?? '#6366f1') }}" class="h-10 w-20 rounded-md border border-slate-300 cursor-pointer">
        @error('color') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="position" class="block text-sm font-medium mb-1">الترتيب</label>
        <input type="number" id="position" name="position" min="0" max="9999" value="{{ old('position', $language->position ?? 0) }}"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('position') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
</div>
