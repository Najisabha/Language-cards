<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('deck_id')->constrained('categories')->nullOnDelete();
        });

        DB::table('decks')->orderBy('id')->chunkById(100, function ($decks) {
            foreach ($decks as $deck) {
                $categoryId = DB::table('categories')->insertGetId([
                    'deck_id' => $deck->id,
                    'name' => 'عام',
                    'description' => 'تصنيف افتراضي',
                    'position' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('cards')->where('deck_id', $deck->id)->update(['category_id' => $categoryId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
