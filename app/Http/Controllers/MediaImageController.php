<?php

namespace App\Http\Controllers;

use App\Models\MediaImage;
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
        $images = MediaImage::query()
            ->withCount('cards')
            ->orderByDesc('id')
            ->get();

        return view('media-images.index', compact('images'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'max:2048',
                'mimes:jpg,jpeg,png,webp,gif,svg',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/svg+xml',
            ],
        ]);

        MediaImage::createFromUpload($request->file('image'));

        return redirect()
            ->route('media-images.index')
            ->with('status', 'تم إدراج الصورة في المكتبة.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:100'],
            'images.*' => [
                'required',
                'file',
                'max:2048',
                'mimes:jpg,jpeg,png,webp,gif,svg',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/svg+xml',
            ],
        ]);

        $count = 0;
        foreach ($request->file('images', []) as $file) {
            if ($file) {
                MediaImage::createFromUpload($file);
                $count++;
            }
        }

        return redirect()
            ->route('media-images.index')
            ->with('status', "تم استيراد {$count} صورة إلى المكتبة.");
    }

    public function export(): StreamedResponse|RedirectResponse
    {
        $images = MediaImage::query()->orderBy('name')->get();

        if ($images->isEmpty()) {
            return redirect()
                ->route('media-images.index')
                ->with('status', 'لا توجد صور لتصديرها.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'media-export-');
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
                ->route('media-images.index')
                ->with('status', 'تعذر العثور على ملفات الصور للتصدير.');
        }

        $downloadName = 'media-images-'.now()->format('Y-m-d-His').'.zip';

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
        $q = trim((string) $request->query('q', ''));

        $query = MediaImage::query()->orderBy('name')->limit(24);

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
            'url' => $image->url(),
        ]);

        return response()->json(['data' => $results]);
    }

    public function update(Request $request, MediaImage $mediaImage): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $mediaImage->update([
            'name' => trim($validated['name']),
        ]);

        return redirect()
            ->route('media-images.index')
            ->with('status', 'تم تحديث اسم الصورة.');
    }

    public function destroy(MediaImage $mediaImage): RedirectResponse
    {
        $mediaImage->cards()->update(['media_image_id' => null, 'icon_image_path' => null]);
        $mediaImage->deleteStoredFile();
        $mediaImage->delete();

        return redirect()
            ->route('media-images.index')
            ->with('status', 'تم حذف الصورة.');
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
