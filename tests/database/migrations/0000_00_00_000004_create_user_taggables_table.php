<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('user_tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();

            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_taggables');
    }
};
