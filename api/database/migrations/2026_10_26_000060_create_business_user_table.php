<?php

use App\Enums\BusinessUserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_user', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default(BusinessUserRole::Owner->value);
            $table->timestamp('created_at')->nullable();

            $table->primary(['business_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_user');
    }
};
