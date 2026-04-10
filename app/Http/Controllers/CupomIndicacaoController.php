<?php

namespace App\Http\Controllers;

use App\Models\CupomIndicacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CupomIndicacaoController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $cupom = CupomIndicacao::where('tenant_id', $tenantId)->first();

        // Gera o cupom automaticamente se ainda não existe
        if (! $cupom) {
            $cupom = CupomIndicacao::create([
                'tenant_id'            => $tenantId,
                'codigo'               => CupomIndicacao::gerarCodigo(Auth::user()->name),
                'desconto_percentual'  => 20,
            ]);
        }

        // Garante que os atributos com default do DB estejam carregados
        $cupom->refresh();

        $indicacoes = $cupom->indicacoes()
            ->with('tenantIndicado')
            ->latest()
            ->get();

        return view('cupons.index', compact('cupom', 'indicacoes'));
    }
}
