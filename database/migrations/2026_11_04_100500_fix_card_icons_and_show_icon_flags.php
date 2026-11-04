<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clear truncated image URLs wrongly stored in the emoji column.
        DB::table('cards')
            ->where(function ($query) {
                $query->where('icon', 'like', 'http://%')
                    ->orWhere('icon', 'like', 'https://%')
                    ->orWhere('icon', 'like', '/%');
            })
            ->update(['icon' => null]);

        // Enable icon display when a real emoji/text icon exists.
        DB::table('cards')
            ->where('show_icon', false)
            ->whereNotNull('icon')
            ->where('icon', '!=', '')
            ->update(['show_icon' => true]);

        // Enable icon display when a linked/shared image exists.
        DB::table('cards')
            ->where('show_icon', false)
            ->where(function ($query) {
                $query->whereNotNull('media_image_id')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('icon_image_path')
                            ->where('icon_image_path', '!=', '');
                    });
            })
            ->update(['show_icon' => true]);
    }

    public function down(): void
    {
        // Irreversible data repair.
    }
};
