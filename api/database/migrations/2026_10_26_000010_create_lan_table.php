<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lan', function (Blueprint $table) {
            $table->id();
            $table->string('namn');
            $table->char('kod', 2)->unique()->comment('SCB länskod, t.ex. "20"');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lan');
    }
};
