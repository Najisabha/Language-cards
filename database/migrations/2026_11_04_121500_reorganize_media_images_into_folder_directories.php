<?php

use App\Models\MediaFolder;
use App\Models\MediaImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $disk = Storage::disk('card_uploads');

        MediaFolder::query()->orderBy('id')->each(function (MediaFolder $folder) use ($disk) {
            $disk->makeDirectory($folder->storageDirectory());
        });

        MediaImage::query()
            ->with('folder')
            ->orderBy('id')
            ->each(function (MediaImage $image) {
                $directory = $image->folder_id
                    ? MediaImage::directoryForFolderId((int) $image->folder_id)
                    : 'card-icons/_unassigned';

                $image->relocateToDirectory($directory);
            });
    }

    public function down(): void
    {
        // Irreversible data move; intentionally left empty.
    }
};
