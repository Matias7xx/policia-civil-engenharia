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
use Illuminate\Auth\AuthenticationException;

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

        // TRUSTPROXIES - at com IP do container
        //Inserir header na config do NGINX >  proxy_set_header X-Forwarded-Prefix /censo-imoveis;
        $middleware->trustProxies(
            at: ['*'],
            headers: Request::HEADER_X_FORWARDED_FOR |
                    Request::HEADER_X_FORWARDED_HOST |
                    Request::HEADER_X_FORWARDED_PORT |
                    Request::HEADER_X_FORWARDED_PROTO |
                    Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'verify.no.unit' => \App\Http\Middleware\VerifyNoUnit::class,
            'check.unidade.status' => \App\Http\Middleware\CheckUnidadeStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        return redirect()->guest('/');
    });
    
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException || $e instanceof TokenMismatchException) {
                return null;
            }

            $status = 500;

            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
            }

            if ($request->route()) {
                $route = $request->route()->getName() ?? 'Desconhecida';
            } else {
                $route = 'Desconhecida';
            }

            if (in_array($status, [403, 404, 500, 503]) &&
                file_exists(resource_path('js/Pages/Errors/' . $status . '.vue'))) {
                return Inertia::render('Errors/' . $status, [
                    'message' => $e instanceof HttpException ? $e->getMessage() : $e->getMessage(),
                    'status' => $status
                ])->toResponse($request)
                  ->setStatusCode($status);
            }
            elseif ($status >= 400) {
                return Inertia::render('Errors/Error', [
                    'message' => $e instanceof HttpException ?
                                ($e->getMessage() ?: 'Erro interno do servidor') :
                                'Erro interno do servidor',
                    'status' => $status,
                    'route' => $route
                ])->toResponse($request)
                  ->setStatusCode($status);
            }
            elseif ($status === 419) {
                return back()->with([
                    'flash.banner' => 'A página expirou devido à inatividade. Por favor, tente novamente.',
                    'flash.bannerStyle' => 'danger',
                ]);
            }

            return null;
        });
    })->create();
