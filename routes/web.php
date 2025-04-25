<?php

use App\Http\Controllers\AcessibilidadeUnidadeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InformacoesUnidadeController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\AvaliacaoUnidadeController;
use App\Http\Controllers\Admin\UnidadeController as AdminUnidadeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SuperAdminController;

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/debug/user-role', function () {
    $user = auth()->user();
    return [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'team' => [
            'id' => $user->currentTeam->id,
            'name' => $user->currentTeam->name,
        ],
        'isSuperAdmin' => \App\Helpers\RoleHelper::isSuperAdmin($user),
        'isAdmin' => \App\Helpers\RoleHelper::isAdmin($user),
        'isServidor' => \App\Helpers\RoleHelper::isServidor($user),
        'teamRole' => $user->teamRole($user->currentTeam) ? $user->teamRole($user->currentTeam)->key : null,
    ];
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
    
    // Middleware para verificar se já existe unidade
    Route::middleware(['verify.no.unit'])->group(function () {
        Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    });

    // Rotas para visualizar unidades (todos os níveis)
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    
    // Rotas que requerem permissão de admin ou superadmin
    Route::middleware(['role:admin'])->group(function () {
        // Rotas para gerenciar unidades
        Route::post('/unidades', [UnidadeController::class, 'store'])->name('unidades.store');
        Route::put('/unidades/{unidade}', [UnidadeController::class, 'update'])->name('unidades.update');
        
        // Rotas para gerenciar acessibilidade
        Route::post('/acessibilidade', [AcessibilidadeUnidadeController::class, 'store'])->name('acessibilidade.store');
        Route::put('/acessibilidade/{acessibilidade}', [AcessibilidadeUnidadeController::class, 'update'])->name('acessibilidade.update');
        
        // Rotas para gerenciar informações estruturais
        Route::post('/informacoes-unidade', [InformacoesUnidadeController::class, 'store'])->name('informacoes-unidade.store');
        Route::put('/informacoes-unidade/{informacoesUnidade}', [InformacoesUnidadeController::class, 'update'])->name('informacoes-unidade.update');
    });
    
    // Rotas apenas para superadministradores
    Route::middleware(['role:superadmin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');
        
        // Rota para gerenciar todas as unidades
        Route::get('/unidades', [AdminUnidadeController::class, 'index'])->name('admin.unidades.index');
        
        // Rota para visualizar uma unidade específica
        Route::get('/unidades/{id}', [AdminUnidadeController::class, 'show'])->name('admin.unidades.show');
        
        // Rotas para avaliação de unidades
        Route::post('/unidades/{unidade}/avaliar', [AvaliacaoUnidadeController::class, 'store'])
            ->name('admin.unidades.avaliar');

        Route::put('/avaliacoes/{avaliacao}', [AvaliacaoUnidadeController::class, 'update'])
            ->name('admin.avaliacoes.update');
    });
    
    // Rotas apenas para servidores comuns (visualização)
    Route::middleware(['role:servidor'])->prefix('servidor')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('servidor.dashboard');
    });
});