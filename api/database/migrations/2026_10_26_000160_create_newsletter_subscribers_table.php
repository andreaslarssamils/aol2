<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete();
            $table->string('email');
            $table->uuid('token')->unique()->comment('Avprenumerationslänken slår upp på token');
            $table->timestamp('consented_at')->comment('GDPR-samtycke');
            $table->string('consent_ip', 45)->nullable()->comment('Bevis för hur samtycket gavs');
            $table->string('consent_source')->nullable()->comment('"startsida" / "evenemangssida" / "import"');
            $table->timestamp('confirmed_at')->nullable()->comment('Double opt-in');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['email', 'kommun_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
