<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'isAdmin',
        'isServidor',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

        /**
     * Verifica se o usuário tem a role especificada no time atual
     * consultando diretamente o banco de dados
     *
     * @param string $role
     * @return bool
     */
    public function hasRoleInCurrentTeam(string $role): bool
    {
        if (!$this->currentTeam) {
            return false;
        }
        
        // Consulta diretamente a tabela pivot para verificar a role
        $teamUser = DB::table('team_user')
            ->where('team_id', $this->currentTeam->id)
            ->where('user_id', $this->id)
            ->first();
        
        return $teamUser && $teamUser->role === $role;
    }

    /**
     * Atributo que indica se o usuário é admin no time atual
     *
     * @return bool
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRoleInCurrentTeam('admin') || 
            ($this->ownsTeam($this->currentTeam) && $this->currentTeam->name === 'Engenharia');
    }

    /**
     * Atributo que indica se o usuário é servidor no time atual
     *
     * @return bool
     */
    public function getIsServidorAttribute(): bool
    {
        return $this->hasRoleInCurrentTeam('servidor');
    }
}
