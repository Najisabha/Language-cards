<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_images', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->string('extension', 16)->nullable();
            $table->timestamps();
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('media_image_id')
                ->nullable()
                ->after('icon_image_path')
                ->constrained('media_images')
                ->nullOnDelete();
        });

        $paths = DB::table('cards')
            ->whereNotNull('icon_image_path')
            ->where('icon_image_path', '!=', '')
            ->distinct()
            ->pluck('icon_image_path');

        $pathToId = [];

        foreach ($paths as $path) {
            $basename = basename((string) $path);
            $extension = pathinfo($basename, PATHINFO_EXTENSION);
            $name = pathinfo($basename, PATHINFO_FILENAME);

            if ($name === '') {
                $name = $basename !== '' ? $basename : 'image';
            }

            $id = DB::table('media_images')->insertGetId([
                'name' => $name,
                'path' => $path,
                'extension' => $extension !== '' ? strtolower($extension) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pathToId[$path] = $id;
        }

        foreach ($pathToId as $path => $id) {
            DB::table('cards')
                ->where('icon_image_path', $path)
                ->update(['media_image_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_image_id');
        });

        Schema::dropIfExists('media_images');
    }
};
