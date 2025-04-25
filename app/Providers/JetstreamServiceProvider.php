<?php

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
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
        $this->configurePermissions();

        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
        
        // Personalizar os textos relacionados a Teams
        /* Jetstream::useTeamsTerms('Unidade Policial', 'Unidades Policiais'); */
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::role('superadmin', 'Super Administrador', [
            'create',
            'read',
            'update',
            'delete',
            'view-all-units',
            'evaluate-units',
            'manage-all-units'
        ])->description('Super Administradores podem realizar qualquer ação, gerenciar e avaliar todas as unidades.');

        Jetstream::role('admin', 'Administrador', [
            'read',
            'create',
            'update',
            'manage-own-unit'
        ])->description('Administradores podem ler, criar e atualizar informações apenas da sua própria unidade.');

        Jetstream::role('servidor', 'Servidor', [
            'read',
            'view-own-unit'
        ])->description('Servidores podem apenas visualizar informações da sua própria unidade.');
    }
}