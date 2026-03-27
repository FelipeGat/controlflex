<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->boolean('tem_conta_corrente')->default(false)->after('tipo_conta');
            $table->boolean('tem_poupanca')->default(false)->after('tem_conta_corrente');
            $table->boolean('tem_cartao_credito')->default(false)->after('tem_poupanca');
            $table->boolean('eh_dinheiro')->default(false)->after('tem_cartao_credito');
            $table->decimal('saldo_poupanca', 15, 2)->default(0)->after('saldo');
        });

        // Migrar dados existentes
        DB::table('bancos')->where('tipo_conta', 'Conta Corrente')->update(['tem_conta_corrente' => true]);
        DB::table('bancos')->where('tipo_conta', 'Dinheiro')->update(['eh_dinheiro' => true]);

        // Poupança: mover saldo para saldo_poupanca
        DB::table('bancos')->where('tipo_conta', 'Poupanca')->update([
            'tem_poupanca'   => true,
            'saldo_poupanca' => DB::raw('saldo'),
            'saldo'          => 0,
        ]);
        // Variação com acento
        DB::table('bancos')->where('tipo_conta', 'Poupança')->update([
            'tem_poupanca'   => true,
            'saldo_poupanca' => DB::raw('saldo'),
            'saldo'          => 0,
        ]);

        // Cartão de Crédito (com e sem acento)
        DB::table('bancos')->where('tipo_conta', 'Cartao de Credito')->update(['tem_cartao_credito' => true]);
        DB::table('bancos')->where('tipo_conta', 'Cartão de Crédito')->update(['tem_cartao_credito' => true]);

        // Tornar tipo_conta nullable (deprecado)
        Schema::table('bancos', function (Blueprint $table) {
            $table->string('tipo_conta')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Restaurar tipo_conta a partir dos booleans
        DB::table('bancos')->where('tem_conta_corrente', true)->update(['tipo_conta' => 'Conta Corrente']);
        DB::table('bancos')->where('eh_dinheiro', true)->update(['tipo_conta' => 'Dinheiro']);
        DB::table('bancos')->where('tem_poupanca', true)->where('tem_conta_corrente', false)->update([
            'tipo_conta' => 'Poupanca',
            'saldo'      => DB::raw('saldo_poupanca'),
        ]);
        DB::table('bancos')->where('tem_cartao_credito', true)->where('tem_conta_corrente', false)->update(['tipo_conta' => 'Cartao de Credito']);

        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['tem_conta_corrente', 'tem_poupanca', 'tem_cartao_credito', 'eh_dinheiro', 'saldo_poupanca']);
        });
    }
};
