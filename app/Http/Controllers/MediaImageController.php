<?php

namespace App\Http\Controllers;

use App\Models\MediaFolder;
use App\Models\MediaImage;
use App\Support\MediaLibrarySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MediaImageController extends Controller
{
    public function index(): View
    {
        $folders = MediaFolder::query()
            ->withCount('images')
            ->orderBy('name')
            ->get();

        $mediaSettings = MediaLibrarySettings::all();

        return view('media-images.index', compact('folders', 'mediaSettings'));
    }

    public function store(Request $request, MediaFolder $mediaFolder): RedirectResponse
    {
        $maxKb = MediaLibrarySettings::maxUploadKb();
        $maxPerFolder = MediaLibrarySettings::maxImagesPerFolder();

        if ($mediaFolder->images()->count() >= $maxPerFolder) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', "وصل المجلد إلى الحد الأقصى ({$maxPerFolder} صورة).");
        }

        $request->validate([
            'image' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:jpg,jpeg,png,webp,gif,svg',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/svg+xml',
            ],
        ]);

        MediaImage::createFromUpload($request->file('image'), $mediaFolder->id);

        return redirect()
            ->route('media-folders.show', $mediaFolder)
            ->with('status', 'تم إدراج الصورة في المجلد.');
    }

    public function import(Request $request, MediaFolder $mediaFolder): RedirectResponse
    {
        $maxKb = MediaLibrarySettings::maxUploadKb();
        $maxPerFolder = MediaLibrarySettings::maxImagesPerFolder();
        $remaining = max(0, $maxPerFolder - $mediaFolder->images()->count());

        if ($remaining === 0) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', "وصل المجلد إلى الحد الأقصى ({$maxPerFolder} صورة).");
        }

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:'.$maxPerFolder],
            'images.*' => ['nullable', 'file', 'max:'.$maxKb],
        ]);

        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

        $count = 0;
        $skipped = 0;

        foreach ($request->file('images', []) as $file) {
            if (! $file) {
                continue;
            }

            if ($count >= $remaining) {
                $skipped++;
                continue;
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            if (! in_array($extension, $allowedExtensions, true) && ! in_array($mime, $allowedMimes, true)) {
                $skipped++;
                continue;
            }

            MediaImage::createFromUpload($file, $mediaFolder->id);
            $count++;
        }

        if ($count === 0) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', 'لم يتم العثور على صور داخل المجلد المحدد.');
        }

        $message = "تم استيراد {$count} صورة إلى المجلد.";
        if ($skipped > 0) {
            $message .= " تم تخطي {$skipped} ملفاً غير صوري.";
        }

        return redirect()
            ->route('media-folders.show', $mediaFolder)
            ->with('status', $message);
    }

    public function export(MediaFolder $mediaFolder): StreamedResponse|RedirectResponse
    {
        $images = $mediaFolder->images()->orderBy('name')->get();

        if ($images->isEmpty()) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', 'لا توجد صور لتصديرها في هذا المجلد.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'media-export-');
        if ($tmpPath === false) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', 'تعذر إنشاء ملف التصدير.');
        }

        $zipPath = $tmpPath.'.zip';
        @unlink($tmpPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', 'تعذر إنشاء أرشيف التصدير.');
        }

        $usedNames = [];
        $added = 0;

        foreach ($images as $image) {
            $contents = $this->readStoredContents($image);
            if ($contents === null) {
                continue;
            }

            $entryName = $this->uniqueZipEntryName($image->displayName(), $usedNames);
            $zip->addFromString($entryName, $contents);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);

            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', 'تعذر العثور على ملفات الصور للتصدير.');
        }

        $safeFolder = preg_replace('/[\\\\\/:*?"<>|]+/', '_', $mediaFolder->name) ?: 'folder';
        $downloadName = $safeFolder.'-'.now()->format('Y-m-d-His').'.zip';

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

    public function search(Request $request): JsonResponse
    {
        $folderId = $request->integer('folder_id') ?: null;
        $q = trim((string) $request->query('q', ''));

        // Images can only be searched inside a selected folder.
        if (! $folderId) {
            return response()->json(['data' => [], 'message' => 'اختر مجلداً أولاً.']);
        }

        $query = MediaImage::query()
            ->with('folder:id,name')
            ->withCount('cards')
            ->where('folder_id', $folderId)
            ->orderBy('name')
            ->limit(24);

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($builder) use ($like, $q) {
                $builder->where('name', 'like', $like)
                    ->orWhere('extension', 'like', $like)
                    ->orWhereRaw("CONCAT(name, '.', COALESCE(extension, '')) like ?", [$like]);

                if (str_contains($q, '.')) {
                    $builder->orWhereRaw("CONCAT(name, '.', COALESCE(extension, '')) = ?", [mb_strtolower($q)]);
                }
            });
        }

        $results = $query->get()->map(fn (MediaImage $image) => [
            'id' => $image->id,
            'name' => $image->name,
            'extension' => $image->extension,
            'display_name' => $image->displayName(),
            'folder_name' => $image->folder?->name,
            'url' => $image->url(),
            'cards_count' => (int) $image->cards_count,
            'is_used' => ((int) $image->cards_count) > 0,
        ]);

        return response()->json(['data' => $results]);
    }

    public function update(Request $request, MediaImage $mediaImage): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $mediaImage->renameTo($validated['name']);

        return redirect()
            ->route('media-folders.show', $mediaImage->folder_id)
            ->with('status', 'تم تحديث اسم الصورة والملف.');
    }

    public function destroy(MediaImage $mediaImage): RedirectResponse
    {
        $folderId = $mediaImage->folder_id;

        $mediaImage->purge();
        MediaImage::pruneOrphanStoredFiles();

        return redirect()
            ->route('media-folders.show', $folderId)
            ->with('status', 'تم حذف الصورة من التخزين نهائياً.');
    }

    public function destroyAll(MediaFolder $mediaFolder): RedirectResponse
    {
        $images = $mediaFolder->images()->get();
        $count = $images->count();

        if ($count === 0) {
            return redirect()
                ->route('media-folders.show', $mediaFolder)
                ->with('status', 'لا توجد صور لحذفها في هذا المجلد.');
        }

        foreach ($images as $image) {
            $image->purge();
        }

        MediaImage::pruneOrphanStoredFiles();

        return redirect()
            ->route('media-folders.show', $mediaFolder)
            ->with('status', "تم حذف جميع صور المجلد ({$count}) من التخزين نهائياً.");
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
