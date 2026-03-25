<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('banco_id')->nullable()->constrained('bancos')->onDelete('set null');
            $table->string('nome_ativo');
            $table->string('tipo_investimento');
            $table->date('data_aporte');
            $table->decimal('valor_aportado', 15, 2);
            $table->decimal('quantidade_cotas', 15, 6)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investimentos');
    }
};
