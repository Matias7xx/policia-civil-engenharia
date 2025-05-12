<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
{
    // Check if user is SuperAdmin
    if (!Auth::user()->isSuperAdmin) {
        abort(403, 'Unauthorized action.');
    }

    // Iniciar a consulta base
    $usersQuery = User::with('ownedTeams');
    
    // Aplicar filtros se fornecidos na requisição
    if ($request->has('search') && $request->search) {
        $searchTerms = array_filter(explode(' ', trim($request->search))); // Divide a string em termos
        $usersQuery->where(function($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->where(function($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($term) . '%'])
                             ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($term) . '%'])
                             ->orWhereRaw('LOWER(matricula) LIKE ?', ['%' . strtolower($term) . '%'])
                             ->orWhereHas('currentTeam', function($teamQuery) use ($term) {
                                 $teamQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($term) . '%']);
                             });
                });
            }
        });
    }

    if ($request->has('role') && $request->role !== 'todos') {
        $role = $request->role;
        $usersQuery->whereHas('teams', function($query) use ($role) {
            $query->whereRaw('team_user.role = ?', [$role]);
        });
    }
    
    // Paginar resultados (10 por página)
    $users = $usersQuery->orderBy('name')
        ->paginate(10)
        ->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                /* 'email' => $user->email, */
                'matricula' => $user->matricula,
                'created_at' => $user->created_at->format('d/m/Y'),
                'team' => $user->currentTeam ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                ] : null,
                'role' => $this->getUserRole($user),
            ];
        });

    // Preservar os parâmetros de consulta na paginação
    $users->appends($request->all());

    // Obter times para o dropdown
    $teams = Team::all()->map(function ($team) {
        return [
            'id' => $team->id,
            'name' => $team->name,
        ];
    });

    return Inertia::render('Admin/Users/Index', [
        'users' => $users,
        'teams' => $teams,
        'roles' => [
            ['key' => 'superadmin', 'name' => 'Super Administrador'],
            ['key' => 'admin', 'name' => 'Administrador'],
            ['key' => 'servidor', 'name' => 'Servidor'],
        ],
        'filters' => [
            'search' => $request->search ?? '',
            'role' => $request->role ?? 'todos',
        ]
    ]);
}

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Check if user is SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Unauthorized action.');
        }

        $teams = Team::all()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
            ];
        });

        return Inertia::render('Admin/Users/Create', [
            'teams' => $teams,
            'roles' => [
                ['key' => 'superadmin', 'name' => 'Super Administrador'],
                ['key' => 'admin', 'name' => 'Administrador'],
                ['key' => 'servidor', 'name' => 'Servidor'],
            ]
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request)
    {
        // Check if user is SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Unauthorized action.');
        }

        // Get validated data
        $validatedData = $request->validated();

        // Create user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'matricula' => $validatedData['matricula'],
            'cargo' => $validatedData['cargo'] ?? null,
            'telefone' => $validatedData['telefone'] ?? null,
        ]);

        // Find the team
        $team = Team::findOrFail($validatedData['team_id']);

        // Associate the user with the team and set the role
        $user->teams()->syncWithoutDetaching([$team->id => ['role' => $validatedData['role']]]);

        // Set the current team
        $user->current_team_id = $team->id;
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('flash.banner', 'Usuário criado com sucesso.');
    }

    /**
     * Mostra o formulário para editar o usuário especificado.
     */
    public function edit(User $user)
    {
        // Verificar se o usuário é SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $teams = Team::all()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
            ];
        });

        // Obter a função atual do usuário em seu time atual
        $userRole = null;
        if ($user->currentTeam) {
            $teamUser = \DB::table('team_user')
                ->where('team_id', $user->currentTeam->id)
                ->where('user_id', $user->id)
                ->first();
            $userRole = $teamUser ? $teamUser->role : null;
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'matricula' => $user->matricula,
                'cargo' => $user->cargo,
                'telefone' => $user->telefone,
                'current_team_id' => $user->current_team_id,
                'role' => $userRole,
            ],
            'teams' => $teams,
            'roles' => [
                ['key' => 'superadmin', 'name' => 'Super Administrador'],
                ['key' => 'admin', 'name' => 'Administrador'],
                ['key' => 'servidor', 'name' => 'Servidor'],
            ]
        ]);
    }

    /**
     * Atualiza o usuário especificado no banco de dados.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Verificar se o usuário é SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        // Obter dados validados
        $validatedData = $request->validated();

        // Atualizar usuário
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->matricula = $validatedData['matricula'];
        $user->cargo = $validatedData['cargo'] ?? null;
        $user->telefone = $validatedData['telefone'] ?? null;

        // Atualizar senha apenas se fornecida
        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        // Encontrar o time
        $team = Team::findOrFail($validatedData['team_id']);

        // Verificar se é necessário atualizar o time atual do usuário
        if ($user->current_team_id != $team->id) {
            $user->current_team_id = $team->id;
        }

        $user->save();

        // Atualizar a função do usuário no time
        $user->teams()->syncWithoutDetaching([$team->id => ['role' => $validatedData['role']]]);

        return redirect()->route('admin.users.index')
            ->with('flash.banner', 'Usuário atualizado com sucesso.');
    }

    /**
 * Display the specified user.
 */
    public function show(User $user)
    {
        // Verificar se o usuário é SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        // Obter a função atual do usuário em seu time atual
        $userRole = $this->getUserRole($user);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'matricula' => $user->matricula,
                'cargo' => $user->cargo,
                'telefone' => $user->telefone,
                'created_at' => $user->created_at->format('d/m/Y'),
                'team' => $user->currentTeam ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                ] : null,
                'role' => $userRole,
            ],
        ]);
    }

    /**
     * Remove o usuário especificado do banco de dados.
     */
    public function destroy(User $user)
    {
        // Verificar se o usuário é SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        // Impedir exclusão do próprio usuário
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('flash.banner', 'Você não pode excluir seu próprio usuário.')
                ->with('flash.bannerStyle', 'danger');
        }

        // Remover associações de times
        $user->teams()->detach();
        
        // Excluir usuário
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('flash.banner', 'Usuário excluído com sucesso.');
    }

    /**
     * Obtém a função do usuário em seu time atual
     */
    private function getUserRole($user)
    {
        if (!$user->currentTeam) {
            return null;
        }

        $teamUser = \DB::table('team_user')
            ->where('team_id', $user->currentTeam->id)
            ->where('user_id', $user->id)
            ->first();

        return $teamUser ? $teamUser->role : null;
    }
}