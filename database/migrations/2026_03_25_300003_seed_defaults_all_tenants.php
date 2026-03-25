<?php

use Database\Seeders\CategoriasDefaultSeeder;
use Database\Seeders\FornecedoresDefaultSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Busca todos os masters de todos os tenants
        $masters = DB::table('users')
            ->whereNotNull('tenant_id')
            ->where('role', 'master')
            ->select('id', 'tenant_id')
            ->get();

        foreach ($masters as $master) {
            CategoriasDefaultSeeder::seedParaTenant($master->tenant_id, $master->id);
            FornecedoresDefaultSeeder::seedParaTenant($master->tenant_id, $master->id);
        }
    }

    public function down(): void
    {
        // Não remove dados em rollback
    }
};
