<?php

namespace App\Policies;

use App\Models\Demande;
use App\Models\Utilisateur;
use App\Services\AccessControlService;
use Illuminate\Auth\Access\Response;

class DemandePolicy
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function view(Utilisateur $user, Demande $demande): Response
    {
        return $this->access->canAccessDemand((int) $user->id_utilisateur, (int) $demande->id_demande)
            ? Response::allow()
            : Response::deny('Acces refuse sur cette demande.');
    }

    public function assignService(Utilisateur $user, Demande $demande): Response
    {
        return $this->access->hasPermission((int) $user->id_utilisateur, 'demande.assign')
            ? Response::allow()
            : Response::deny('Permission requise: demande.assign');
    }

    public function cancelServiceAssignment(Utilisateur $user, Demande $demande): Response
    {
        return $this->assignService($user, $demande);
    }

    public function assignAgent(Utilisateur $user, Demande $demande): Response
    {
        if (!$this->access->hasPermission((int) $user->id_utilisateur, 'demande.assign.agent')) {
            return Response::deny('Permission requise: demande.assign.agent');
        }

        return $this->view($user, $demande);
    }

    public function cancelAgentAssignment(Utilisateur $user, Demande $demande): Response
    {
        return $this->assignAgent($user, $demande);
    }

    public function draftReply(Utilisateur $user, Demande $demande): Response
    {
        $userId = (int) $user->id_utilisateur;

        if (
            !$this->access->hasPermission($userId, 'demande.reply.draft')
            && !$this->access->hasPermission($userId, 'demande.reply.send')
        ) {
            return Response::deny('Permission requise: demande.reply.draft');
        }

        return $this->view($user, $demande);
    }

    public function reply(Utilisateur $user, Demande $demande): Response
    {
        if (!$this->access->hasPermission((int) $user->id_utilisateur, 'demande.reply.send')) {
            return Response::deny('Permission requise: demande.reply.send');
        }

        return $this->view($user, $demande);
    }
}
