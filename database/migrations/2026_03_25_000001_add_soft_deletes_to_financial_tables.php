<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->softDeletes()->after('grupo_recorrencia_id');
        });

        Schema::table('receitas', function (Blueprint $table) {
            $table->softDeletes()->after('grupo_recorrencia_id');
        });

        Schema::table('investimentos', function (Blueprint $table) {
            $table->softDeletes()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('receitas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('investimentos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};