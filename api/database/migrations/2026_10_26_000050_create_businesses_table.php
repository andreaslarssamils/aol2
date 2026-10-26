<?php

use App\Enums\BusinessKind;
use App\Enums\ContentStatus;
use App\Enums\ManagedBy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->default(BusinessKind::Company->value);
            $table->string('managed_by')->default(ManagedBy::Editorial->value);
            $table->boolean('auto_approve')->default(false);
            $table->string('namn');
            $table->jsonb('beskrivning')->comment('Översättningsbar sv/en – gemensam för alla orter');
            $table->string('epost')->nullable();
            $table->string('webbplats')->nullable();
            $table->string('status')->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
