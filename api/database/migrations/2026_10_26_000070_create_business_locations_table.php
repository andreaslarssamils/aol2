<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete();
            $table->string('slug')->comment('Sidan per ort: /verksamheter/{slug}');
            $table->string('namn')->nullable()->comment('Överskriver företagsnamnet vid behov');
            $table->string('telefon')->nullable();
            $table->string('adress')->nullable();
            $table->string('postnummer')->nullable();
            $table->string('ort')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('kommun_id'); // Driver listsidorna via join
        });

        // Partiella unika index: en soft-raderad rad ska inte blockera slug/ort för alltid.
        DB::statement('CREATE UNIQUE INDEX business_locations_slug_kommun_id_unique ON business_locations (slug, kommun_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX business_locations_business_id_kommun_id_unique ON business_locations (business_id, kommun_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('business_locations');
    }
};
