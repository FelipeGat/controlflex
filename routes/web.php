<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DespesaController;
use App\Http\Controllers\ReceitaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\InvestimentoController;
use App\Http\Controllers\FluxoCaixaController;
use App\Http\Controllers\LancamentoDiarioController;
use App\Http\Controllers\LancamentoController;
use App\Http\Controllers\MembroController;
use App\Http\Controllers\Admin\SaasDashboardController;
use App\Http\Controllers\Admin\PlanoController;
use App\Http\Controllers\Admin\RevendaAdminController;
use App\Http\Controllers\Revenda\ClienteController;
use App\Http\Controllers\Revenda\RevendaDashboardController;
use Illuminate\Support\Facades\Route;

// Redirect dinâmico baseado em role
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->homeRoute());
    }
    return redirect()->route('login');
});

// ─── Super Admin ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [SaasDashboardController::class, 'index'])->name('admin.dashboard');

    // Planos
    Route::get('/planos', [PlanoController::class, 'index'])->name('admin.planos.index');
    Route::post('/planos', [PlanoController::class, 'store'])->name('admin.planos.store');
    Route::put('/planos/{plano}', [PlanoController::class, 'update'])->name('admin.planos.update');
    Route::delete('/planos/{plano}', [PlanoController::class, 'destroy'])->name('admin.planos.destroy');

    // Revendas
    Route::get('/revendas', [RevendaAdminController::class, 'index'])->name('admin.revendas.index');
    Route::post('/revendas', [RevendaAdminController::class, 'store'])->name('admin.revendas.store');
    Route::put('/revendas/{revenda}', [RevendaAdminController::class, 'update'])->name('admin.revendas.update');
    Route::delete('/revendas/{revenda}', [RevendaAdminController::class, 'destroy'])->name('admin.revendas.destroy');
    Route::post('/revendas/provisionar', [RevendaAdminController::class, 'provisionar'])->name('admin.revendas.provisionar');
    Route::post('/revendas/{revenda}/reset-senha', [RevendaAdminController::class, 'resetSenha'])->name('admin.revendas.resetSenha');
});

// ─── Admin Revenda ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin_revenda'])->prefix('revenda')->group(function () {
    Route::get('/dashboard', [RevendaDashboardController::class, 'index'])->name('revenda.dashboard');
    Route::get('/clientes', [ClienteController::class, 'index'])->name('revenda.clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('revenda.clientes.store');
    Route::put('/clientes/{tenant}', [ClienteController::class, 'update'])->name('revenda.clientes.update');
    Route::delete('/clientes/{tenant}', [ClienteController::class, 'destroy'])->name('revenda.clientes.destroy');
    Route::post('/clientes/{tenant}/reset-senha', [ClienteController::class, 'resetSenha'])->name('revenda.clientes.resetSenha');
    Route::post('/clientes/{tenant}/renovar', [ClienteController::class, 'renovar'])->name('revenda.clientes.renovar');
});

// ─── Tenant (Master / Membro) ───────────────────────────────────────────────
Route::middleware(['auth', 'tenant.ativo'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Fluxo de Caixa / Baixas
    Route::get('/fluxo-caixa', [FluxoCaixaController::class, 'index'])->name('fluxo-caixa.index');
    Route::post('/fluxo-caixa/baixar-despesa/{despesa}', [FluxoCaixaController::class, 'baixarDespesa'])->name('fluxo-caixa.baixar-despesa');
    Route::post('/fluxo-caixa/estornar-despesa/{despesa}', [FluxoCaixaController::class, 'estornarDespesa'])->name('fluxo-caixa.estornar-despesa');
    Route::post('/fluxo-caixa/baixar-receita/{receita}', [FluxoCaixaController::class, 'baixarReceita'])->name('fluxo-caixa.baixar-receita');
    Route::post('/fluxo-caixa/estornar-receita/{receita}', [FluxoCaixaController::class, 'estornarReceita'])->name('fluxo-caixa.estornar-receita');

    // Lançamentos (extrato bancário)
    Route::get('/lancamentos', [LancamentoController::class, 'index'])->name('lancamentos.index')->middleware('permissao:despesas,criar');
    Route::post('/lancamentos/escanear', [LancamentoController::class, 'escanear'])->name('lancamentos.escanear')->middleware('permissao:despesas,criar');
    Route::post('/lancamentos/importar-extrato', [LancamentoController::class, 'importarExtrato'])->name('lancamentos.importar-extrato')->middleware('permissao:despesas,criar');
    Route::post('/lancamentos/confirmar-importacao', [LancamentoController::class, 'confirmarImportacao'])->name('lancamentos.confirmar-importacao')->middleware('permissao:despesas,criar');
    // Retrocompatibilidade
    Route::get('/lancamentos-diarios', fn() => redirect()->route('lancamentos.index'))->name('lancamentos-diarios.index');
    Route::post('/lancamentos-diarios/escanear', [LancamentoDiarioController::class, 'escanear'])->name('lancamentos-diarios.escanear');

    // Despesas
    Route::get('/despesas', [DespesaController::class, 'index'])->name('despesas.index')->middleware('permissao:despesas,ver');
    Route::post('/despesas', [DespesaController::class, 'store'])->name('despesas.store')->middleware('permissao:despesas,criar');
    Route::put('/despesas/{despesa}', [DespesaController::class, 'update'])->name('despesas.update')->middleware('permissao:despesas,editar');
    Route::delete('/despesas/{despesa}', [DespesaController::class, 'destroy'])->name('despesas.destroy')->middleware('permissao:despesas,excluir');

    // Receitas
    Route::get('/receitas', [ReceitaController::class, 'index'])->name('receitas.index')->middleware('permissao:receitas,ver');
    Route::post('/receitas', [ReceitaController::class, 'store'])->name('receitas.store')->middleware('permissao:receitas,criar');
    Route::put('/receitas/{receita}', [ReceitaController::class, 'update'])->name('receitas.update')->middleware('permissao:receitas,editar');
    Route::delete('/receitas/{receita}', [ReceitaController::class, 'destroy'])->name('receitas.destroy')->middleware('permissao:receitas,excluir');

    // Categorias
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index')->middleware('permissao:categorias,ver');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store')->middleware('permissao:categorias,criar');

    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update')->middleware('permissao:categorias,editar');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy')->middleware('permissao:categorias,excluir');

    // Familiares
    Route::get('/familiares', [FamiliarController::class, 'index'])->name('familiares.index')->middleware('permissao:familiares,ver');
    Route::post('/familiares', [FamiliarController::class, 'store'])->name('familiares.store')->middleware('permissao:familiares,criar');
    Route::put('/familiares/{familiar}', [FamiliarController::class, 'update'])->name('familiares.update')->middleware('permissao:familiares,editar');
    Route::delete('/familiares/{familiar}', [FamiliarController::class, 'destroy'])->name('familiares.destroy')->middleware('permissao:familiares,excluir');

    // Fornecedores
    Route::get('/fornecedores', [FornecedorController::class, 'index'])->name('fornecedores.index')->middleware('permissao:fornecedores,ver');
    Route::post('/fornecedores', [FornecedorController::class, 'store'])->name('fornecedores.store')->middleware('permissao:fornecedores,criar');

    Route::put('/fornecedores/{fornecedor}', [FornecedorController::class, 'update'])->name('fornecedores.update')->middleware('permissao:fornecedores,editar');
    Route::delete('/fornecedores/{fornecedor}', [FornecedorController::class, 'destroy'])->name('fornecedores.destroy')->middleware('permissao:fornecedores,excluir');

    // Bancos
    Route::get('/bancos', [BancoController::class, 'index'])->name('bancos.index')->middleware('permissao:bancos,ver');
    Route::post('/bancos', [BancoController::class, 'store'])->name('bancos.store')->middleware('permissao:bancos,criar');
    Route::put('/bancos/{banco}', [BancoController::class, 'update'])->name('bancos.update')->middleware('permissao:bancos,editar');
    Route::post('/bancos/{banco}/ajustar-saldo', [BancoController::class, 'ajustarSaldo'])->name('bancos.ajustar-saldo')->middleware('permissao:bancos,editar');
    Route::post('/bancos/{banco}/ajustar-saldo-cartao', [BancoController::class, 'ajustarSaldoCartao'])->name('bancos.ajustar-saldo-cartao')->middleware('permissao:bancos,editar');
    Route::post('/bancos/{banco}/ajustar-saldo-poupanca', [BancoController::class, 'ajustarSaldoPoupanca'])->name('bancos.ajustar-saldo-poupanca')->middleware('permissao:bancos,editar');
    Route::delete('/bancos/{banco}', [BancoController::class, 'destroy'])->name('bancos.destroy')->middleware('permissao:bancos,excluir');

    // Investimentos
    Route::get('/investimentos', [InvestimentoController::class, 'index'])->name('investimentos.index')->middleware('permissao:investimentos,ver');
    Route::post('/investimentos', [InvestimentoController::class, 'store'])->name('investimentos.store')->middleware('permissao:investimentos,criar');
    Route::put('/investimentos/{investimento}', [InvestimentoController::class, 'update'])->name('investimentos.update')->middleware('permissao:investimentos,editar');
    Route::delete('/investimentos/{investimento}', [InvestimentoController::class, 'destroy'])->name('investimentos.destroy')->middleware('permissao:investimentos,excluir');

    // Membros → redirecionado para familiares (tela unificada)
    Route::get('/membros', fn() => redirect()->route('familiares.index'))->name('membros.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
