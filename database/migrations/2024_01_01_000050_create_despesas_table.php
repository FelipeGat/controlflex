<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quem_comprou')->nullable()->constrained('familiares')->onDelete('set null');
            $table->foreignId('onde_comprou')->nullable()->constrained('fornecedores')->onDelete('set null');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->foreignId('forma_pagamento')->nullable()->constrained('bancos')->onDelete('set null');
            $table->decimal('valor', 15, 2);
            $table->date('data_compra');
            $table->date('data_pagamento')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->integer('parcelas')->default(1);
            $table->enum('frequencia', ['diaria', 'semanal', 'quinzenal', 'mensal', 'trimestral', 'semestral', 'anual'])->default('mensal');
            $table->string('grupo_recorrencia_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
