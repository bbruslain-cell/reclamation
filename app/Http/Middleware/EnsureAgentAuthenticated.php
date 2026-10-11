<?php

namespace App\Http\Middleware;

use App\Services\AccessControlService;
use App\Services\SessionSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgentAuthenticated
{
    public function __construct(
        private readonly AccessControlService $access,
        private readonly SessionSecurityService $sessions
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $this->access->resolveActor($request);
        $guard = Auth::guard('web');
        $sessionAuthenticated = $request->hasSession()
            && $request->session()->has($guard->getName());
        $invalidSessionVersion = $sessionAuthenticated
            && $actor
            && ! $this->sessions->currentSessionMatches($request, $actor);

        if (! $actor || ! $actor->actif || $invalidSessionVersion) {
            if ($sessionAuthenticated) {
                $this->sessions->logoutCurrent($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentification requise'], 401);
            }

            return redirect('/login');
        }

        $request->attributes->set('actor', $actor);
        Auth::guard('web')->setUser($actor);

        if (
            (bool) ($actor->changement_mdp_requis ?? false)
            && ! $this->isPasswordChangePath($request)
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
