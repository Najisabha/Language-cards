<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('media_images', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('id')
                ->constrained('media_folders')
                ->cascadeOnDelete();
        });

        $hasImages = DB::table('media_images')->exists();
        if ($hasImages) {
            $folderId = DB::table('media_folders')->insertGetId([
                'name' => 'عام',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('media_images')
                ->whereNull('folder_id')
                ->update(['folder_id' => $folderId]);
        }
    }

    public function down(): void
    {
        Schema::table('media_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists('media_folders');
    }
};
