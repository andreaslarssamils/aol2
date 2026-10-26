<?php

use App\Enums\SiteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vilka kommuner sajten visar bor i site_kommun. Innehåll hör fortfarande hemma i EN kommun.
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('namn')->comment('"Allt om Ludvika", "Allt om Västerbergslagen"');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique()->comment('null = nås via sökväg');
            $table->foreignId('primary_kommun_id')->nullable()->constrained('kommuner')->nullOnDelete()
                ->comment('Ort för väder, startsidans fokus och kanonisk URL när sajten täcker flera');
            $table->string('status')->default(SiteStatus::ComingSoon->value);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
