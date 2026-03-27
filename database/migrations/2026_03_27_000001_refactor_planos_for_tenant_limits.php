<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Adicionar max_bancos aos planos
        Schema::table('planos', function (Blueprint $table) {
            $table->integer('max_bancos')->default(-1)->after('max_usuarios');
        });

        // Remover max_clientes dos planos (não mais necessário)
        Schema::table('planos', function (Blueprint $table) {
            $table->dropColumn('max_clientes');
        });

        // Remover plano_id das revendas (plano é do cliente, não da revenda)
        Schema::table('revendas', function (Blueprint $table) {
            $table->dropForeign(['plano_id']);
            $table->dropColumn('plano_id');
        });

        // Remover coluna legada 'plano' (enum string) dos tenants
        // O plano_id FK já existe e é o correto
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('plano');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('plano', ['basic', 'pro'])->default('basic')->after('nome');
        });

        Schema::table('revendas', function (Blueprint $table) {
            $table->foreignId('plano_id')->nullable()->constrained('planos')->nullOnDelete();
        });

        Schema::table('planos', function (Blueprint $table) {
            $table->integer('max_clientes')->default(-1)->after('max_usuarios');
        });

        Schema::table('planos', function (Blueprint $table) {
            $table->dropColumn('max_bancos');
        });
    }
};
