@extends('layouts.app')

@section('title', 'مكتبة الصور')

@section('content')
    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">Media</p>
            <h1 class="page-title">مكتبة الصور</h1>
            <p class="page-subtitle">أنشئ مجلدات وضع الصور داخلها، ثم أعد استخدامها في بطاقات بأي لغة.</p>
        </div>
        <div class="flex flex-col items-stretch gap-2 sm:items-end">
            <div class="flex flex-wrap items-end gap-2">
                <form method="POST" action="{{ route('media-folders.store') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label for="folder-name" class="sr-only">اسم المجلد</label>
                        <input type="text" id="folder-name" name="name" required maxlength="120"
                               value="{{ old('name') }}"
                               placeholder="اسم المجلد الجديد"
                               class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 min-w-[12rem]">
                    </div>
                    <button type="submit" class="btn btn-primary">إنشاء المجلد</button>
                </form>

                <button type="button"
                        id="media-settings-open"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90"
                        style="background-color:#64748b;"
                        title="إعدادات المكتبة"
                        aria-label="إعدادات المكتبة">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('media-folders.import') }}" enctype="multipart/form-data" class="inline">
                    @csrf
                    <input type="file" id="media-folders-import-zips" name="archives[]" accept=".zip,application/zip"
                           class="sr-only" onchange="if (this.files.length) this.form.submit()">
                    <label for="media-folders-import-zips" class="btn btn-secondary cursor-pointer"
                           title="اختر ملف ZIP واحد يحتوي مجلدات/صور">استيراد ZIP</label>
                </form>

                <button type="button"
                        id="media-folders-multi-pick-btn"
                        class="btn btn-secondary"
                        data-import-url="{{ route('media-folders.import') }}"
                        data-index-url="{{ route('media-images.index') }}"
                        data-csrf="{{ csrf_token() }}"
                        title="اختر مجلداً واحداً لاستيراد كل صوره">
                    استيراد مجلد
                </button>

                <form method="POST" action="{{ route('media-folders.import') }}" enctype="multipart/form-data" class="inline" id="media-folders-parent-form">
                    @csrf
                    <input type="file" id="media-folders-import-parent" name="images[]" accept="image/*" multiple
                           webkitdirectory directory
                           class="sr-only">
                </form>

                @if ($folders->isEmpty())
                    <span class="btn btn-secondary opacity-50 cursor-not-allowed" title="لا توجد مجلدات للتصدير">تصدير المجلدات</span>
                @else
                    <a href="{{ route('media-folders.export') }}" class="btn btn-secondary">تصدير المجلدات</a>
                @endif
            </div>
        </div>
    </section>

    <div id="media-settings-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="media-settings-title">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="media-settings-title" class="text-lg font-bold text-slate-900">إعدادات المكتبة</h2>
                    <p class="mt-1 text-sm text-slate-500">حدّد أقصى حجم للصورة وعدد الصور المسموح في المجلد الواحد.</p>
                </div>
                <button type="button" id="media-settings-close" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="إغلاق">×</button>
            </div>

            <form method="POST" action="{{ route('media-library.settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="max_upload_mb" class="mb-1 block text-sm font-medium text-slate-700">أقصى حجم للصورة (ميجابايت)</label>
                    <input type="number" id="max_upload_mb" name="max_upload_mb" required min="1" max="40"
                           value="{{ old('max_upload_mb', $mediaSettings['max_upload_mb']) }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-slate-500">من 1 إلى 40 ميجابايت.</p>
                </div>
                <div>
                    <label for="max_images_per_folder" class="mb-1 block text-sm font-medium text-slate-700">أقصى عدد صور في المجلد</label>
                    <input type="number" id="max_images_per_folder" name="max_images_per_folder" required min="1" max="2000"
                           value="{{ old('max_images_per_folder', $mediaSettings['max_images_per_folder']) }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-slate-500">من 1 إلى 2000 صورة.</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                    <button type="button" id="media-settings-cancel" class="btn btn-secondary">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                </div>
            </form>
        </div>
    </div>

    @error('name')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('max_upload_mb')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('max_images_per_folder')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('images')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('images.*')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('archives')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
    @error('archives.*')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if ($folders->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
            <p class="text-slate-500 mb-2">لا توجد مجلدات بعد.</p>
            <p class="text-sm text-slate-400">أنشئ مجلداً، أو استورد عدة مجلدات دفعة واحدة من جهازك.</p>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($folders as $folder)
                <article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                    <a href="{{ route('media-folders.show', $folder) }}" class="group block flex-1 p-5 pb-4">
                        <div class="mb-4 flex h-20 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-9 w-9" aria-hidden="true">
                                <path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 break-all">{{ $folder->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $folder->images_count }} صورة</p>
                    </a>

                    <div class="mt-auto flex items-center justify-center gap-2 px-5 pb-4">
                        <form method="POST" action="{{ route('media-folders.update', $folder) }}"
                              class="inline"
                              onsubmit="return renameMediaFolder(event, this);">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="name" value="{{ $folder->name }}">
                            <button type="submit"
                                    title="تعديل اسم المجلد"
                                    aria-label="تعديل اسم المجلد"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full shadow-sm hover:opacity-90"
                                    style="background-color:#facc15;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2.2" class="h-4 w-4" aria-hidden="true">
                                    <path d="M12 20h9" stroke-linecap="round"></path>
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke-linejoin="round"></path>
                                </svg>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('media-folders.destroy', $folder) }}"
                              class="inline"
                              onsubmit="return confirm('حذف المجلد «{{ $folder->name }}» مع كل صوره؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    title="حذف المجلد"
                                    aria-label="حذف المجلد"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full shadow-sm hover:opacity-90"
                                    style="background-color:#ef4444;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" class="h-4 w-4" aria-hidden="true">
                                    <path d="M3 6h18" stroke-linecap="round"></path>
                                    <path d="M8 6V4h8v2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M19 6l-1 14H6L5 6" stroke-linejoin="round"></path>
                                    <path d="M10 11v6M14 11v6" stroke-linecap="round"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <script>
    function renameMediaFolder(event, form) {
        event.preventDefault();
        event.stopPropagation();

        const input = form.querySelector('input[name="name"]');
        const current = input ? input.value : '';
        const next = window.prompt('اسم المجلد الجديد', current);

        if (next === null) {
            return false;
        }

        const trimmed = next.trim();
        if (!trimmed || trimmed === current) {
            return false;
        }

        if (input) {
            input.value = trimmed;
        }
        form.submit();
        return false;
    }

    (() => {
        const settingsModal = document.getElementById('media-settings-modal');
        const openSettings = () => {
            if (!settingsModal) return;
            settingsModal.classList.remove('hidden');
            settingsModal.classList.add('flex');
        };
        const closeSettings = () => {
            if (!settingsModal) return;
            settingsModal.classList.add('hidden');
            settingsModal.classList.remove('flex');
        };
        document.getElementById('media-settings-open')?.addEventListener('click', openSettings);
        document.getElementById('media-settings-close')?.addEventListener('click', closeSettings);
        document.getElementById('media-settings-cancel')?.addEventListener('click', closeSettings);
        settingsModal?.addEventListener('click', (event) => {
            if (event.target === settingsModal) closeSettings();
        });
        @if ($errors->has('max_upload_mb') || $errors->has('max_images_per_folder'))
            openSettings();
        @endif
    })();

    (() => {
        const btn = document.getElementById('media-folders-multi-pick-btn');
        const fallbackInput = document.getElementById('media-folders-import-parent');
        if (!btn) return;

        const importUrl = btn.getAttribute('data-import-url');
        const indexUrl = btn.getAttribute('data-index-url');
        const csrf = btn.getAttribute('data-csrf');
        const imageExt = /\.(jpe?g|png|webp|gif|svg)$/i;
        const maxMb = {{ (int) $mediaSettings['max_upload_mb'] }};
        const maxBytes = maxMb * 1024 * 1024;
        const maxImages = {{ (int) $mediaSettings['max_images_per_folder'] }};
        // PHP max_file_uploads is often 20 — stay safely under it.
        const batchSize = 15;

        const collectFromDirectory = async (dirHandle, folderName, items, report) => {
            for await (const entry of dirHandle.values()) {
                if (entry.kind === 'file') {
                    const file = await entry.getFile();
                    const isImage = file.type.startsWith('image/') || imageExt.test(file.name);
                    if (!isImage) {
                        report.skipped.push(file.name + ' (ليس صورة)');
                        continue;
                    }
                    if (file.size > maxBytes) {
                        report.skipped.push(file.name + ' (أكبر من ' + maxMb + 'MB)');
                        continue;
                    }
                    if (items.length >= maxImages) {
                        report.skipped.push(file.name + ' (تجاوز حد المجلد)');
                        continue;
                    }
                    items.push({ file, folderName });
                } else if (entry.kind === 'directory') {
                    await collectFromDirectory(entry, folderName, items, report);
                }
            }
        };

        const collectFromFileList = (fileList) => {
            const items = [];
            const report = { skipped: [] };

            Array.from(fileList || []).forEach((file) => {
                const isImage = (file.type && file.type.startsWith('image/')) || imageExt.test(file.name);
                if (!isImage) {
                    report.skipped.push(file.name + ' (ليس صورة)');
                    return;
                }
                if (file.size > maxBytes) {
                    report.skipped.push(file.name + ' (أكبر من ' + maxMb + 'MB)');
                    return;
                }
                if (items.length >= maxImages) {
                    report.skipped.push(file.name + ' (تجاوز حد المجلد)');
                    return;
                }

                const relative = String(file.webkitRelativePath || file.name).replace(/\\/g, '/');
                const parts = relative.split('/').filter(Boolean);
                const folderName = parts.length >= 2 ? parts[0] : 'مستورد';
                items.push({ file, folderName });
            });

            return { items, report };
        };

        const uploadBatches = async (items, report) => {
            for (let offset = 0; offset < items.length; offset += batchSize) {
                const chunk = items.slice(offset, offset + batchSize);
                const formData = new FormData();
                formData.append('_token', csrf || '');

                chunk.forEach(({ file, folderName }) => {
                    formData.append('images[]', file, file.name);
                    formData.append('folder_names[]', folderName);
                });

                const isLast = offset + batchSize >= items.length;
                if (isLast && report.skipped.length) {
                    formData.append('client_skipped_count', String(report.skipped.length));
                    formData.append('client_skipped_names', report.skipped.slice(0, 20).join(' | '));
                }

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
        };

        const runImport = async (items, report, folderLabel) => {
            if (!items.length) {
                const skippedNote = report.skipped.length
                    ? `\nملفات لم تُستورد: ${report.skipped.slice(0, 8).join('، ')}`
                    : '';
                alert(`لم يتم العثور على صور صالحة داخل المجلد «${folderLabel}».${skippedNote}`);
                return;
            }

            btn.disabled = true;
            const oldLabel = btn.textContent;
            btn.textContent = 'جاري الاستيراد...';

            try {
                await uploadBatches(items, report);
                window.location.href = indexUrl || '/media-images';
            } catch (error) {
                console.error(error);
                alert('تعذر رفع المجلد. حاول مرة أخرى.');
                btn.disabled = false;
                btn.textContent = oldLabel || 'استيراد مجلد';
            }
        };

        btn.addEventListener('click', async () => {
            if (!window.showDirectoryPicker) {
                alert('متصفحك لا يدعم هذا الاختيار. سيتم فتح نافذة المجلد البديلة.');
                fallbackInput?.click();
                return;
            }

            let dirHandle;
            try {
                dirHandle = await window.showDirectoryPicker({ mode: 'read' });
            } catch (error) {
                if (!error || error.name !== 'AbortError') {
                    console.error(error);
                    alert('تعذر فتح نافذة اختيار المجلد.');
                }
                return;
            }

            const items = [];
            const report = { skipped: [] };

            try {
                await collectFromDirectory(dirHandle, dirHandle.name, items, report);
            } catch (error) {
                console.error(error);
                alert('تعذر قراءة محتويات المجلد.');
                return;
            }

            await runImport(items, report, dirHandle.name);
        });

        fallbackInput?.addEventListener('change', async () => {
            if (!fallbackInput.files || !fallbackInput.files.length) {
                return;
            }

            const { items, report } = collectFromFileList(fallbackInput.files);
            const label = items[0]?.folderName || 'المجلد';
            fallbackInput.value = '';
            await runImport(items, report, label);
        });
    })();
    </script>
@endsection
