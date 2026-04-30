<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->foreignId('language_id')
                ->nullable()
                ->after('id')
                ->constrained('languages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_id');
        });
    }
};
