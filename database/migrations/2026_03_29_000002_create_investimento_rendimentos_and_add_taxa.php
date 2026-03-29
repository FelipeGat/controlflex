<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona taxa configurada ao ativo
        Schema::table('investimentos', function (Blueprint $table) {
            $table->decimal('percentual_mensal', 8, 4)->nullable()->after('quantidade_cotas')
                ->comment('Taxa de rendimento mensal configurada (%)');
            $table->decimal('percentual_anual', 8, 4)->nullable()->after('percentual_mensal')
                ->comment('Taxa de rendimento anual configurada (%)');
        });

        // Tabela de histórico de rendimentos
        Schema::create('investimento_rendimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investimento_id')->constrained('investimentos')->onDelete('cascade');
            $table->unsignedBigInteger('tenant_id');
            $table->date('data');
            $table->decimal('valor_atual', 15, 2)->comment('Valor total do ativo nesta data');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['investimento_id', 'data']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investimento_rendimentos');

        Schema::table('investimentos', function (Blueprint $table) {
            $table->dropColumn(['percentual_mensal', 'percentual_anual']);
        });
    }
};
