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
                // Verificar se as variáveis de ambiente da API estão configuradas
                $apiToken = env('API_TOKEN');
                $apiLoginUrl = env('API_LOGIN_URL');
                
                if (empty($apiToken) || empty($apiLoginUrl)) {
                    Log::warning('API não configurada, usando apenas autenticação local', [
                        'api_token_empty' => empty($apiToken),
                        'api_login_url_empty' => empty($apiLoginUrl)
                    ]);
                    
                    // Fallback para banco local
                    $user = User::where('matricula', $request->matricula)->first();
                    if ($user && Hash::check($request->password, $user->password)) {
                        return $user;
                    }
                    return null;
                }
                
                Log::info('Tentativa de autenticação via API', [
                    'matricula' => $request->matricula,
                    'api_url' => $apiLoginUrl,
                    'user_agent' => $request->header('User-Agent'),
                    'ip' => $request->ip()
                ]);
                
                // Tenta autenticar via API com configurações otimizadas
                $response = Http::timeout(20) // Timeout mais longo
                    ->connectTimeout(10) // Timeout de conexão
                    ->retry(2, 1000) // Retry 2 vezes com 1 segundo de intervalo
                    ->withToken($apiToken)
                    ->post($apiLoginUrl . '/api/servidor/login', [
                        'matricula' => $request->matricula,
                        'senha' => $request->password
                    ]);
                
                Log::info('Resposta da API recebida', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'size' => strlen($response->body())
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
                        'body' => $response->body(),
                        'api_url' => $apiLoginUrl,
                        'headers' => $response->headers()
                    ]);
                    
                    // Fallback para banco local
                    $user = User::where('matricula', $request->matricula)->first();
                    if ($user && Hash::check($request->password, $user->password)) {
                        Log::info('Autenticação local bem-sucedida para matrícula: ' . $request->matricula);
                        return $user;
                    }
                    return null;
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Erro de conexão com a API', [
                    'message' => $e->getMessage(),
                    'api_url' => $apiLoginUrl,
                    'curl_error' => $e->getCode()
                ]);
                
                // Fallback para banco local
                $user = User::where('matricula', $request->matricula)->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    Log::info('Fallback para autenticação local (erro de conexão) para matrícula: ' . $request->matricula);
                    return $user;
                }
                return null;
            } catch (\Exception $e) {
                Log::error('Erro na autenticação: ' . $e->getMessage(), [
                    'exception_class' => get_class($e),
                    'api_url' => $apiLoginUrl,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Fallback para banco local
                $user = User::where('matricula', $request->matricula)->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    Log::info('Fallback para autenticação local (exceção geral) para matrícula: ' . $request->matricula);
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
        
        Log::info('Lotação da API: ' . $lotacao);
        
        // Verificar se é Super Admin
        if ($user->matricula === '0000001') {
            // Lógica especial para Super Admin
            $this->setupSuperAdminTeam($user);
            return;
        }
        
        // Buscar ou criar team de forma inteligente
        $team = $this->findOrCreateTeam($lotacao, $user);
        
        if (!$team) {
            Log::error('Erro: Team não foi criado ou encontrado');
            return;
        }
        
        // Verificar se o usuário já está no team
        $teamUser = DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$teamUser) {
            // Adicionar o usuário ao team
            DB::table('team_user')->insert([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('Usuário adicionado ao team', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'user_id' => $user->id,
                'role' => 'admin'
            ]);
        } else if ($teamUser->role !== 'admin') {
            // Atualizar a role para admin
            DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->update(['role' => 'admin']);
            
            Log::info('Role do usuário atualizada para admin');
        }
        
        // Definir o team como atual do usuário
        $user->current_team_id = $team->id;
        $user->save();
        
        Log::info('Team atual do usuário definido', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'user_id' => $user->id
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erro ao configurar team e role: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'trace' => $e->getTraceAsString()
        ]);
    }
}

    /**
 * Função para normalizar strings (remover acentos)
 */
private function normalizeString($string)
{
    $string = trim($string);
    $string = mb_strtolower($string, 'UTF-8');
    
    $replacements = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'ª' => 'a', 'º' => 'o'
    ];
    
    return strtr($string, $replacements);
}

/**
 * Busca team de forma inteligente (com ou sem acentos)
 */
private function findOrCreateTeam($lotacao, $user)
{
    Log::info('Buscando team para lotação: ' . $lotacao);
    
    // Primeiro: busca exata
    $team = Team::where('name', $lotacao)->first();
    
    if ($team) {
        Log::info('Team encontrado com nome exato: ' . $lotacao);
        return $team;
    }
    
    // Segunda tentativa: busca por similaridade (sem acentos)
    $lotacaoNormalizada = $this->normalizeString($lotacao);
    
    $teams = Team::all();
    foreach ($teams as $candidateTeam) {
        $nomeNormalizado = $this->normalizeString($candidateTeam->name);
        if ($nomeNormalizado === $lotacaoNormalizada) {
            Log::info("Team encontrado com busca normalizada: {$candidateTeam->name} para lotação: {$lotacao}");
            return $candidateTeam;
        }
    }
    
    // Se não encontrou, criar novo com o nome original da API
    $superAdmin = User::where('matricula', '0000001')->first();
    
    if (!$superAdmin) {
        $superAdmin = $user;
        Log::warning('Super Admin não encontrado, usando usuário atual como dono do time');
    }
    
    $team = Team::create([
        'name' => $lotacao, // Nome original da API (com acentos)
        'user_id' => $superAdmin->id,
        'personal_team' => false,
    ]);
    
    Log::info("Team criado com nome da API: {$lotacao}, ID: {$team->id}");
    
    return $team;
}

private function setupSuperAdminTeam($user)
{
    try {
        // Verificar se o team DITI já existe
        $team = Team::where('name', 'DITI')->first();
        
        if (!$team) {
            $team = Team::create([
                'name' => 'DITI',
                'user_id' => $user->id,
                'personal_team' => true,
            ]);
            
            Log::info('Team DITI criado para Super Admin');
        }
        
        // Verificar se o Super Admin já está no team
        $teamUser = DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$teamUser) {
            DB::table('team_user')->insert([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'superadmin',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('Super Admin adicionado ao team DITI');
        }
        
        // Definir o team como atual
        $user->current_team_id = $team->id;
        $user->save();
        
        Log::info('Team DITI definido como atual para Super Admin');
        
    } catch (\Exception $e) {
        Log::error('Erro ao configurar Super Admin: ' . $e->getMessage());
    }
}

}