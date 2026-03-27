<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->string('origem', 30)->default('manual')->after('grupo_recorrencia_id');
            $table->string('numero_documento', 60)->nullable()->after('origem');
        });
    }

    public function down(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->dropColumn(['origem', 'numero_documento']);
        });
    }
};
