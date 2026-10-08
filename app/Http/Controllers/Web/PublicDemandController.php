<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicDemandRequest;
use App\Mail\PublicDemandAcknowledgementMail;
use App\Services\PublicDemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class PublicDemandController extends Controller
{
    private const ACKNOWLEDGEMENT_MAX_PROCESSING_HOURS = 72;

    public function __construct(private readonly PublicDemandService $publicDemand)
    {
    }

    public function create(): View
    {
        $types = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('actif', true)
            ->orderBy('ordre_affichage')
            ->get(['code', 'libelle']);

        $establishments = Schema::hasTable('etablissements')
            ? DB::table('etablissements')
                ->where('actif', true)
                ->orderBy('nom')
                ->pluck('nom')
                ->values()
                ->all()
            : [];

        return view('public.create-demand', [
            'types' => $types,
            'usagerStatuses' => StorePublicDemandRequest::usagerStatuses(),
            'countries' => StorePublicDemandRequest::countries(),
            'establishments' => $establishments,
            'categoriesByType' => StorePublicDemandRequest::categoriesByType(),
            'acceptedEmailTldPattern' => StorePublicDemandRequest::acceptedEmailTldPattern(),
        ]);
    }

    public function store(StorePublicDemandRequest $request): RedirectResponse
    {
        $file = $request->file('piece_jointe');
        $payload = $request->payload();
        $tracking = $this->publicDemand->create(
            $payload,
            $file instanceof UploadedFile ? $file : null
        );

        $this->sendAcknowledgementMail($tracking, $payload);

        return redirect('/reclamations/nouvelle')
            ->with('success', "Votre demande est enregistrée avec succès. Numéro de suivi : {$tracking}");
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendAcknowledgementMail(string $tracking, array $payload): void
    {
        $email = trim((string) ($payload['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $subjectLabel = trim((string) ($payload['objet'] ?? 'Réclamation'));
        $recipientName = trim(implode(' ', array_filter([
            trim((string) ($payload['prenom'] ?? '')),
            trim((string) ($payload['nom'] ?? '')),
        ]))) ?: 'usager';
        $content = "Accusé de réception de la demande {$tracking}. "
            .'Délai annoncé: '.self::ACKNOWLEDGEMENT_MAX_PROCESSING_HOURS.' heures.';

        try {
            Mail::to($email)->send(new PublicDemandAcknowledgementMail(
                trackingNumber: $tracking,
                subjectLabel: $subjectLabel,
                recipientName: $recipientName,
                maxProcessingHours: self::ACKNOWLEDGEMENT_MAX_PROCESSING_HOURS
            ));

            $this->recordAcknowledgementNotification($tracking, $email, $content, 'succes');
        } catch (Throwable $exception) {
            $this->recordAcknowledgementNotification($tracking, $email, $content, 'echec', $exception->getMessage());

            Log::warning('Echec envoi accuse reception usager', [
                'numero_suivi' => $tracking,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function recordAcknowledgementNotification(
        string $tracking,
        string $email,
        string $content,
        string $statusCode,
        ?string $error = null
    ): void {
        try {
            $demandId = DB::table('demandes')->where('numero_suivi', $tracking)->value('id_demande');
            $typeId = DB::table('parametres')
                ->where('famille', 'type_notif')
                ->where('code', 'accuse_reception')
                ->value('id_parametre');
            $statusId = DB::table('parametres')
                ->where('famille', 'statut_notif')
                ->where('code', $statusCode)
                ->value('id_parametre');

            if (!$demandId || !$typeId) {
                return;
            }

            DB::table('notifications')->insert([
                'id_demande' => $demandId,
                'id_type_notif' => $typeId,
                'id_statut_notif' => $statusId ?: null,
                'id_emetteur' => null,
                'destinataire_email' => $email,
                'sujet' => 'ANBG - Accusé de réception '.$tracking,
                'contenu' => $content,
                'date_envoi' => now(),
                'message_erreur' => $error,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Echec journalisation accuse reception usager', [
                'numero_suivi' => $tracking,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
