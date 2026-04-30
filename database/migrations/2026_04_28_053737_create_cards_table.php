<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();

            $table->string('word');

            $table->enum('front_bg_type', ['color', 'image'])->default('color');
            $table->string('front_bg_value')->default('#ffffff');

            $table->string('en_meaning')->nullable();
            $table->string('ar_meaning')->nullable();
            $table->text('explanation')->nullable();
            $table->string('icon', 16)->nullable();

            $table->boolean('show_en')->default(true);
            $table->boolean('show_ar')->default(true);
            $table->boolean('show_explanation')->default(false);
            $table->boolean('show_icon')->default(false);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
