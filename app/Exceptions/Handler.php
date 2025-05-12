<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler
{
    /**
     * Render an exception to a response.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public static function render(Throwable $e, Request $request)
    {
        // Não personalizar erros de validação e CSRF
        if ($e instanceof ValidationException || $e instanceof TokenMismatchException) {
            return null; // Continuar com o handler padrão
        }
        
        if (!app()->environment(['local', 'testing'])) {
            // Obter o código de status HTTP
            $status = 500; // Padrão para erro de servidor
            
            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
            }
            
            // Tratamento para erros comuns
            if (in_array($status, [403, 404, 500, 503]) && 
                file_exists(resource_path('js/Pages/Errors/' . $status . '.vue'))) {
                return Inertia::render('Errors/' . $status, [
                    'message' => $e instanceof HttpException ? $e->getMessage() : null,
                    'status' => $status
                ])->toResponse($request)
                  ->setStatusCode($status);
            } 
            elseif ($status >= 400) {
                // Para outros códigos de erro, use a página genérica
                return Inertia::render('Errors/Error', [
                    'message' => $e instanceof HttpException ? $e->getMessage() : null,
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
        }
        
        return null; // Continuar com o handler padrão
    }
}