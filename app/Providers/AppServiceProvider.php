<?php

namespace App\Providers;

use App\Models\Demande;
use App\Models\Utilisateur;
use App\Policies\DemandePolicy;
use App\Services\AccessControlService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
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
        Gate::policy(Demande::class, DemandePolicy::class);

        $access = app(AccessControlService::class);

        foreach ([
            'dashboard.view',
            'dashboard.export',
            'demande.assign',
            'demande.assign.agent',
            'demande.reply.send',
            'demande.reply.draft',
            'demande.view.all',
            'admin.users.manage',
            'admin.parameters.manage',
        ] as $permissionCode) {
            Gate::define($permissionCode, function (Utilisateur $user) use ($access, $permissionCode): bool {
                return $access->hasPermission((int) $user->id_utilisateur, $permissionCode);
            });
        }

        Gate::define('admin.access', function (Utilisateur $user) use ($access): bool {
            $userId = (int) $user->id_utilisateur;

            return $access->hasPermission($userId, 'admin.users.manage')
                || $access->hasPermission($userId, 'admin.parameters.manage');
        });
    }
}
