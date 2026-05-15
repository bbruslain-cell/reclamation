<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Services\AccessControlService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;


class AttachmentController extends Controller
{
    private const SAFE_MIME_BY_EXTENSION = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

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
            $demand = Demande::query()->find((int) $piece->id_demande);
            abort_if(!$demand, 404);

            Gate::forUser($actor)->authorize('view', $demand);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        $disk = $this->resolveAttachmentDisk((string) $piece->chemin_fichier);
        abort_unless($disk !== null, 404);

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        return $storage->download(
            (string) $piece->chemin_fichier,
            (string) $piece->nom_fichier,
            [
                'Content-Type' => $this->safeMimeType((string) $piece->nom_fichier, (string) ($piece->type_mime ?? '')),
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]
        );
    }

    private function resolveAttachmentDisk(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    private function safeMimeType(string $filename, string $storedMime): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (isset(self::SAFE_MIME_BY_EXTENSION[$extension])) {
            return self::SAFE_MIME_BY_EXTENSION[$extension];
        }

        if (in_array($storedMime, self::SAFE_MIME_BY_EXTENSION, true)) {
            return $storedMime;
        }

        return 'application/octet-stream';
    }
}
