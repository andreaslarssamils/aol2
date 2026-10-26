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
            $table->uuid('token')->unique();
            $table->timestamp('consented_at')->comment('GDPR-samtycke');
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
