<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kategorier sätts på företaget, inte per lokalisering.
        Schema::create('business_category', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['business_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_category');
    }
};
