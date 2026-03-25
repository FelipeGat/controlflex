<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Alterar role de enum para string para suportar super_admin, admin_revenda
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(30) NOT NULL DEFAULT 'master'");

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('revenda_id')->nullable()->constrained('revendas')->nullOnDelete()->after('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['revenda_id']);
            $table->dropColumn('revenda_id');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('master', 'membro') NOT NULL DEFAULT 'master'");
    }
};
