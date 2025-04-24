<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\RoleHelper;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        
        if (!$user || !$user->currentTeam) {
            abort(403, 'Acesso não autorizado.');
        }
        
        if ($role === 'admin' && !RoleHelper::isAdmin($user)) {
            abort(403, 'Acesso não autorizado. Apenas administradores podem acessar esta página.');
        }
        
        if ($role === 'servidor' && !RoleHelper::isServidor($user)) {
            abort(403, 'Acesso não autorizado. Apenas servidores podem acessar esta página.');
        }

        return $next($request);
    }
}