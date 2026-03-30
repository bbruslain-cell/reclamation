<?php

namespace App\Http\Middleware;

use App\Services\AccessControlService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgentAuthenticated
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $this->access->resolveActor($request);

        if (!$actor || !$actor->actif) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentification requise'], 401);
            }

            return redirect('/login');
        }

        $request->attributes->set('actor', $actor);
        Auth::guard('web')->setUser($actor);

        if (
            (bool) ($actor->changement_mdp_requis ?? false)
            && !$this->isPasswordChangePath($request)
        ) {
            return redirect('/mot-de-passe/nouveau');
        }

        return $next($request);
    }

    private function isPasswordChangePath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return in_array($path, ['mot-de-passe/nouveau'], true)
            || $path === 'logout';
    }
}
