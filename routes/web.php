<?php

use App\Http\Controllers\AcessibilidadeUnidadeController;
use App\Http\Controllers\Admin\UnidadeController as AdminUnidadeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AvaliacaoUnidadeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\InformacoesUnidadeController;
use App\Http\Controllers\MidiaController;
use App\Http\Controllers\MidiaTipoController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UnidadeController;
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
})->name('home');

// Rotas públicas
Route::prefix('geocoding')->name('geocoding.')->group(function () {
    Route::get('/search', [GeocodingController::class, 'search'])->name('search');
    Route::get('/reverse', [GeocodingController::class, 'reverse'])->name('reverse');
});

// Rota de depuração
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
})->name('debug.user-role');

// Rotas autenticadas
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rotas de mídias
    Route::get('/midia-tipos/ativos', [MidiaTipoController::class, 'ativos'])->name('api.midia-tipos.ativos');

    // Rotas de equipes (teams)
    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/{team}', [TeamController::class, 'show'])->name('show');
        
        Route::middleware('verify.no.unit')->group(function () {
            Route::get('/create', [TeamController::class, 'create'])->name('create');
        });
    });

    // Rotas de administração (admin e superadmin)
    Route::middleware('role:admin')->group(function () {
        // Gerenciamento de unidades
        Route::prefix('unidades')->name('unidades.')->group(function () {
            Route::get('/create/{teamId?}', [UnidadeController::class, 'create'])->name('create');
            Route::get('/{team}/{unidade}', [UnidadeController::class, 'show'])->name('show');
            Route::post('/dados-gerais', [UnidadeController::class, 'saveDadosGerais'])->name('saveDadosGerais');
            Route::post('/acessibilidade', [UnidadeController::class, 'saveAcessibilidade'])->name('saveAcessibilidade');
            Route::post('/informacoes-estruturais', [UnidadeController::class, 'saveInformacoesEstruturais'])->name('saveInformacoesEstruturais');
            Route::post('/finalize/{unidade}', [UnidadeController::class, 'finalize'])->name('finalize');
        });

        // Gerenciamento de mídias
        Route::post('/midias/store', [MidiaController::class, 'store'])->name('midias.store');
    });

    // Rotas de superadministrador
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', fn() => Inertia::render('Admin/Dashboard'))->name('dashboard');

        // Gerenciamento de unidades
        Route::prefix('unidades')->name('unidades.')->group(function () {
            Route::get('/', [AdminUnidadeController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminUnidadeController::class, 'show'])->name('show');
            Route::post('/{unidade}/avaliar', [AvaliacaoUnidadeController::class, 'store'])->name('avaliar');
        });

        // Gerenciamento de avaliações
        Route::put('/avaliacoes/{avaliacao}', [AvaliacaoUnidadeController::class, 'update'])->name('avaliacoes.update');

        // Gerenciamento de usuários
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });

    // Rotas para servidores
    Route::middleware('role:servidor')->prefix('servidor')->name('servidor.')->group(function () {
        Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    });
});