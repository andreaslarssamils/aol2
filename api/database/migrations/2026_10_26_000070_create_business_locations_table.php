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
        // URL:en bor i slugs-tabellen. Flera rader per företag och ort är tillåtet. Status följer
        // samma publiceringsregel som allt annat: en lokalisering skapad via revision är draft tills
        // den godkänns, och katalogen visar bara published-rader vars företag också är published.
        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete();
            $table->string('namn')->nullable()->comment('Överskriver företagsnamnet vid behov, "Acme Centrum"');
            $table->string('telefon')->nullable();
            $table->string('adress')->nullable();
            $table->string('postnummer')->nullable();
            $table->string('ort')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'kommun_id']);
            $table->index(['kommun_id', 'status']); // Driver katalogens listsidor
        });

        // Bara en primär lokalisering per företag bland de som inte är raderade.
        DB::statement('CREATE UNIQUE INDEX business_locations_primary_unique ON business_locations (business_id) WHERE is_primary AND deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('business_locations');
    }
};
