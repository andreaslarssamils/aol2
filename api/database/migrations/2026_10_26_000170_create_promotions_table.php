<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Startsidan = ordinarie innehåll för kommunen + aktiva promotions riktade hit.
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->morphs('promotable');
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete()
                ->comment('Orten där innehållet visas (utöver hemkommunen)');
            $table->string('placement');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->smallInteger('priority')->default(0);
            $table->boolean('is_paid')->default(false)->comment('true → visas med "Annons"-etikett');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['kommun_id', 'placement', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
