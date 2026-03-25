<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Revenda;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaasDashboardController extends Controller
{
    public function index()
    {
        $totalRevendas   = Revenda::count();
        $revendasAtivas  = Revenda::where('status', 'ativo')->count();
        $revendasInativas = $totalRevendas - $revendasAtivas;

        $totalTenants   = Tenant::count();
        $tenantsAtivos  = Tenant::where('status', 'ativo')->where('ativo', true)->count();
        $tenantsInativos = $totalTenants - $tenantsAtivos;

        $totalUsuarios = User::whereIn('role', ['master', 'membro'])->count();

        $ultimasRevendas = Revenda::with('plano', 'admin')
            ->withCount('tenants')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevendas', 'revendasAtivas', 'revendasInativas',
            'totalTenants', 'tenantsAtivos', 'tenantsInativos',
            'totalUsuarios', 'ultimasRevendas'
        ));
    }
}
