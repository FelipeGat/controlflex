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
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Despesas
    Route::get('/despesas', [DespesaController::class, 'index'])->name('despesas.index');
    Route::post('/despesas', [DespesaController::class, 'store'])->name('despesas.store');
    Route::put('/despesas/{despesa}', [DespesaController::class, 'update'])->name('despesas.update');
    Route::delete('/despesas/{despesa}', [DespesaController::class, 'destroy'])->name('despesas.destroy');

    // Receitas
    Route::get('/receitas', [ReceitaController::class, 'index'])->name('receitas.index');
    Route::post('/receitas', [ReceitaController::class, 'store'])->name('receitas.store');
    Route::put('/receitas/{receita}', [ReceitaController::class, 'update'])->name('receitas.update');
    Route::delete('/receitas/{receita}', [ReceitaController::class, 'destroy'])->name('receitas.destroy');

    // Categorias
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    // Familiares
    Route::get('/familiares', [FamiliarController::class, 'index'])->name('familiares.index');
    Route::post('/familiares', [FamiliarController::class, 'store'])->name('familiares.store');
    Route::put('/familiares/{familiar}', [FamiliarController::class, 'update'])->name('familiares.update');
    Route::delete('/familiares/{familiar}', [FamiliarController::class, 'destroy'])->name('familiares.destroy');

    // Fornecedores
    Route::get('/fornecedores', [FornecedorController::class, 'index'])->name('fornecedores.index');
    Route::post('/fornecedores', [FornecedorController::class, 'store'])->name('fornecedores.store');
    Route::put('/fornecedores/{fornecedor}', [FornecedorController::class, 'update'])->name('fornecedores.update');
    Route::delete('/fornecedores/{fornecedor}', [FornecedorController::class, 'destroy'])->name('fornecedores.destroy');

    // Bancos
    Route::get('/bancos', [BancoController::class, 'index'])->name('bancos.index');
    Route::post('/bancos', [BancoController::class, 'store'])->name('bancos.store');
    Route::put('/bancos/{banco}', [BancoController::class, 'update'])->name('bancos.update');
    Route::post('/bancos/{banco}/ajustar-saldo', [BancoController::class, 'ajustarSaldo'])->name('bancos.ajustar-saldo');
    Route::post('/bancos/{banco}/ajustar-saldo-cartao', [BancoController::class, 'ajustarSaldoCartao'])->name('bancos.ajustar-saldo-cartao');
    Route::delete('/bancos/{banco}', [BancoController::class, 'destroy'])->name('bancos.destroy');

    // Investimentos
    Route::get('/investimentos', [InvestimentoController::class, 'index'])->name('investimentos.index');
    Route::post('/investimentos', [InvestimentoController::class, 'store'])->name('investimentos.store');
    Route::put('/investimentos/{investimento}', [InvestimentoController::class, 'update'])->name('investimentos.update');
    Route::delete('/investimentos/{investimento}', [InvestimentoController::class, 'destroy'])->name('investimentos.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
