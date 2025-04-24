<?php

namespace App\Http\Middleware;

use App\Models\Unidade;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNoUnit
{
    /**
     * Handle an incoming request.
     * Middleware que verifica se a Unidade já está cadastrada
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->currentTeam) {
            $unidade = Unidade::where('team_id', $user->currentTeam->id)->first();
            
            if ($unidade) {
                // Se já existe uma unidade, redireciona para o dashboard
                return redirect()->route('dashboard')->with('flash.banner', 'Você já possui uma unidade cadastrada.');
            }
        }
        
        return $next($request);
    }
}