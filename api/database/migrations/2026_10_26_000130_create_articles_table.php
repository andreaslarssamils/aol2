<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // URL:en bor i slugs-tabellen – en per språk, plus gamla Wagtail-sökvägar som icke-kanoniska rader.
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Nullable för GDPR-radering – bylinen bevaras i author_name');
            $table->string('author_name')->nullable();
            $table->jsonb('titel');
            $table->jsonb('ingress');
            $table->jsonb('body');
            $table->boolean('featured')->default(false);
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kommun_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
