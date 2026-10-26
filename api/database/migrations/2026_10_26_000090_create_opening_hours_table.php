<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->comment('0 = måndag ... 6 = söndag');
            $table->time('opens')->nullable();
            $table->time('closes')->nullable();
            $table->boolean('closed')->default(false);

            $table->unique(['business_location_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
    }
};
