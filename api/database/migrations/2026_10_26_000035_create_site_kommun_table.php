<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gör "Allt om Västerbergslagen" (Ludvika + Smedjebacken) möjlig. Listfrågor blir kommun_id IN (...).
        Schema::create('site_kommun', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kommun_id')->constrained('kommuner')->cascadeOnDelete();
            $table->smallInteger('sort_order')->default(0);

            $table->primary(['site_id', 'kommun_id']);
            $table->index('kommun_id'); // Vilka sajter visar den här kommunen?
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_kommun');
    }
};
