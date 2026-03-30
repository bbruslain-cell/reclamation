<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function show(Request $request, int $id): StreamedResponse|RedirectResponse
    {
        $actor = $this->access->resolveActor($request);
        if (!$actor || !(bool) $actor->actif) {
            return redirect('/login');
        }

        $piece = DB::table('pieces_jointes as pj')
            ->leftJoin('demande_piece_jointe as dpj', 'dpj.id_piece_jointe', '=', 'pj.id_piece_jointe')
            ->leftJoin('reponse_piece_jointe as rpj', 'rpj.id_piece_jointe', '=', 'pj.id_piece_jointe')
            ->leftJoin('reponses as r', 'r.id_reponse', '=', 'rpj.id_reponse')
            ->where('pj.id_piece_jointe', $id)
            ->select(
                'pj.id_piece_jointe',
                'pj.nom_fichier',
                'pj.chemin_fichier',
                'pj.type_mime',
                DB::raw('COALESCE(dpj.id_demande, r.id_demande) as id_demande')
            )
            ->first();

        abort_if(!$piece || !$piece->id_demande, 404);

        try {
            $this->access->assertDemandAccess((int) $actor->id_utilisateur, (int) $piece->id_demande);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        abort_unless(Storage::disk('public')->exists($piece->chemin_fichier), 404);

        $headers = [];
        if (!empty($piece->type_mime)) {
            $headers['Content-Type'] = (string) $piece->type_mime;
        }

        return Storage::disk('public')->response(
            (string) $piece->chemin_fichier,
            (string) $piece->nom_fichier,
            $headers
        );
    }
}
