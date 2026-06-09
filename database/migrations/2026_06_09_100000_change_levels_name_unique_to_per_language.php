<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['language_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropUnique(['language_id', 'name']);
            $table->unique('name');
        });
    }
};
