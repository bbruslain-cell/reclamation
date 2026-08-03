<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicDemandRequest;
use App\Services\PublicDemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicDemandController extends Controller
{
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
        $tracking = $this->publicDemand->create(
            $request->payload(),
            $file instanceof UploadedFile ? $file : null
        );

        return redirect('/reclamations/nouvelle')
            ->with('success', "Votre demande est enregistrée avec succès. Numéro de suivi : {$tracking}");
    }
}
