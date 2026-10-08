<?php

namespace App\Services;

use App\Models\Utilisateur;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionSecurityService
{
    public const VERSION_KEY = 'auth_session_version';

    public function login(Request $request, Utilisateur $actor): void
    {
        Auth::guard('web')->login($actor);
        $this->synchronizeCurrentSession($request, (int) $actor->session_version);
    }

    public function logoutCurrent(Request $request): void
    {
        Auth::guard('web')->logout();

        if (! $request->hasSession()) {
            return;
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function currentSessionMatches(Request $request, Utilisateur $actor): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $sessionVersion = $request->session()->get(self::VERSION_KEY);

        return is_numeric($sessionVersion)
            && (int) $sessionVersion === (int) $actor->session_version;
    }

    public function revokeForUser(int $userId, ?string $exceptSessionId = null): int
    {
        return DB::transaction(function () use ($userId, $exceptSessionId): int {
            $currentVersion = DB::table('utilisateurs')
                ->where('id_utilisateur', $userId)
                ->lockForUpdate()
                ->value('session_version');

            if ($currentVersion === null) {
                return 0;
            }

            $nextVersion = max(1, (int) $currentVersion + 1);

            DB::table('utilisateurs')
                ->where('id_utilisateur', $userId)
                ->update([
                    'session_version' => $nextVersion,
                    'remember_token' => Str::random(60),
                    'updated_at' => now(),
                ]);

            $this->deletePersistedSessions($userId, $exceptSessionId);

            return $nextVersion;
        });
    }

    public function synchronizeCurrentSession(
        Request $request,
        int $sessionVersion,
        bool $regenerate = false
    ): void {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put(self::VERSION_KEY, $sessionVersion);

        if ($regenerate) {
            $request->session()->regenerate(true);
        }
    }

    private function deletePersistedSessions(int $userId, ?string $exceptSessionId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = $this->sessionConnection()
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $userId);

        if ($exceptSessionId !== null && $exceptSessionId !== '') {
            $query->where('id', '!=', $exceptSessionId);
        }

        $query->delete();
    }

    private function sessionConnection(): ConnectionInterface
    {
        $connection = config('session.connection');

        return DB::connection(is_string($connection) && $connection !== '' ? $connection : null);
    }
}
