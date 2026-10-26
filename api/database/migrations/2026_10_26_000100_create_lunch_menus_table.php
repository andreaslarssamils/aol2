<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lunch_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('year');
            $table->smallInteger('week');
            $table->jsonb('menu')->comment('{"mon": [...], "tue": [...]}');
            $table->string('status')->default(ContentStatus::Pending->value);
            $table->timestamps();

            $table->unique(['business_location_id', 'year', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_menus');
    }
};
