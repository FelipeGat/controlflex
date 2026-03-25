<?php

use Database\Seeders\BancosDefaultSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $masters = DB::table('users')
            ->whereNotNull('tenant_id')
            ->where('role', 'master')
            ->select('id', 'tenant_id')
            ->get();

        foreach ($masters as $master) {
            BancosDefaultSeeder::seedParaTenant($master->tenant_id, $master->id);
        }
    }

    public function down(): void
    {
        // Não remove dados em rollback
    }
};
