<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->nullable()->constrained('kommuner')->restrictOnDelete()
                ->comment('null = nationell sida, satt = ortspecifik (överskriver nationell vid samma slug)');
            $table->string('slug')->comment('"om-oss", "kontakt", "integritetspolicy"');
            $table->jsonb('titel');
            $table->jsonb('body')->comment('Översättningsbar, HTML från rich text-editor');
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // NULL räknas som distinkt i unika index, därför COALESCE – nationella slugs blir unika sinsemellan.
        DB::statement('CREATE UNIQUE INDEX pages_slug_kommun_id_unique ON pages (slug, COALESCE(kommun_id, 0)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
