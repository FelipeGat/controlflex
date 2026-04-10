<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupons_indicacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('codigo', 50)->unique();
            $table->decimal('desconto_percentual', 5, 2)->default(20.00);
            $table->unsignedInteger('creditos_disponiveis')->default(0);
            $table->unsignedInteger('creditos_utilizados')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('indicacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cupom_id');
            $table->unsignedBigInteger('tenant_indicado_id');
            $table->timestamps();

            $table->foreign('cupom_id')->references('id')->on('cupons_indicacao')->cascadeOnDelete();
            $table->foreign('tenant_indicado_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicacoes');
        Schema::dropIfExists('cupons_indicacao');
    }
};
