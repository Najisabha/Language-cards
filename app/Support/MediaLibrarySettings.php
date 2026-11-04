<?php

namespace App\Support;

use App\Models\AppSetting;

class MediaLibrarySettings
{
    public const KEY_MAX_UPLOAD_MB = 'media.max_upload_mb';

    public const KEY_MAX_IMAGES_PER_FOLDER = 'media.max_images_per_folder';

    public const DEFAULT_MAX_UPLOAD_MB = 2;

    public const DEFAULT_MAX_IMAGES_PER_FOLDER = 100;

    public static function maxUploadMb(): int
    {
        $value = (int) (AppSetting::getValue(self::KEY_MAX_UPLOAD_MB, (string) self::DEFAULT_MAX_UPLOAD_MB) ?? self::DEFAULT_MAX_UPLOAD_MB);

        return max(1, min(40, $value));
    }

    public static function maxUploadKb(): int
    {
        return self::maxUploadMb() * 1024;
    }

    public static function maxUploadBytes(): int
    {
        return self::maxUploadMb() * 1024 * 1024;
    }

    public static function maxImagesPerFolder(): int
    {
        $value = (int) (AppSetting::getValue(self::KEY_MAX_IMAGES_PER_FOLDER, (string) self::DEFAULT_MAX_IMAGES_PER_FOLDER) ?? self::DEFAULT_MAX_IMAGES_PER_FOLDER);

        return max(1, min(2000, $value));
    }

    /**
     * @return array{max_upload_mb: int, max_images_per_folder: int}
     */
    public static function all(): array
    {
        return [
            'max_upload_mb' => self::maxUploadMb(),
            'max_images_per_folder' => self::maxImagesPerFolder(),
        ];
    }

    public static function update(int $maxUploadMb, int $maxImagesPerFolder): void
    {
        AppSetting::setValue(self::KEY_MAX_UPLOAD_MB, (string) max(1, min(40, $maxUploadMb)));
        AppSetting::setValue(self::KEY_MAX_IMAGES_PER_FOLDER, (string) max(1, min(2000, $maxImagesPerFolder)));
    }
}
