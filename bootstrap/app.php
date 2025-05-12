<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'verify.no.unit' => \App\Http\Middleware\VerifyNoUnit::class,
            'check.unidade.status' => \App\Http\Middleware\CheckUnidadeStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            // Não personalizar erros de validação e CSRF
            if ($e instanceof ValidationException || $e instanceof TokenMismatchException) {
                return null; // Continuar com o handler padrão
            }
            
            // REMOVER a condição de ambiente para permitir testar em ambiente local!
            
            // Obter o código de status HTTP
            $status = 500; // Padrão para erro de servidor
            
            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
            }
            
            // Adicionar rota para a Request
            if ($request->route()) {
                $route = $request->route()->getName() ?? 'Desconhecida';
            } else {
                $route = 'Desconhecida';
            }
            
            // Tratamento para erros comuns
            if (in_array($status, [403, 404, 500, 503]) && 
                file_exists(resource_path('js/Pages/Errors/' . $status . '.vue'))) {
                return Inertia::render('Errors/' . $status, [
                    'message' => $e instanceof HttpException ? $e->getMessage() : $e->getMessage(),
                    'status' => $status
                ])->toResponse($request)
                  ->setStatusCode($status);
            } 
            elseif ($status >= 400) {
                // Para outros códigos de erro, use a página genérica
                return Inertia::render('Errors/Error', [
                    'message' => $e instanceof HttpException ? $e->getMessage() : $e->getMessage(),
                    'status' => $status
                ])->toResponse($request)
                  ->setStatusCode($status);
            }
            elseif ($status === 419) {
                return back()->with([
                    'flash.banner' => 'A página expirou devido à inatividade. Por favor, tente novamente.',
                    'flash.bannerStyle' => 'danger',
                ]);
            }
            
            return null; // Continuar com o handler padrão
        });
    })->create();
