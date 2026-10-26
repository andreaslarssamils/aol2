<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Om oss, Kontakt, Integritetspolicy m.fl. URL:en bor i slugs-tabellen, som hanterar
        // nationella sidor (kommun_id null) via sina två partiella index.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->nullable()->constrained('kommuner')->restrictOnDelete()
                ->comment('null = nationell sida, satt = ortspecifik (överskrider nationell vid samma slug)');
            $table->jsonb('titel');
            $table->jsonb('body')->comment('Översättningsbar, HTML från rich text-editor');
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
