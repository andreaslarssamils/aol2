<?php

use App\Enums\SiteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kommun_id')->constrained('kommuner')->restrictOnDelete();
            $table->string('namn');
            $table->string('domain')->nullable()->unique()->comment('null = nås via sökväg');
            $table->string('status')->default(SiteStatus::ComingSoon->value);
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->unique('kommun_id'); // En sajt per kommun
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
