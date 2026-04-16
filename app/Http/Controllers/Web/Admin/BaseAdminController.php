<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\AccessControlService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

abstract class BaseAdminController extends Controller
{
    public function __construct(
        protected readonly AccessControlService $access
    ) {
    }

    /**
     * Execute une action d administration de facon standardisee.
     */
    protected function executeAdminAction(
        Request $request,
        callable $action,
        string $permission = 'admin',
        ?string $logActionType = null,
        ?string $logComment = null,
        ?string $redirectTo = null
    ): RedirectResponse {
        $redirectTarget = $redirectTo ?? ($permission === 'users' ? '/admin/utilisateurs' : null);

        try {
            $actor = $this->access->requireActor($request);
            $userId = (int) $actor->id_utilisateur;

            match ($permission) {
                'users' => $this->assertCanManageUsers($actor),
                'params' => $this->assertCanManageParams($actor),
                default => $this->assertCanManageAdmin($actor),
            };

            $action();

            if ($logActionType && $logComment) {
                $this->recordAdminAudit($userId, $logActionType, $logComment);
            }

            return $this->redirectAfterAction($redirectTarget)
                ->with('success', 'Operation effectuee avec succes.');
        } catch (AuthorizationException $e) {
            return $this->redirectAfterAction($redirectTarget)->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->redirectAfterAction($redirectTarget)->with('error', $e->getMessage());
        }
    }

    protected function redirectAfterAction(?string $redirectTo = null): RedirectResponse
    {
        if ($redirectTo) {
            return redirect()->to($redirectTo);
        }

        return redirect()->back();
    }

    protected function recordAdminAudit(int $userId, string $actionType, string $comment, ?int $demandId = null): void
    {
        $normalizedType = str_starts_with($actionType, 'ADMIN_') ? $actionType : 'ADMIN_'.$actionType;

        DB::table('historique_actions')->insert([
            'id_demande' => $demandId,
            'id_utilisateur' => $userId,
            'type_action' => $normalizedType,
            'commentaire' => $comment,
            'date_action' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function assertCanManageAdmin(Utilisateur $actor): void
    {
        if (Gate::forUser($actor)->denies('admin.access')) {
            throw new AuthorizationException('Acces admin refuse.');
        }
    }

    protected function assertCanManageUsers(Utilisateur $actor): void
    {
        if (Gate::forUser($actor)->denies('admin.users.manage')) {
            throw new AuthorizationException('Acces refuse : gestion des utilisateurs requise.');
        }
    }

    protected function assertCanManageParams(Utilisateur $actor): void
    {
        if (Gate::forUser($actor)->denies('admin.parameters.manage')) {
            throw new AuthorizationException('Acces refuse : gestion des parametres requise.');
        }
    }
}
