<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // URL:er per språk + historik. Byter innehållet namn behålls den gamla raden med
        // is_canonical = false så att frontend kan svara 301 till den kanoniska.
        Schema::create('slugs', function (Blueprint $table) {
            $table->id();
            $table->string('slugable_type')->comment('Polymorf: article / event / page / business_location');
            $table->unsignedBigInteger('slugable_id');
            $table->foreignId('kommun_id')->nullable()->constrained('kommuner')->restrictOnDelete()
                ->comment('null = nationell (t.ex. integritetspolicy)');
            $table->string('locale', 8)->comment('"sv", "en", "ar" ... – en rad per språk');
            $table->string('slug');
            $table->boolean('is_canonical')->default(true)->comment('false = gammal URL som ska 301:a hit');
            $table->timestamp('created_at')->nullable();

            $table->index(['slugable_type', 'slugable_id', 'locale']);
        });

        // Två partiella unika index: NULL räknas som distinkt, så nationella rader behöver ett eget.
        DB::statement('CREATE UNIQUE INDEX slugs_locale_kommun_type_slug_unique ON slugs (locale, kommun_id, slugable_type, slug) WHERE kommun_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX slugs_national_locale_type_slug_unique ON slugs (locale, slugable_type, slug) WHERE kommun_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('slugs');
    }
};
