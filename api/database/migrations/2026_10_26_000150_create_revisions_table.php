<?php

use App\Enums\RevisionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // INKORG, inte tillstånd: raden i innehållstabellen rörs först vid godkännande, då payload
        // skrivs dit, status sätts och applied_at stämplas. Avslag rör aldrig raden.
        // Radera inte godkända rader – de är versionshistorik.
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Nullable för GDPR-radering');
            $table->jsonb('payload');
            $table->string('status')->default(RevisionStatus::Pending->value);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamp('applied_at')->nullable()->comment('När payload faktiskt skrevs till raden');
            $table->timestamps();

            $table->index('status'); // Granskningskön i Filament
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
