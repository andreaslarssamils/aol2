<?php

use App\Enums\ClaimStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ägare tar över en redaktionell post. Godkänt anspråk skapar business_user-rad
        // och sätter businesses.managed_by = self.
        Schema::create('business_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(ClaimStatus::Pending->value);
            $table->string('verified_via')->nullable()->comment('epost-domän / telefon / brev / bolagsuppgift');
            $table->text('message')->nullable()->comment('Vad den sökande skrev');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_claims');
    }
};
