<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Röda dagar och semesterstängt. Avvikelsen vinner alltid över opening_hours för det datumet.
        Schema::create('opening_hour_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('opens')->nullable();
            $table->time('closes')->nullable();
            $table->boolean('closed')->default(false);
            $table->string('note')->nullable()->comment('"Midsommarafton", "Semesterstängt"');

            $table->index(['business_location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hour_exceptions');
    }
};
