<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('titular_id')->nullable()->constrained('familiares')->onDelete('set null');
            $table->string('nome');
            $table->enum('tipo_conta', ['Conta Corrente', 'Poupança', 'Dinheiro', 'Cartão de Crédito'])->default('Conta Corrente');
            $table->string('codigo_banco')->nullable();
            $table->string('agencia')->nullable();
            $table->string('conta')->nullable();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->decimal('cheque_especial', 15, 2)->default(0);
            $table->decimal('saldo_cheque', 15, 2)->default(0);
            $table->decimal('limite_cartao', 15, 2)->default(0);
            $table->decimal('saldo_cartao', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
