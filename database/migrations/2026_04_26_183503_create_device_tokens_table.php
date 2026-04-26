<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('token', 500);
            $table->enum('platform', ['ios', 'android']);
            $table->string('app_version', 32)->nullable();
            $table->string('device_model', 120)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token'], 'unique_user_device_token');
            $table->index(['tenant_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
