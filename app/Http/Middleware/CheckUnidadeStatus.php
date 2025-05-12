<?php

namespace App\Http\Middleware;

use App\Models\Unidade;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUnidadeStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$statuses): Response
    {
        // Verifica a unidade do time atual
        $routeName = $request->route()->getName();
        
        if ($routeName === 'unidades.create') {
            // Verificar se o usuário já tem uma unidade associada ao time atual
            $user = Auth::user();
            if (!$user || !$user->currentTeam) {
                return $next($request);
            }
            
            $unidade = Unidade::where('team_id', $user->currentTeam->id)->first();
            
            // Se não há unidade, permitir o acesso à página de criação
            if (!$unidade) {
                return $next($request);
            }
            
            // Se há unidade, verifica se seu status está na lista de permitidos
            if (!in_array($unidade->status, $statuses)) {
                return redirect()->route('dashboard')
                    ->with('flash.banner', 'Você já possui uma unidade cadastrada com status: ' . $unidade->status_formatado)
                    ->with('flash.bannerStyle', 'danger');
            }
            
            return $next($request);
        }
        
        // Para outras rotas, verificar a unidade da URL
        $unidadeId = $request->route('unidade');
        
        if (!$unidadeId) {
            $unidadeId = $request->route('id');
        }
        
        if (!$unidadeId) {
            return $next($request);
        }
        
        $unidade = Unidade::find($unidadeId);
        
        if (!$unidade) {
            return $next($request);
        }
        
        if (!in_array($unidade->status, $statuses)) {
            return redirect()->route('dashboard')
                ->with('flash.banner', 'Acesso não permitido para unidades com status: ' . $unidade->status_formatado)
                ->with('flash.bannerStyle', 'danger');
        }
        
        return $next($request);
    }
}