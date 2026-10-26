<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Följer samma publiceringsregel som allt annat: draft tills en revision godkänns.
        Schema::create('lunch_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('iso_year')->comment('ISO-år, inte kalenderår – vecka 1 kan börja i december');
            $table->smallInteger('week');
            $table->jsonb('menu')->comment('{"mon": [...], "tue": [...]}');
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX lunch_menus_location_iso_year_week_unique ON lunch_menus (business_location_id, iso_year, week) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('lunch_menus');
    }
};
