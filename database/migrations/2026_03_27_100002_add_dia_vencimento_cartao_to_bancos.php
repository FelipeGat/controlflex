<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->unsignedTinyInteger('dia_vencimento_cartao')->nullable()->after('saldo_cartao');
            $table->unsignedTinyInteger('dia_fechamento_cartao')->nullable()->after('dia_vencimento_cartao');
        });
    }

    public function down(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['dia_vencimento_cartao', 'dia_fechamento_cartao']);
        });
    }
};
