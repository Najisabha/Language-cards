@extends('layouts.app')

@section('title', $folder->name.' | مكتبة الصور')

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('media-images.index') }}" class="text-indigo-600 hover:underline">مكتبة الصور</a>
        <span class="mx-1">/</span>
        <span>{{ $folder->name }}</span>
    </div>

    <section class="page-hero mb-8 items-center">
        <div class="min-w-0">
            <p class="page-eyebrow">Folder</p>
            <h1 class="page-title">{{ $folder->name }}</h1>
        </div>
        <div class="flex shrink-0 flex-nowrap items-center gap-2">
            <form method="POST" action="{{ route('media-folders.images.store', $folder) }}" enctype="multipart/form-data" class="inline">
                @csrf
                <input type="file" id="media-store-input" name="image" accept="image/*"
                       class="sr-only" onchange="if (this.files.length) this.form.submit()">
                <label for="media-store-input"
                       class="btn btn-primary !gap-0 !p-2.5 cursor-pointer"
                       title="إدراج صورة"
                       aria-label="إدراج صورة">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z" />
                    </svg>
                </label>
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
                <label for="media-import-input"
                       class="btn btn-secondary !gap-0 !p-2.5 cursor-pointer"
                       title="استيراد الصور — اختر مجلداً من جهازك"
                       aria-label="استيراد الصور">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M3.5 3A1.5 1.5 0 0 0 2 4.5v11A1.5 1.5 0 0 0 3.5 17h13a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-3.12-3.122A1.5 1.5 0 0 0 13.378 3H3.5Zm6.5 4a.75.75 0 0 1 .75.75v2.69l.72-.72a.75.75 0 1 1 1.06 1.06l-2 2a.75.75 0 0 1-1.06 0l-2-2a.75.75 0 1 1 1.06-1.06l.72.72V7.75A.75.75 0 0 1 10 7Z" />
                    </svg>
                </label>
            </form>

            @if ($images->isEmpty())
                <span class="btn btn-secondary !gap-0 !p-2.5 opacity-50 cursor-not-allowed"
                      title="لا توجد صور للتصدير بعد"
                      aria-label="تصدير الصور">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.3 8.49a.75.75 0 0 0-1.1 1.02l3.25 3.5a.75.75 0 0 0 1.1 0l3.25-3.5a.75.75 0 1 0-1.1-1.02l-2.95 3.15V2.75Z" />
                        <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                    </svg>
                </span>
                <span class="btn btn-secondary !gap-0 !p-2.5 opacity-50 cursor-not-allowed text-red-600"
                      title="لا توجد صور للحذف"
                      aria-label="حذف جميع الصور">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <a href="{{ route('media-folders.images.export', $folder) }}"
                   class="btn btn-secondary !gap-0 !p-2.5"
                   title="تصدير الصور"
                   aria-label="تصدير الصور">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.3 8.49a.75.75 0 0 0-1.1 1.02l3.25 3.5a.75.75 0 0 0 1.1 0l3.25-3.5a.75.75 0 1 0-1.1-1.02l-2.95 3.15V2.75Z" />
                        <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                    </svg>
                </a>
                <form method="POST" action="{{ route('media-folders.images.destroy-all', $folder) }}" class="inline"
                      onsubmit="return confirm('حذف جميع صور هذا المجلد؟ ستُزال من البطاقات التي تستخدمها.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="btn btn-secondary !gap-0 !p-2.5 text-red-600 hover:border-red-200 hover:bg-red-50"
                            title="حذف جميع الصور"
                            aria-label="حذف جميع الصور">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </form>
            @endif

            <a href="{{ route('media-images.index') }}"
               class="btn btn-secondary !gap-0 !p-2.5"
               title="رجوع"
               aria-label="رجوع">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                </svg>
            </a>
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
