<?php

use App\Models\Familiar;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $masters = User::where('role', 'master')
            ->whereNotNull('tenant_id')
            ->whereNull('familiar_id')
            ->get();

        foreach ($masters as $master) {
            $familiar = DB::table('familiares')->insertGetId([
                'tenant_id'     => $master->tenant_id,
                'user_id'       => $master->id,
                'nome'          => $master->name,
                'salario'       => 0,
                'limite_cartao' => 0,
                'limite_cheque' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $master->update(['familiar_id' => $familiar]);
        }
    }

    public function down(): void
    {
        // Busca familiares vinculados a masters e remove
        $masters = User::where('role', 'master')
            ->whereNotNull('familiar_id')
            ->get();

        foreach ($masters as $master) {
            DB::table('familiares')->where('id', $master->familiar_id)->delete();
            $master->update(['familiar_id' => null]);
        }
    }
};
