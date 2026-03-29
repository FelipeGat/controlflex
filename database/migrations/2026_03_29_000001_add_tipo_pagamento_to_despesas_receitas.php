<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o campo tipo_pagamento em despesas e receitas.
     *
     * Valores para despesas:
     *   dinheiro, pix, debito, credito, transferencia
     *
     * Valores para receitas:
     *   dinheiro, pix, transferencia, deposito, outros
     */
    public function up(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            // tipo_pagamento: como o pagamento foi/será feito (distinto de qual conta foi usada)
            $table->string('tipo_pagamento', 20)->nullable()->after('forma_pagamento');
        });

        Schema::table('receitas', function (Blueprint $table) {
            $table->string('tipo_pagamento', 20)->nullable()->after('forma_recebimento');
        });
    }

    public function down(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->dropColumn('tipo_pagamento');
        });

        Schema::table('receitas', function (Blueprint $table) {
            $table->dropColumn('tipo_pagamento');
        });
    }
};
