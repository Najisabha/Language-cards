@props(['deck', 'category' => null])

@php
    $category = $category ?? new \App\Models\Category();
@endphp

<div class="grid gap-5">
    <div>
        <label for="name" class="block text-sm font-medium mb-1">اسم التصنيف <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required maxlength="120" value="{{ old('name', $category->name) }}"
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium mb-1">الوصف</label>
        <textarea id="description" name="description" rows="3" maxlength="500" class="w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $category->description) }}</textarea>
        @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
</div>
