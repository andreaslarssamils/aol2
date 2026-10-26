<?php

use App\Enums\RevisionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Publicerat innehåll rörs inte förrän revisionen godkänns.
        // Radera inte godkända rader – de är versionshistorik.
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->jsonb('payload');
            $table->string('status')->default(RevisionStatus::Pending->value);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
