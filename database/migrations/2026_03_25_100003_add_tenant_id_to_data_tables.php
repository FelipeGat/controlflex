<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = ['familiares', 'categorias', 'fornecedores', 'bancos', 'despesas', 'receitas', 'investimentos'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete()->after('id');
                $t->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        $tables = ['familiares', 'categorias', 'fornecedores', 'bancos', 'despesas', 'receitas', 'investimentos'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign([$table . '.tenant_id']);
                $t->dropColumn('tenant_id');
            });
        }
    }
};
