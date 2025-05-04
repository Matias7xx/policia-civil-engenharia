<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Unidade;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $unidadeId = null;

        // Verificar se há um usuário autenticado e um time atual
        if ($user && $user->currentTeam) {
            // Buscar a unidade associada ao time atual do usuário
            $unidade = Unidade::where('team_id', $user->currentTeam->id)->first();
            $unidadeId = $unidade ? $unidade->id : null;
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'unidade_id' => $unidadeId, // Compartilhar o unidade_id
            ],
        ]);
    }
}