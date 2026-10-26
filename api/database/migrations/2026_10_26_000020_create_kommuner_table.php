<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kommuner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lan_id')->constrained('lan')->restrictOnDelete();
            $table->string('namn');
            $table->char('kod', 4)->unique()->comment('SCB kommunkod, t.ex. "2085"');
            $table->string('slug')->unique();
            $table->decimal('lat', 10, 7)->nullable()->comment('Koordinater för SMHI-vädret');
            $table->decimal('lng', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kommuner');
    }
};
