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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete()
                ->comment('Synkas från business_location (EventObserver) när en sådan är satt');
            $table->foreignId('business_location_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Arrangör/plats om den finns i katalogen');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('namn');
            $table->string('slug');
            $table->jsonb('beskrivning');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue_name')->nullable()->comment('Fritext om platsen inte finns i katalogen');
            $table->string('adress')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('pris')->nullable();
            $table->string('ticket_url')->nullable();
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kommun_id', 'status', 'starts_at']);
        });

        DB::statement('CREATE UNIQUE INDEX events_slug_kommun_id_unique ON events (slug, kommun_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
