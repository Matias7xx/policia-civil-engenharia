<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(function(){
            return view('auth.login');
        });
        
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $matricula = (string) $request->matricula;
            return Limit::perMinute(5)->by($matricula.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::authenticateUsing(function(Request $request){
            try {
                // Tenta autenticar via API
                $response = Http::withToken(env('API_TOKEN'))
                    ->post(env('API_LOGIN_URL').'/api/servidor/login', [
                        'matricula' => $request->matricula,
                        'senha' => $request->password
                    ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Log do retorno da API
                    Log::debug('Dados recebidos da API:', ['userData' => $data]);
                    
                    // Verificar se o usuário já existe
                    $user = User::where('matricula', $data['matricula'])->first();
                    
                    if (!$user) {
                        // Criar novo usuário simples
                        $user = $this->createSimpleUser($data, $request->password);
                        Log::info('Novo usuário criado: ' . $user->id);
                    } else {
                        // Atualizar apenas dados essenciais
                        $user->name = $data['nome'];
                        $user->save();
                        Log::info('Usuário existente atualizado: ' . $user->id);
                    }
                    
                    // Configurar time e role explicitamente
                    $this->setupTeamAndRole($user, $data);
                    
                    return $user;
                } else {
                    // Log da falha
                    Log::warning('Falha na autenticação com API', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    
                    // Fallback para banco local
                    $user = User::where('matricula', $request->matricula)->first();
                    if ($user && Hash::check($request->password, $user->password)) {
                        return $user;
                    }
                    return null;
                }
            } catch (\Exception $e) {
                Log::error('Erro na autenticação: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Fallback para banco local
                $user = User::where('matricula', $request->matricula)->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    return $user;
                }
                return null;
            }
        });
    }
    
    /**
     * Cria um usuário básico
     */
    private function createSimpleUser($data, $password)
    {
        try {
            $user = new User();
            $user->name = $data['nome'];
            $user->email = $data['email'] ?: $data['matricula'].'@pc.pb.gov.br';
            $user->matricula = $data['matricula'];
            $user->password = Hash::make($password);
            $user->cargo = $data['cargo'] ?? null;
            $user->save();
            
            
            Log::info('Usuário criado com sucesso', [
                'id' => $user->id,
                'matricula' => $user->matricula
            ]);
            
            return $user;
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Configura explicitamente o time e a role do usuário
     */
    private function setupTeamAndRole($user, $data)
    {
        try {
            // Determinar lotação
            $lotacao = "Sem Lotação";
            
            if (isset($data['lotacao_principal']) && 
                is_array($data['lotacao_principal']) && 
                !empty($data['lotacao_principal']['unidade_lotacao'])) {
                $lotacao = trim($data['lotacao_principal']['unidade_lotacao']);
            } elseif (!empty($data['unidade_lotacao'])) {
                $lotacao = trim($data['unidade_lotacao']);
            }
            
            // Normalizar o nome da lotação
            $lotacao = ucwords(mb_strtolower($lotacao));
            
            Log::info('Lotação determinada: ' . $lotacao);
            
            // Verificar se é o Super Admin
            $isSuperAdmin = $user->matricula === '0000001';
            
            // Criar team para o Super Admin
            if ($isSuperAdmin) {
                // Verificar se o team DITI já existe
                $team = Team::where('name', 'DITI')->first();
                
                if (!$team) {
                    // Criar o team DITI
                    $team = new Team();
                    $team->name = 'DITI';
                    $team->user_id = $user->id;
                    $team->personal_team = true;
                    $team->save();
                    
                    Log::info('Team DITI criado para Super Admin', [
                        'team_id' => $team->id
                    ]);
                } else {
                    // Garantir que o Super Admin é o dono
                    $team->user_id = $user->id;
                    $team->save();
                    
                    Log::info('Team DITI existente atualizado', [
                        'team_id' => $team->id
                    ]);
                }
                
                // Verificar se o Super Admin já está no team
                $teamUser = DB::table('team_user')
                    ->where('team_id', $team->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$teamUser) {
                    // Adicionar o Super Admin ao team com role superadmin
                    DB::table('team_user')->insert([
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'superadmin',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info('Super Admin adicionado ao team DITI', [
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'superadmin'
                    ]);
                } else if ($teamUser->role !== 'superadmin') {
                    // Atualizar a role para superadmin
                    DB::table('team_user')
                        ->where('team_id', $team->id)
                        ->where('user_id', $user->id)
                        ->update(['role' => 'superadmin']);
                    
                    Log::info('Role do Super Admin atualizada', [
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'superadmin'
                    ]);
                }
                
                // Definir o team como atual do Super Admin
                if ($user->current_team_id !== $team->id) {
                    $user->current_team_id = $team->id;
                    $user->save();
                    
                    Log::info('Team atual do Super Admin atualizado', [
                        'team_id' => $team->id,
                        'user_id' => $user->id
                    ]);
                }
            } else {
                // Para usuários normais
                
                // Encontrar o Super Admin
                $superAdmin = User::where('matricula', '0000001')->first();
                
                if (!$superAdmin) {
                    // Usar o usuário atual como dono do time
                    $superAdmin = $user;
                    Log::warning('Super Admin não encontrado, usando usuário atual como dono do time', [
                        'user_id' => $user->id
                    ]);
                }
                
                // Verificar se o team da lotação já existe
                $team = Team::where('name', $lotacao)->first();
                
                if (!$team) {
                    // Criar o team
                    $team = new Team();
                    $team->name = $lotacao;
                    $team->user_id = $superAdmin->id;
                    $team->personal_team = false;
                    $team->save();
                    
                    Log::info('Team criado para lotação', [
                        'team_id' => $team->id,
                        'lotacao' => $lotacao,
                        'owner_id' => $superAdmin->id
                    ]);
                } else if ($team->user_id !== $superAdmin->id) {
                    // Garantir que o Super Admin é o dono
                    $team->user_id = $superAdmin->id;
                    $team->save();
                    
                    Log::info('Proprietário do team atualizado', [
                        'team_id' => $team->id,
                        'owner_id' => $superAdmin->id
                    ]);
                }
                
                // Verificar se o usuário já está no team
                $teamUser = DB::table('team_user')
                    ->where('team_id', $team->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$teamUser) {
                    // Adicionar o usuário ao team com role admin
                    DB::table('team_user')->insert([
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'admin',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info('Usuário adicionado ao team', [
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'admin'
                    ]);
                } else if ($teamUser->role !== 'admin') {
                    // Atualizar a role para admin
                    DB::table('team_user')
                        ->where('team_id', $team->id)
                        ->where('user_id', $user->id)
                        ->update(['role' => 'admin']);
                    
                    Log::info('Role do usuário atualizada', [
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'admin'
                    ]);
                }
                
                // Definir o team como atual do usuário
                if (!$user->current_team_id) {
                    $user->current_team_id = $team->id;
                    $user->save();
                    
                    Log::info('Team atual do usuário definido', [
                        'team_id' => $team->id,
                        'user_id' => $user->id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao configurar team e role: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}