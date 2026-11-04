<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaImage extends Model
{
    protected $fillable = [
        'folder_id',
        'name',
        'path',
        'extension',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public static function createFromUpload(UploadedFile $file, ?int $folderId = null): self
    {
        $original = $file->getClientOriginalName();
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION) ?: (string) $file->extension());
        $baseName = self::sanitizeBaseName((string) pathinfo($original, PATHINFO_FILENAME));

        if ($baseName === '') {
            $baseName = 'image';
        }

        $directory = self::directoryForFolderId($folderId);
        $uniqueName = self::uniqueBaseName($directory, $baseName, $extension);
        $filename = self::composeFilename($uniqueName, $extension);
        $path = $directory.'/'.$filename;

        Storage::disk('card_uploads')->makeDirectory($directory);
        Storage::disk('card_uploads')->putFileAs($directory, $file, $filename);

        return static::query()->create([
            'folder_id' => $folderId,
            'name' => $uniqueName,
            'path' => $path,
            'extension' => $extension !== '' ? $extension : null,
        ]);
    }

    public static function createFromContents(string $contents, string $originalFilename, ?int $folderId = null): self
    {
        $extension = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
        $baseName = self::sanitizeBaseName((string) pathinfo($originalFilename, PATHINFO_FILENAME));

        if ($baseName === '') {
            $baseName = 'image';
        }

        $directory = self::directoryForFolderId($folderId);
        $uniqueName = self::uniqueBaseName($directory, $baseName, $extension);
        $filename = self::composeFilename($uniqueName, $extension);
        $path = $directory.'/'.$filename;

        Storage::disk('card_uploads')->makeDirectory($directory);
        Storage::disk('card_uploads')->put($path, $contents);

        return static::query()->create([
            'folder_id' => $folderId,
            'name' => $uniqueName,
            'path' => $path,
            'extension' => $extension !== '' ? $extension : null,
        ]);
    }

    public function renameTo(string $newName): void
    {
        $baseName = self::sanitizeBaseName(trim($newName));
        if ($baseName === '') {
            $baseName = 'image';
        }

        $extension = strtolower(ltrim((string) $this->extension, '.'));
        $directory = $this->storageDirectory();
        $uniqueName = self::uniqueBaseName($directory, $baseName, $extension, $this->id);
        $filename = self::composeFilename($uniqueName, $extension);
        $newPath = $directory.'/'.$filename;
        $oldPath = (string) $this->path;

        if ($oldPath !== '' && $oldPath !== $newPath) {
            Storage::disk('card_uploads')->makeDirectory($directory);
            $this->moveStoredFile($oldPath, $newPath);
        }

        $this->update([
            'name' => $uniqueName,
            'path' => $newPath,
            'extension' => $extension !== '' ? $extension : null,
        ]);

        $this->cards()->update([
            'icon_image_path' => $newPath,
        ]);
    }

    public function relocateToDirectory(string $directory): void
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');
        $extension = strtolower(ltrim((string) $this->extension, '.'));
        $baseName = self::sanitizeBaseName((string) $this->name) ?: 'image';
        $uniqueName = self::uniqueBaseName($directory, $baseName, $extension, $this->id);
        $filename = self::composeFilename($uniqueName, $extension);
        $newPath = $directory.'/'.$filename;
        $oldPath = (string) $this->path;

        if ($oldPath !== '' && $oldPath !== $newPath) {
            Storage::disk('card_uploads')->makeDirectory($directory);
            $this->moveStoredFile($oldPath, $newPath);
        }

        $this->update([
            'name' => $uniqueName,
            'path' => $newPath,
        ]);

        $this->cards()->update([
            'icon_image_path' => $newPath,
        ]);
    }

    public function storageDirectory(): string
    {
        if ($this->folder_id) {
            return self::directoryForFolderId((int) $this->folder_id);
        }

        $dir = trim(str_replace('\\', '/', (string) dirname((string) $this->path)), '/');

        return $dir !== '' && $dir !== '.' ? $dir : 'card-icons/_unassigned';
    }

    public static function directoryForFolderId(?int $folderId): string
    {
        if (! $folderId) {
            return 'card-icons/_unassigned';
        }

        $folder = MediaFolder::query()->find($folderId);

        return $folder ? $folder->storageDirectory() : 'card-icons/_unassigned';
    }

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $path = $this->path;

        // Migrate legacy public-disk files when present; always return a stable /uploads URL for export/UI.
        if (! Storage::disk('card_uploads')->exists($path) && Storage::disk('public')->exists($path)) {
            try {
                Storage::disk('card_uploads')->put(
                    $path,
                    Storage::disk('public')->get($path)
                );
                Storage::disk('public')->delete($path);
            } catch (\Throwable) {
                return Storage::disk('public')->url($path);
            }
        }

        return asset('uploads/'.$path);
    }

    public function displayName(): string
    {
        $ext = ltrim((string) $this->extension, '.');

        return $ext !== '' ? $this->name.'.'.$ext : $this->name;
    }

    public function deleteStoredFile(): void
    {
        if (! $this->path) {
            return;
        }

        $path = $this->path;
        $uploads = Storage::disk('card_uploads');
        $public = Storage::disk('public');

        if ($uploads->exists($path)) {
            $uploads->delete($path);
        }

        if ($public->exists($path)) {
            $public->delete($path);
        }
    }

    /**
     * Permanently remove DB row, card links, and file(s) from disk.
     */
    public function purge(): void
    {
        $directory = $this->storageDirectory();

        $this->cards()->update([
            'media_image_id' => null,
            'icon_image_path' => null,
        ]);

        $this->deleteStoredFile();
        $this->delete();

        self::deleteDirectoryIfEmpty($directory);
    }

    public static function deleteDirectoryIfEmpty(string $directory): void
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');
        if ($directory === '' || $directory === 'card-icons') {
            return;
        }

        $disk = Storage::disk('card_uploads');
        if (! $disk->exists($directory)) {
            return;
        }

        $files = array_values(array_filter(
            $disk->files($directory),
            fn (string $file) => ! str_ends_with($file, '/.gitkeep') && ! str_ends_with($file, '.gitkeep')
        ));

        if ($files === [] && $disk->directories($directory) === []) {
            $disk->deleteDirectory($directory);
        }
    }

    /**
     * Delete files under card-icons that are not referenced by media_images or cards.
     *
     * @return int Number of deleted files
     */
    public static function pruneOrphanStoredFiles(): int
    {
        $disk = Storage::disk('card_uploads');
        if (! $disk->exists('card-icons')) {
            return 0;
        }

        $referenced = collect()
            ->merge(static::query()->whereNotNull('path')->pluck('path')->all())
            ->merge(
                Card::query()
                    ->whereNotNull('icon_image_path')
                    ->where('icon_image_path', '!=', '')
                    ->pluck('icon_image_path')
                    ->all()
            )
            ->map(fn ($path) => str_replace('\\', '/', (string) $path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $referencedLookup = array_fill_keys($referenced, true);
        $keptDirectories = collect(MediaFolder::query()->get())
            ->map(fn (MediaFolder $folder) => $folder->storageDirectory())
            ->push('card-icons/_unassigned')
            ->unique()
            ->values()
            ->all();
        $keptDirectoryLookup = array_fill_keys($keptDirectories, true);

        $deleted = 0;

        foreach ($disk->allFiles('card-icons') as $file) {
            $normalized = str_replace('\\', '/', $file);
            $basename = basename($normalized);

            if ($basename === '.gitkeep') {
                continue;
            }

            if (isset($referencedLookup[$normalized])) {
                continue;
            }

            $disk->delete($normalized);
            $deleted++;
        }

        foreach (array_reverse($disk->allDirectories('card-icons')) as $directory) {
            $normalized = str_replace('\\', '/', $directory);
            if (isset($keptDirectoryLookup[$normalized])) {
                continue;
            }

            self::deleteDirectoryIfEmpty($normalized);
        }

        return $deleted;
    }

    public static function sanitizeBaseName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $name) ?? '';
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?? '';
        $name = trim($name, " .");

        return mb_substr($name, 0, 180);
    }

    private static function composeFilename(string $baseName, string $extension): string
    {
        return $extension !== '' ? $baseName.'.'.$extension : $baseName;
    }

    private static function uniqueBaseName(string $directory, string $baseName, string $extension, ?int $ignoreId = null): string
    {
        $candidate = $baseName;
        $i = 2;

        while (self::pathIsTaken($directory.'/'.self::composeFilename($candidate, $extension), $ignoreId)) {
            $candidate = $baseName.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private static function pathIsTaken(string $path, ?int $ignoreId = null): bool
    {
        $query = static::query()->where('path', $path);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            return true;
        }

        if (! Storage::disk('card_uploads')->exists($path)) {
            return false;
        }

        if ($ignoreId === null) {
            return true;
        }

        $owner = static::query()->where('path', $path)->first();

        return ! ($owner && (int) $owner->id === $ignoreId);
    }

    private function moveStoredFile(string $from, string $to): void
    {
        $uploads = Storage::disk('card_uploads');

        if ($uploads->exists($from)) {
            if ($uploads->exists($to)) {
                $uploads->delete($to);
            }
            $uploads->move($from, $to);

            return;
        }

        $public = Storage::disk('public');
        if ($public->exists($from)) {
            $uploads->put($to, $public->get($from));
            $public->delete($from);
        }
    }
}
