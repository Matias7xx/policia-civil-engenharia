<?php

namespace App\Helpers;

class RoleHelper
{
    /**
     * Verifica se o usuário tem APENAS a role de administrador
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public static function isAdmin($user)
{
    if (!$user || !$user->currentTeam) {
        return false;
    }
    
    return $user->isAdmin && !$user->isServidor;
}

    /* Para verificar se o usuário é ADMIN no frontend
    <div v-if="$page.props.auth.user.isAdmin">
        <!-- Conteúdo apenas para administradores -->
    </div> */

public static function isServidor($user)
{
    if (!$user || !$user->currentTeam) {
        return false;
    }
    
    return $user->isServidor && !$user->isAdmin;
}

    /* Para verificar se o usuário é SERVIDOR no frontend
    <div v-if="$page.props.auth.user.isServidor">
        <!-- Conteúdo apenas para administradores -->
    </div> */
    
    /**
     * Verifica se o usuário tem qualquer uma das roles especificadas
     *
     * @param \App\Models\User $user
     * @param array $roles
     * @return bool
     */
    public static function hasAnyRole($user, array $roles)
    {
        if (!$user || !$user->currentTeam) {
            return false;
        }
        
        foreach ($roles as $role) {
            if ($user->hasTeamRole($user->currentTeam, $role)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário tem todas as roles especificadas
     *
     * @param \App\Models\User $user
     * @param array $roles
     * @return bool
     */
    public static function hasAllRoles($user, array $roles)
    {
        if (!$user || !$user->currentTeam) {
            return false;
        }
        
        foreach ($roles as $role) {
            if (!$user->hasTeamRole($user->currentTeam, $role)) {
                return false;
            }
        }
        
        return true;
    }
}