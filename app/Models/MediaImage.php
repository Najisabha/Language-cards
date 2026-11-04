<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaImage extends Model
{
    protected $fillable = [
        'name',
        'path',
        'extension',
    ];

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public static function createFromUpload(UploadedFile $file): self
    {
        $path = $file->store('card-icons', 'card_uploads');
        $original = $file->getClientOriginalName();
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION) ?: (string) $file->extension());
        $name = pathinfo($original, PATHINFO_FILENAME);

        if ($name === '') {
            $name = pathinfo($path, PATHINFO_FILENAME) ?: 'image';
        }

        return static::query()->create([
            'name' => $name,
            'path' => $path,
            'extension' => $extension !== '' ? $extension : null,
        ]);
    }

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $path = $this->path;

        if (Storage::disk('card_uploads')->exists($path)) {
            return asset('uploads/'.$path);
        }

        if (Storage::disk('public')->exists($path)) {
            try {
                Storage::disk('card_uploads')->put(
                    $path,
                    Storage::disk('public')->get($path)
                );
                Storage::disk('public')->delete($path);
            } catch (\Throwable) {
                return Storage::disk('public')->url($path);
            }

            if (Storage::disk('card_uploads')->exists($path)) {
                return asset('uploads/'.$path);
            }

            return Storage::disk('public')->url($path);
        }

        return null;
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

        if (Storage::disk('card_uploads')->exists($this->path)) {
            Storage::disk('card_uploads')->delete($this->path);

            return;
        }

        if (Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }
    }
}
