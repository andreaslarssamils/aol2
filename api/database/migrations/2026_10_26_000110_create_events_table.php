<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // URL:en bor i slugs-tabellen. Återkommande event läggs in en gång (recurrence_rule på
        // moderposten) och förekomsterna materialiseras som barnrader med parent_event_id.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete()
                ->comment('Sätts av EventObserver ENDAST om den är null – skrivs aldrig över');
            $table->foreignId('business_location_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Arrangör/plats om den finns i katalogen');
            $table->foreignId('parent_event_id')->nullable()->constrained('events')->cascadeOnDelete()
                ->comment('Satt = en förekomst av ett återkommande event');
            $table->string('recurrence_rule')->nullable()->comment('RRULE på moderposten, t.ex. FREQ=WEEKLY;BYDAY=TU');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('namn');
            $table->jsonb('beskrivning');
            $table->boolean('all_day')->default(false);
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
            $table->index(['kommun_id', 'status', 'ends_at']); // "pågår just nu"-frågor
            $table->index('parent_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
