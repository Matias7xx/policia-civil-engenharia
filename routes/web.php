<?php

use App\Http\Controllers\AcessibilidadeUnidadeController;
use App\Http\Controllers\Admin\FormularioController;
use App\Http\Controllers\Admin\OrgaoController;
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
use Illuminate\Support\Facades\URL;

URL::forceScheme(env('HTTP_SCHEMA'));
URL::forceRootUrl(env('APP_URL'));

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        /* 'canRegister' => Route::has('register'), */
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Rotas públicas
Route::prefix('geocoding')->name('geocoding.')->group(function () {
    Route::get('/search', [GeocodingController::class, 'search'])->name('search');
    Route::get('/reverse', [GeocodingController::class, 'reverse'])->name('reverse');
});

if (app()->environment('local')) {
    Route::get('/error/{code}', function ($code) {
        abort((int)$code);
    })->name('error.test');
}

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

    // Rota para buscar mídias atualizadas (para o refresh automático)
    Route::get('/unidades/{unidade_id}/midias', [MidiaController::class, 'getMidias'])->name('unidades.midias');

    // API para buscar unidades QUE COMPARTILHAM O MESMO PRÉDIO
    Route::get('/api/unidades/ativas', [UnidadeController::class, 'getUnidadesAtivas'])
    ->name('api.unidades.ativas');
    
    // Rotas para visualização de mídias (MinIO)
    Route::get('/midias/{id}/view', [MidiaController::class, 'view'])->name('midias.view');
    Route::get('/midias/{id}/download', [MidiaController::class, 'download'])->name('midias.download');

    // Rotas de equipes (teams)
    Route::prefix('teams')->name('teams.')->group(function () {
        /* Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/{team}', [TeamController::class, 'show'])->name('show'); */
        
        Route::middleware('verify.no.unit')->group(function () {
            Route::get('/create', [TeamController::class, 'create'])->name('create');
        });
    });

    // Rotas de administração (admin e superadmin)
    Route::middleware('role:admin')->group(function () {
        // Gerenciamento de unidades
        Route::prefix('unidades')->name('unidades.')->group(function () {
            
            Route::get('/create/{teamId?}', [UnidadeController::class, 'create'])
            ->middleware('check.unidade.status:') //Só entra se o status for SEM CADASTRO
            ->name('create');

            Route::get('/{team}/{unidade}', [UnidadeController::class, 'show'])
            ->middleware('check.unidade.status:pendente_avaliacao,aprovada,reprovada,em_revisao')
            ->name('show');

            Route::get('/{team}/{unidade}/edit', [UnidadeController::class, 'edit'])
            ->middleware('check.unidade.status:pendente_avaliacao,reprovada,em_revisao')
            ->name('edit');

            Route::post('/dados-gerais', [UnidadeController::class, 'saveDadosGerais'])->name('saveDadosGerais');
            Route::post('/saveLocalizacao', [UnidadeController::class, 'saveLocalizacao'])->name('saveLocalizacao');
            Route::post('/acessibilidade', [UnidadeController::class, 'saveAcessibilidade'])->name('saveAcessibilidade');
            Route::post('/informacoes-estruturais', [UnidadeController::class, 'saveInformacoesEstruturais'])->name('saveInformacoesEstruturais');
            Route::post('/finalize/{unidade}', [UnidadeController::class, 'finalize'])->name('finalize');
        });

        // Gerenciamento de mídias
        Route::post('/midias/store', [MidiaController::class, 'store'])->name('midias.store');
        Route::post('/midias/update/{unidade_id}', [MidiaController::class, 'update'])->name('midias.update');
        Route::delete('/midias/{id}', [MidiaController::class, 'destroy'])->name('midias.destroy');
    });

    // Rotas de superadministrador
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {

        // Gerenciamento de unidades
        Route::prefix('unidades')->name('unidades.')->group(function () {
            Route::get('/', [AdminUnidadeController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminUnidadeController::class, 'show'])->name('show');
            Route::post('/{unidade}/avaliar', [AvaliacaoUnidadeController::class, 'store'])->name('avaliar');

            Route::post('/{id}/contrato', [AdminUnidadeController::class, 'updateContrato'])->name('updateContrato');
            Route::post('/{id}/cessao', [AdminUnidadeController::class, 'updateCessao'])->name('updateCessao');
            Route::get('/{id}/anexo', [AdminUnidadeController::class, 'anexo'])->name('anexo');
            Route::get('/{id}/termo-cessao', [AdminUnidadeController::class, 'termoCessao'])->name('termoCessao');
            Route::post('/{id}/status', [AdminUnidadeController::class, 'updateStatus'])->name('updateStatus');
            
            // Rotas para super admin preencher formulários das Unidades
            Route::get('/{teamId}/create', [UnidadeController::class, 'create'])->name('create');
            Route::get('/{team}/{unidade}/edit', [UnidadeController::class, 'edit'])->name('edit');
        });

        // Rotas de relatórios
            Route::prefix('formularios')->name('formularios.')->group(function () {
            Route::get('/relatorio', [FormularioController::class, 'gerarRelatorio'])->name('relatorio');
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

        // Gerenciamento de órgãos
        Route::prefix('orgaos')->name('orgaos.')->group(function () {
            Route::get('/', [OrgaoController::class, 'index'])->name('index');
            Route::get('/create', [OrgaoController::class, 'create'])->name('create');
            Route::post('/', [OrgaoController::class, 'store'])->name('store');
            Route::get('/{orgao}/edit', [OrgaoController::class, 'edit'])->name('edit');
            Route::put('/{orgao}', [OrgaoController::class, 'update'])->name('update');
            Route::get('/{orgao}', [OrgaoController::class, 'show'])->name('show');
            Route::delete('/{orgao}', [OrgaoController::class, 'destroy'])->name('destroy');
        });
    });

    // Rotas para servidores
    Route::middleware('role:servidor')->prefix('servidor')->name('servidor.')->group(function () {
        Route::get('/{team}/{unidade}', [UnidadeController::class, 'show'])
            ->middleware('check.unidade.status:pendente_avaliacao,aprovada,reprovada,em_revisao')
            ->name('show');
    });
});