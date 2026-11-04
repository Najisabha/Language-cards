@extends('layouts.app')

@section('title', 'مكتبة الصور')

@section('content')
    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">Media</p>
            <h1 class="page-title">مكتبة الصور</h1>
            <p class="page-subtitle">عرض الصور المشتركة وتعديل أسمائها أو حذفها. يمكن إعادة استخدامها في بطاقات بأي لغة.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form id="media-store-form" method="POST" action="{{ route('media-images.store') }}" enctype="multipart/form-data" class="inline">
                @csrf
                <input type="file" id="media-store-input" name="image" accept="image/*" class="hidden">
                <button type="button" id="media-store-btn" class="btn btn-primary">إدراج صورة</button>
            </form>

            <form id="media-import-form" method="POST" action="{{ route('media-images.import') }}" enctype="multipart/form-data" class="inline">
                @csrf
                <input type="file" id="media-import-input" name="images[]" accept="image/*" multiple class="hidden">
                <button type="button" id="media-import-btn" class="btn btn-secondary">استيراد الصور</button>
            </form>

            <a href="{{ route('media-images.export') }}" class="btn btn-secondary {{ $images->isEmpty() ? 'pointer-events-none opacity-50' : '' }}">تصدير الصور</a>
        </div>
    </section>

    @error('image')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('images')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('images.*')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if ($images->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-4">لا توجد صور في المكتبة بعد.</p>
            <p class="text-sm text-slate-400">استخدم «إدراج صورة» أو «استيراد الصور» لإضافة صور مباشرة إلى المكتبة.</p>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($images as $image)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 overflow-hidden rounded-xl border border-slate-100 bg-slate-50 aspect-square flex items-center justify-center">
                        @if ($image->url())
                            <img src="{{ $image->url() }}" alt="{{ $image->displayName() }}" class="h-full w-full object-contain">
                        @else
                            <span class="text-xs text-slate-400">الملف غير موجود</span>
                        @endif
                    </div>

                    <p class="mb-1 text-sm font-semibold text-slate-800 break-all" title="{{ $image->displayName() }}">
                        {{ $image->displayName() }}
                    </p>
                    <p class="mb-3 text-xs text-slate-500">مستخدمة في {{ $image->cards_count }} بطاقة</p>

                    <form method="POST" action="{{ route('media-images.update', $image) }}" class="mb-2 space-y-2">
                        @csrf
                        @method('PATCH')
                        <label class="sr-only" for="name-{{ $image->id }}">اسم الصورة</label>
                        <input type="text" id="name-{{ $image->id }}" name="name" required maxlength="255"
                               value="{{ old('name', $image->name) }}"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" class="w-full rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                            حفظ الاسم
                        </button>
                    </form>

                    <form method="POST" action="{{ route('media-images.destroy', $image) }}"
                          onsubmit="return confirm('حذف هذه الصورة من المكتبة؟ ستُزال من البطاقات التي تستخدمها.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                            حذف الصورة
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
@endsection

<script>
(() => {
    const bindPicker = (btnId, inputId, formId) => {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        const form = document.getElementById(formId);
        if (!btn || !input || !form) return;

        btn.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            if (input.files && input.files.length > 0) {
                form.submit();
            }
        });
    };

    bindPicker('media-store-btn', 'media-store-input', 'media-store-form');
    bindPicker('media-import-btn', 'media-import-input', 'media-import-form');
})();
</script>
