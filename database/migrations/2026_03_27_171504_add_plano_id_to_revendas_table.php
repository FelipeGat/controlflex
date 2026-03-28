<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revendas', function (Blueprint $table) {
            $table->foreignId('plano_id')->nullable()->after('status')->constrained('planos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('revendas', function (Blueprint $table) {
            $table->dropForeign(['plano_id']);
            $table->dropColumn('plano_id');
        });
    }
};
