<?php

use App\Http\Controllers\AcessibilidadeUnidadeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InformacoesUnidadeController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\AvaliacaoUnidadeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Rotas protegidas por autenticação
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Rota do dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rotas das unidades (teams)
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    
    // Rotas para gerenciar unidades
    Route::post('/unidades', [UnidadeController::class, 'store'])->name('unidades.store');
    Route::put('/unidades/{unidade}', [UnidadeController::class, 'update'])->name('unidades.update');
    
    // Rotas para gerenciar acessibilidade
    Route::post('/acessibilidade', [AcessibilidadeUnidadeController::class, 'store'])->name('acessibilidade.store');
    Route::put('/acessibilidade/{acessibilidade}', [AcessibilidadeUnidadeController::class, 'update'])->name('acessibilidade.update');
    
    // Rotas para gerenciar informações estruturais
    Route::post('/informacoes-unidade', [InformacoesUnidadeController::class, 'store'])->name('informacoes-unidade.store');
    Route::put('/informacoes-unidade/{informacoesUnidade}', [InformacoesUnidadeController::class, 'update'])->name('informacoes-unidade.update');
    
    // Rotas apenas para administradores
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');
        
        // Rota para gerenciar todas as unidades
        Route::get('/unidades', function () {
            return Inertia::render('Admin/Unidades/Index');
        })->name('admin.unidades.index');
        
        // Rota para visualizar uma unidade específica
        Route::get('/unidades/{id}', function ($id) {
            return Inertia::render('Admin/Unidades/Show');
        })->name('admin.unidades.show');
        
         // Rotas para avaliação de unidades
        Route::post('/unidades/{unidade}/avaliar', [AvaliacaoUnidadeController::class, 'store'])
        ->name('admin.unidades.avaliar');

        Route::put('/avaliacoes/{avaliacao}', [AvaliacaoUnidadeController::class, 'update'])
        ->name('admin.avaliacoes.update');
    });
    
    // Rotas apenas para servidores
    Route::middleware(['role:servidor'])->prefix('servidor')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('servidor.dashboard');
    });
});