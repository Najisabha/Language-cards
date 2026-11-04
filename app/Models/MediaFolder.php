<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaFolder extends Model
{
    protected $fillable = [
        'name',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(MediaImage::class, 'folder_id');
    }

    public function storageSegment(): string
    {
        $safe = MediaImage::sanitizeBaseName((string) $this->name);
        if ($safe === '') {
            $safe = 'folder';
        }

        $conflict = static::query()
            ->where('id', '<', (int) $this->id)
            ->get()
            ->contains(function (self $folder) use ($safe) {
                $other = MediaImage::sanitizeBaseName((string) $folder->name);

                return ($other !== '' ? $other : 'folder') === $safe;
            });

        return $conflict ? $safe.'-'.$this->id : $safe;
    }

    public function storageDirectory(): string
    {
        return 'card-icons/'.$this->storageSegment();
    }

    public function renameTo(string $newName): void
    {
        $oldDirectory = $this->storageDirectory();
        $this->name = trim($newName);
        $newDirectory = $this->storageDirectory();

        if ($oldDirectory !== $newDirectory) {
            $this->images()->orderBy('id')->each(function (MediaImage $image) use ($newDirectory) {
                $image->relocateToDirectory($newDirectory);
            });

            $disk = Storage::disk('card_uploads');
            if ($disk->exists($oldDirectory) && count($disk->files($oldDirectory)) === 0 && count($disk->directories($oldDirectory)) === 0) {
                $disk->deleteDirectory($oldDirectory);
            }
        }

        $this->save();
    }

    public function deleteStorageDirectory(): void
    {
        $directory = $this->storageDirectory();
        $disk = Storage::disk('card_uploads');

        if ($disk->exists($directory)) {
            $disk->deleteDirectory($directory);
        }
    }

    /**
     * Permanently remove folder, all images, card links, and storage directory.
     */
    public function purge(): void
    {
        $this->load('images');

        foreach ($this->images as $image) {
            $image->purge();
        }

        $this->deleteStorageDirectory();
        $this->delete();

        MediaImage::pruneOrphanStoredFiles();
    }
}
