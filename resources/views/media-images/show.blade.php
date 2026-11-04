@extends('layouts.app')

@section('title', $folder->name.' | مكتبة الصور')

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('media-images.index') }}" class="text-indigo-600 hover:underline">مكتبة الصور</a>
        <span class="mx-1">/</span>
        <span>{{ $folder->name }}</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">Folder</p>
            <h1 class="page-title">{{ $folder->name }}</h1>
            <p class="page-subtitle">أدرج أو استورد صوراً داخل هذا المجلد، أو صدّرها/احذفها دفعة واحدة. الحد: {{ $mediaSettings['max_upload_mb'] }}MB للصورة، و{{ $mediaSettings['max_images_per_folder'] }} صورة للمجلد.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('media-folders.images.store', $folder) }}" enctype="multipart/form-data" class="inline">
                @csrf
                <input type="file" id="media-store-input" name="image" accept="image/*"
                       class="sr-only" onchange="if (this.files.length) this.form.submit()">
                <label for="media-store-input" class="btn btn-primary cursor-pointer">إدراج صورة</label>
            </form>

            <form method="POST" action="{{ route('media-folders.images.import', $folder) }}" enctype="multipart/form-data" class="inline" id="media-folder-import-form">
                @csrf
                <input type="file" id="media-import-input" name="images[]" accept="image/*" multiple
                       webkitdirectory directory
                       class="sr-only"
                       data-import-url="{{ route('media-folders.images.import', $folder) }}"
                       data-redirect-url="{{ route('media-folders.show', $folder) }}"
                       data-csrf="{{ csrf_token() }}"
                       data-max-mb="{{ (int) $mediaSettings['max_upload_mb'] }}"
                       data-max-images="{{ (int) $mediaSettings['max_images_per_folder'] }}"
                       data-current-count="{{ $images->count() }}">
                <label for="media-import-input" class="btn btn-secondary cursor-pointer" title="اختر مجلداً من جهازك لاستيراد كل الصور داخله">استيراد الصور</label>
            </form>

            @if ($images->isEmpty())
                <span class="btn btn-secondary opacity-50 cursor-not-allowed" title="لا توجد صور للتصدير بعد">تصدير الصور</span>
                <span class="btn btn-secondary opacity-50 cursor-not-allowed text-red-600" title="لا توجد صور للحذف">حذف جميع الصور</span>
            @else
                <a href="{{ route('media-folders.images.export', $folder) }}" class="btn btn-secondary">تصدير الصور</a>
                <form method="POST" action="{{ route('media-folders.images.destroy-all', $folder) }}" class="inline"
                      onsubmit="return confirm('حذف جميع صور هذا المجلد؟ ستُزال من البطاقات التي تستخدمها.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary text-red-600 hover:border-red-200 hover:bg-red-50">حذف جميع الصور</button>
                </form>
            @endif

            <a href="{{ route('media-images.index') }}" class="btn btn-secondary">رجوع</a>
        </div>
    </section>

    <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="POST" action="{{ route('media-folders.update', $folder) }}" class="flex flex-wrap items-end gap-2">
            @csrf
            @method('PATCH')
            <div class="min-w-[14rem] flex-1">
                <label for="folder-rename" class="mb-1 block text-xs font-medium text-slate-600">اسم المجلد</label>
                <input type="text" id="folder-rename" name="name" required maxlength="120"
                       value="{{ old('name', $folder->name) }}"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="btn btn-secondary">حفظ الاسم</button>
        </form>
        <form method="POST" action="{{ route('media-folders.destroy', $folder) }}" class="mt-3"
              onsubmit="return confirm('حذف المجلد بالكامل مع كل صوره؟');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">حذف المجلد</button>
        </form>
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
    @error('name')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if ($images->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-2">المجلد فارغ.</p>
            <p class="text-sm text-slate-400">استخدم «إدراج صورة» أو «استيراد الصور» لإضافة صور هنا.</p>
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
                          onsubmit="return confirm('حذف هذه الصورة من المجلد؟ ستُزال من البطاقات التي تستخدمها.');">
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

    <script>
    (() => {
        const input = document.getElementById('media-import-input');
        if (!input) return;

        const importUrl = input.getAttribute('data-import-url');
        const redirectUrl = input.getAttribute('data-redirect-url');
        const csrf = input.getAttribute('data-csrf');
        const maxMb = Number(input.getAttribute('data-max-mb') || 2);
        const maxBytes = maxMb * 1024 * 1024;
        const maxImages = Number(input.getAttribute('data-max-images') || 100);
        const currentCount = Number(input.getAttribute('data-current-count') || 0);
        const remaining = Math.max(0, maxImages - currentCount);
        const batchSize = 15;
        const imageExt = /\.(jpe?g|png|webp|gif|svg)$/i;

        input.addEventListener('change', async () => {
            const files = Array.from(input.files || []);
            input.value = '';
            if (!files.length) return;

            const accepted = [];
            let skipped = 0;

            for (const file of files) {
                const isImage = (file.type && file.type.startsWith('image/')) || imageExt.test(file.name);
                if (!isImage || file.size > maxBytes) {
                    skipped++;
                    continue;
                }
                if (accepted.length >= remaining) {
                    skipped++;
                    continue;
                }
                accepted.push(file);
            }

            if (!accepted.length) {
                alert('لم يتم العثور على صور صالحة للاستيراد ضمن الحد المسموح.');
                return;
            }

            try {
                for (let offset = 0; offset < accepted.length; offset += batchSize) {
                    const chunk = accepted.slice(offset, offset + batchSize);
                    const formData = new FormData();
                    formData.append('_token', csrf || '');
                    chunk.forEach((file) => formData.append('images[]', file, file.name));

                    const response = await fetch(importUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrf || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        credentials: 'same-origin',
                        redirect: 'follow',
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                }

                window.location.href = redirectUrl || window.location.href;
            } catch (error) {
                console.error(error);
                alert('تعذر استيراد الصور. حاول مرة أخرى.');
            }
        });
    })();
    </script>
@endsection
