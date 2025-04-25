<?php

namespace App\Helpers;

class RoleHelper
{
    /**
     * Verifica se o usuário tem o papel de superadministrador
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public static function isSuperAdmin($user)
    {
        if (!$user || !$user->currentTeam) {
            return false;
        }
        
        return $user->isSuperAdmin;
    }

    /**
     * Verifica se o usuário tem o papel de administrador
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public static function isAdmin($user)
    {
        if (!$user || !$user->currentTeam) {
            return false;
        }
        
        return $user->isAdmin || $user->isSuperAdmin;
    }

    /**
     * Verifica se o usuário tem o papel de servidor
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public static function isServidor($user)
    {
        if (!$user || !$user->currentTeam) {
            return false;
        }
        
        return $user->isServidor;
    }

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