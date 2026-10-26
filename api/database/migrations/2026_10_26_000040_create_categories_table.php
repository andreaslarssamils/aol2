<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type');
            $table->jsonb('namn')->comment('{"sv": "...", "en": "..."}');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->smallInteger('sort_order')->default(0);

            $table->unique(['slug', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
