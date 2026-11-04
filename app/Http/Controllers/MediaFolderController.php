<?php

namespace App\Http\Controllers;

use App\Models\MediaFolder;
use App\Models\MediaImage;
use App\Support\MediaLibrarySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MediaFolderController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $folders = MediaFolder::query()
            ->withCount('images')
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->map(fn (MediaFolder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'images_count' => $folder->images_count,
            ]);

        return response()->json(['data' => $folders]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $folder = MediaFolder::query()->create([
            'name' => trim($validated['name']),
        ]);

        Storage::disk('card_uploads')->makeDirectory($folder->storageDirectory());

        return redirect()
            ->route('media-folders.show', $folder)
            ->with('status', 'تم إنشاء المجلد.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_upload_mb' => ['required', 'integer', 'min:1', 'max:40'],
            'max_images_per_folder' => ['required', 'integer', 'min:1', 'max:2000'],
        ]);

        MediaLibrarySettings::update(
            (int) $validated['max_upload_mb'],
            (int) $validated['max_images_per_folder']
        );

        return redirect()
            ->route('media-images.index')
            ->with('status', 'تم حفظ إعدادات مكتبة الصور.');
    }

    public function import(Request $request): RedirectResponse
    {
        $hasArchives = $request->hasFile('archives');
        $hasImages = $request->hasFile('images');

        if (! $hasArchives && ! $hasImages) {
            return redirect()
                ->route('media-images.index')
                ->with('status', 'اختر مجلداً واحداً أو ملف ZIP واحداً للاستيراد.');
        }

        $foldersCreated = 0;
        $imagesImported = 0;
        $skipped = 0;
        $skippedNames = [];

        if ($hasArchives) {
            $request->validate([
                'archives' => ['required', 'array', 'min:1', 'max:1'],
                'archives.*' => ['required', 'file', 'mimes:zip', 'max:51200'],
            ]);

            $archive = $request->file('archives.0');
            if ($archive instanceof UploadedFile) {
                $result = $this->importFromZip($archive);
                $foldersCreated += $result['foldersCreated'];
                $imagesImported += $result['imagesImported'];
                $skipped += $result['skipped'];
                $skippedNames = array_merge($skippedNames, $result['skippedNames'] ?? []);
            }
        }

        if ($hasImages) {
            $maxKb = MediaLibrarySettings::maxUploadKb();
            $maxPerFolder = MediaLibrarySettings::maxImagesPerFolder();

            $request->validate([
                'images' => ['required', 'array', 'min:1', 'max:'.$maxPerFolder],
                'images.*' => ['nullable', 'file', 'max:'.$maxKb],
                'client_skipped_count' => ['nullable', 'integer', 'min:0'],
                'client_skipped_names' => ['nullable', 'string', 'max:2000'],
            ]);

            $result = $this->importFromDirectoryFiles($request);
            $foldersCreated += $result['foldersCreated'];
            $imagesImported += $result['imagesImported'];
            $skipped += $result['skipped'];
            $skippedNames = array_merge($skippedNames, $result['skippedNames'] ?? []);

            $clientSkipped = (int) $request->input('client_skipped_count', 0);
            if ($clientSkipped > 0) {
                $skipped += $clientSkipped;
                $clientNames = trim((string) $request->input('client_skipped_names', ''));
                if ($clientNames !== '') {
                    $skippedNames = array_merge($skippedNames, array_filter(array_map('trim', explode('|', $clientNames))));
                }
            }
        }

        if ($imagesImported === 0 && $foldersCreated === 0) {
            $note = $skipped > 0
                ? " ملاحظة: لم يُستورد أي شيء، وتم تخطي {$skipped} ملفاً."
                : '';

            return redirect()
                ->route('media-images.index')
                ->with('status', 'لم يتم العثور على صور صالحة للاستيراد.'.$note);
        }

        $message = "تم استيراد {$imagesImported} صورة";
        if ($foldersCreated > 0) {
            $message .= " ضمن {$foldersCreated} مجلد جديد";
        }
        $message .= '.';

        if ($skipped > 0) {
            $preview = array_slice(array_values(array_unique($skippedNames)), 0, 8);
            $message .= " ملاحظة: لم يتم استيراد {$skipped} ملفاً.";
            if ($preview !== []) {
                $message .= ' أمثلة: '.implode('، ', $preview);
                if (count($skippedNames) > count($preview)) {
                    $message .= '…';
                }
            }
        }

        return redirect()
            ->route('media-images.index')
            ->with('status', $message);
    }

    public function export(): StreamedResponse|RedirectResponse
    {
        $folders = MediaFolder::query()
            ->with(['images' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        if ($folders->isEmpty()) {
            return redirect()
                ->route('media-images.index')
                ->with('status', 'لا توجد مجلدات لتصديرها.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'media-folders-export-');
        if ($tmpPath === false) {
            return redirect()
                ->route('media-images.index')
                ->with('status', 'تعذر إنشاء ملف التصدير.');
        }

        $zipPath = $tmpPath.'.zip';
        @unlink($tmpPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()
                ->route('media-images.index')
                ->with('status', 'تعذر إنشاء أرشيف التصدير.');
        }

        $added = 0;
        $usedNames = [];

        foreach ($folders as $folder) {
            $safeFolder = preg_replace('/[\\\\\/:*?"<>|]+/', '_', $folder->name) ?: 'folder';
            $folderUsed = [];

            foreach ($folder->images as $image) {
                $contents = $this->readStoredContents($image);
                if ($contents === null) {
                    continue;
                }

                $entryFile = $this->uniqueZipEntryName($image->displayName(), $folderUsed);
                $entryName = $safeFolder.'/'.$entryFile;
                $zip->addFromString($entryName, $contents);
                $added++;
            }

            // Keep empty folders represented in the zip.
            if ($folder->images->isEmpty()) {
                $zip->addEmptyDir($safeFolder);
            }

            $usedNames[$safeFolder] = true;
        }

        $zip->close();

        if ($added === 0 && $folders->every(fn (MediaFolder $folder) => $folder->images->isEmpty())) {
            // Still allow exporting empty folder structure.
        } elseif ($added === 0) {
            @unlink($zipPath);

            return redirect()
                ->route('media-images.index')
                ->with('status', 'تعذر العثور على ملفات الصور للتصدير.');
        }

        $downloadName = 'media-folders-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(function () use ($zipPath): void {
            $stream = fopen($zipPath, 'rb');
            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
            @unlink($zipPath);
        }, $downloadName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function show(MediaFolder $mediaFolder): View
    {
        $mediaFolder->load([
            'images' => fn ($q) => $q->withCount('cards')->orderByDesc('id'),
        ]);

        return view('media-images.show', [
            'folder' => $mediaFolder,
            'images' => $mediaFolder->images,
            'mediaSettings' => MediaLibrarySettings::all(),
        ]);
    }

    public function update(Request $request, MediaFolder $mediaFolder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $mediaFolder->renameTo($validated['name']);

        return redirect()
            ->back()
            ->with('status', 'تم تحديث اسم المجلد.');
    }

    public function destroy(MediaFolder $mediaFolder): RedirectResponse
    {
        $mediaFolder->purge();

        return redirect()
            ->route('media-images.index')
            ->with('status', 'تم حذف المجلد وجميع صوره من التخزين نهائياً.');
    }

    /**
     * @return array{foldersCreated: int, imagesImported: int, skipped: int, skippedNames: array<int, string>}
     */
    private function importFromZip(UploadedFile $archive): array
    {
        $foldersCreated = 0;
        $imagesImported = 0;
        $skipped = 0;
        $skippedNames = [];
        $maxBytes = MediaLibrarySettings::maxUploadBytes();
        $maxMb = MediaLibrarySettings::maxUploadMb();
        $maxPerFolder = MediaLibrarySettings::maxImagesPerFolder();
        $folderCounts = [];

        $zip = new ZipArchive;
        if ($zip->open($archive->getRealPath()) !== true) {
            return compact('foldersCreated', 'imagesImported', 'skipped', 'skippedNames');
        }

        $zipBase = pathinfo($archive->getClientOriginalName(), PATHINFO_FILENAME) ?: 'مستورد';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $folderCache = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if ($entryName === '' || str_ends_with($entryName, '/')) {
                continue;
            }

            $baseName = basename($entryName);

            // Skip macOS metadata and hidden files.
            if (str_contains($entryName, '__MACOSX/') || str_contains('/'.$entryName, '/.')) {
                $skipped++;
                $skippedNames[] = $baseName.' (ملف نظام)';
                continue;
            }

            $extension = strtolower((string) pathinfo($entryName, PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions, true)) {
                $skipped++;
                $skippedNames[] = $baseName.' (ليس صورة)';
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false || $contents === '') {
                $skipped++;
                $skippedNames[] = $baseName.' (فارغ/تالف)';
                continue;
            }

            if (strlen($contents) > $maxBytes) {
                $skipped++;
                $skippedNames[] = $baseName.' (أكبر من '.$maxMb.'MB)';
                continue;
            }

            $parts = array_values(array_filter(explode('/', trim($entryName, '/')), fn ($part) => $part !== ''));
            if ($parts === []) {
                $skipped++;
                $skippedNames[] = $baseName;
                continue;
            }

            $folderName = count($parts) >= 2
                ? mb_substr($parts[0], 0, 120)
                : mb_substr($zipBase, 0, 120);

            if (! isset($folderCache[$folderName])) {
                $folder = MediaFolder::query()->firstOrCreate(['name' => $folderName]);
                if ($folder->wasRecentlyCreated) {
                    $foldersCreated++;
                }
                $folderCache[$folderName] = $folder->id;
                $folderCounts[$folderName] = $folder->images()->count();
            }

            if (($folderCounts[$folderName] ?? 0) >= $maxPerFolder) {
                $skipped++;
                $skippedNames[] = $baseName.' (تجاوز حد المجلد)';
                continue;
            }

            MediaImage::createFromContents($contents, $baseName, $folderCache[$folderName]);
            $folderCounts[$folderName] = ($folderCounts[$folderName] ?? 0) + 1;
            $imagesImported++;
        }

        $zip->close();

        return compact('foldersCreated', 'imagesImported', 'skipped', 'skippedNames');
    }

    /**
     * @return array{foldersCreated: int, imagesImported: int, skipped: int, skippedNames: array<int, string>}
     */
    private function importFromDirectoryFiles(Request $request): array
    {
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $maxBytes = MediaLibrarySettings::maxUploadBytes();
        $maxMb = MediaLibrarySettings::maxUploadMb();
        $maxPerFolder = MediaLibrarySettings::maxImagesPerFolder();

        $grouped = [];
        $skipped = 0;
        $skippedNames = [];
        $files = array_values(array_filter($request->file('images', [])));
        $explicitFolderNames = $request->input('folder_names', []);

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            if (! in_array($extension, $allowedExtensions, true) && ! in_array($mime, $allowedMimes, true)) {
                $skipped++;
                $skippedNames[] = $originalName.' (ليس صورة)';
                continue;
            }

            if ($file->getSize() > $maxBytes) {
                $skipped++;
                $skippedNames[] = $originalName.' (أكبر من '.$maxMb.'MB)';
                continue;
            }

            $explicitName = is_array($explicitFolderNames)
                ? trim((string) ($explicitFolderNames[$index] ?? ''))
                : '';

            $folderName = $explicitName !== ''
                ? mb_substr($explicitName, 0, 120)
                : $this->folderNameFromRelativePath($this->clientRelativePath($request, $index, $file));

            $grouped[$folderName][] = $file;
        }

        $foldersCreated = 0;
        $imagesImported = 0;

        foreach ($grouped as $folderName => $folderFiles) {
            $folder = MediaFolder::query()->firstOrCreate([
                'name' => mb_substr($folderName, 0, 120),
            ]);
            if ($folder->wasRecentlyCreated) {
                $foldersCreated++;
            }

            $currentCount = $folder->images()->count();

            foreach ($folderFiles as $file) {
                if ($currentCount >= $maxPerFolder) {
                    $skipped++;
                    $skippedNames[] = $file->getClientOriginalName().' (تجاوز حد المجلد)';
                    continue;
                }

                try {
                    MediaImage::createFromUpload($file, $folder->id);
                    $imagesImported++;
                    $currentCount++;
                } catch (\Throwable) {
                    $skipped++;
                    $skippedNames[] = $file->getClientOriginalName().' (فشل الرفع)';
                }
            }
        }

        return compact('foldersCreated', 'imagesImported', 'skipped', 'skippedNames');
    }

    private function clientRelativePath(Request $request, int $index, UploadedFile $file): string
    {
        $fullPath = $_FILES['images']['full_path'][$index] ?? null;
        if (is_string($fullPath) && $fullPath !== '') {
            return str_replace('\\', '/', $fullPath);
        }

        return str_replace('\\', '/', $file->getClientOriginalName());
    }

    private function folderNameFromRelativePath(string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        $parts = array_values(array_filter(explode('/', $relative), fn ($part) => $part !== ''));

        if (count($parts) >= 2) {
            return mb_substr($parts[0], 0, 120);
        }

        return 'مستورد-'.now()->format('Ymd-His');
    }

    private function readStoredContents(MediaImage $image): ?string
    {
        if (! $image->path) {
            return null;
        }

        if (Storage::disk('card_uploads')->exists($image->path)) {
            return Storage::disk('card_uploads')->get($image->path);
        }

        if (Storage::disk('public')->exists($image->path)) {
            return Storage::disk('public')->get($image->path);
        }

        return null;
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private function uniqueZipEntryName(string $displayName, array &$usedNames): string
    {
        $safe = preg_replace('/[\\\\\/:*?"<>|]+/', '_', $displayName) ?: 'image';
        $base = pathinfo($safe, PATHINFO_FILENAME) ?: 'image';
        $ext = pathinfo($safe, PATHINFO_EXTENSION);
        $candidate = $safe;
        $i = 2;

        while (isset($usedNames[mb_strtolower($candidate)])) {
            $candidate = $ext !== '' ? "{$base}-{$i}.{$ext}" : "{$base}-{$i}";
            $i++;
        }

        $usedNames[mb_strtolower($candidate)] = true;

        return $candidate;
    }
}
