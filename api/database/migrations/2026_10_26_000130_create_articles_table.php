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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->jsonb('titel');
            $table->string('slug');
            $table->jsonb('ingress');
            $table->jsonb('body');
            $table->boolean('featured')->default(false);
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kommun_id', 'status']);
        });

        DB::statement('CREATE UNIQUE INDEX articles_slug_kommun_id_unique ON articles (slug, kommun_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
