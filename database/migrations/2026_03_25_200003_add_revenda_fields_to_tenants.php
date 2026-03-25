<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('revenda_id')->nullable()->constrained('revendas')->nullOnDelete()->after('id');
            $table->foreignId('plano_id')->nullable()->constrained('planos')->nullOnDelete()->after('plano');
            $table->string('status')->default('ativo')->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['revenda_id']);
            $table->dropForeign(['plano_id']);
            $table->dropColumn(['revenda_id', 'plano_id', 'status']);
        });
    }
};
