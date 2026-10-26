<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Synliggörs via promotions (t.ex. på partnerortens startsida).
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->jsonb('titel');
            $table->jsonb('beskrivning');
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->string('code')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
