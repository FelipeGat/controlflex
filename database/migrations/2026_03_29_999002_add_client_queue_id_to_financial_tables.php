<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->string('client_queue_id')->nullable()->index()->after('origem');
        });

        Schema::table('receitas', function (Blueprint $table) {
            $table->string('client_queue_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->dropColumn('client_queue_id');
        });
        Schema::table('receitas', function (Blueprint $table) {
            $table->dropColumn('client_queue_id');
        });
    }
};
