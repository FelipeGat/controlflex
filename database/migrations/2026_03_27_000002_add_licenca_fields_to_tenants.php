<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('tipo_cobranca', ['mensal', 'anual'])->default('mensal')->after('plano_id');
            $table->date('data_inicio_plano')->nullable()->after('tipo_cobranca');
            $table->date('data_fim_plano')->nullable()->after('data_inicio_plano');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['tipo_cobranca', 'data_inicio_plano', 'data_fim_plano']);
        });
    }
};
