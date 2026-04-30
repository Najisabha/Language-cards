<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('decks', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('id')->constrained('levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('decks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
        });
    }
};
