<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flera intervall per dag är tillåtet (11–14 och 17–21). Sortera på opens vid visning.
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('weekday')->comment('0 = måndag ... 6 = söndag');
            $table->time('opens')->nullable();
            $table->time('closes')->nullable();
            $table->boolean('closed')->default(false);

            $table->index(['business_location_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
    }
};
