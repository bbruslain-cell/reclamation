@php
    $profileRoleLabel = '-';
    $profileServiceLabel = '-';
    $profileDirectionLabel = '-';
    $profileEmail = $actor->email ?? '-';
    $profileStatus = !empty($actor?->actif) ? 'Actif' : 'Inactif';
    $profileName = trim(((string) ($actor->prenom ?? '')).' '.((string) ($actor->nom ?? ''))) ?: 'Utilisateur';
    $profileShowLogout = $showLogout ?? true;
    $profileAvatarUrl = $actor?->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($actor->avatar_path) : null;
    $profileHasAvatar = !empty($profileAvatarUrl);
    $profileSpaces = collect();
    $profileCurrentSpaceCode = null;
    $profileOrganizationRows = [];
    $formatProfileCode = fn ($code) => trim((string) $code) !== '' ? trim((string) $code) : '-';
    $formatProfileServiceCode = fn ($code) => \App\Support\ServiceCodeFormatter::display($code) ?: '-';

    if ($actor) {
        $profileRoleCodes = collect(app(\App\Services\AccessControlService::class)->roleCodes((int) $actor->id_utilisateur));
        $profileRoleRows = \Illuminate\Support\Facades\DB::table('utilisateur_role as ur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->where('ur.id_utilisateur', $actor->id_utilisateur)
            ->get(['r.code', 'r.libelle']);

        $rolePriority = [
            'admin' => 0,
            'ciq' => 1,
            'dg' => 2,
            'chef_direction' => 3,
            'chef_service' => 4,
            'accueil' => 5,
            'agent' => 6,
            'lecture_seule' => 7,
        ];
        $roleLabels = [
            'admin' => 'Administrateur',
            'ciq' => 'Contrôle interne',
            'dg' => 'Direction générale',
            'chef_direction' => 'Chef de direction',
            'chef_service' => 'Chef de service',
            'accueil' => 'Accueil',
            'agent' => 'Agent',
            'lecture_seule' => 'Lecture seule',
        ];
        $profileRole = $profileRoleRows
            ->sortBy(fn ($role) => $rolePriority[strtolower((string) $role->code)] ?? 99)
            ->first();

        if ($profileRole) {
            $profileRoleCode = strtolower((string) $profileRole->code);
            $profileRoleLabel = $roleLabels[$profileRoleCode] ?? ($profileRole->libelle ?: $profileRole->code ?: '-');
        }

        $spaceItems = [];
        if (\Illuminate\Support\Facades\Gate::forUser($actor)->allows('admin.access')) {
            $spaceItems[] = ['code' => 'admin', 'title' => 'Administration', 'href' => '/admin'];
        }
        if ($profileRoleCodes->contains('accueil')) {
            $spaceItems[] = ['code' => 'accueil', 'title' => 'Accueil', 'href' => '/accueil/inbox'];
        }
        if ($profileRoleCodes->contains('chef_service')) {
            $spaceItems[] = ['code' => 'chef', 'title' => 'Chef de service', 'href' => '/chef/inbox'];
        }
        if ($profileRoleCodes->contains('chef_direction')) {
            $spaceItems[] = ['code' => 'chef_direction', 'title' => 'Chef de direction', 'href' => '/chef-direction/inbox'];
        }
        if ($profileRoleCodes->contains('agent')) {
            $spaceItems[] = ['code' => 'agent', 'title' => 'Agent', 'href' => '/agent/inbox'];
        }
        if ($profileRoleCodes->intersect(['ciq', 'dg', 'lecture_seule'])->isNotEmpty()) {
            $spaceItems[] = ['code' => 'pilotage', 'title' => 'Pilotage', 'href' => '/pilotage'];
        }

        $profileSpaces = collect($spaceItems);

        $profileCurrentSpaceCode = match (true) {
            request()->is('admin*') => 'admin',
            request()->is('accueil*') => 'accueil',
            request()->is('chef-direction*') || request()->is('direction*') => 'chef_direction',
            request()->is('chef*') => 'chef',
            request()->is('agent*') => 'agent',
            request()->is('pilotage*') => 'pilotage',
            default => null,
        };

        $currentSpace = $profileSpaces->firstWhere('code', $profileCurrentSpaceCode);
        if ($currentSpace) {
            $profileRoleLabel = $currentSpace['title'];
        }

        $profileContextCode = match ($profileCurrentSpaceCode) {
            'chef' => 'chef_service',
            'chef_direction' => 'chef_direction',
            'agent' => 'agent',
            'accueil' => 'accueil',
            'admin' => 'admin',
            default => isset($profileRoleCode) ? $profileRoleCode : null,
        };
        $isAdminProfile = $profileContextCode === 'admin' || $profileRoleCodes->contains('admin');
        $isDirectionProfile = !$isAdminProfile && $profileContextCode === 'chef_direction';
        $isServiceProfile = !$isAdminProfile && in_array($profileContextCode, ['chef_service', 'agent', 'accueil'], true);

        $mainService = \Illuminate\Support\Facades\DB::table('utilisateurs as u')
            ->leftJoin('services as s', 's.id_service', '=', 'u.id_service')
            ->leftJoin('directions as d', 'd.id_direction', '=', 's.id_direction')
            ->where('u.id_utilisateur', $actor->id_utilisateur)
            ->first([
                's.code as service_code',
                's.libelle as service_libelle',
                'd.code as direction_code',
                'd.libelle as direction_libelle',
            ]);

        if ($isServiceProfile && ($mainService?->service_code || $mainService?->service_libelle)) {
            $profileServiceLabel = $formatProfileServiceCode($mainService->service_code);
        }

        if ($isDirectionProfile && ($mainService?->direction_code || $mainService?->direction_libelle)) {
            $profileDirectionLabel = $formatProfileCode($mainService->direction_code);
        }

        $fallbackService = \Illuminate\Support\Facades\DB::table('perimetre_service as ps')
            ->join('services as s', 's.id_service', '=', 'ps.id_service')
            ->leftJoin('directions as d', 'd.id_direction', '=', 's.id_direction')
            ->where('ps.id_utilisateur', $actor->id_utilisateur)
            ->orderBy('s.code')
            ->first([
                's.code as service_code',
                's.libelle as service_libelle',
                'd.code as direction_code',
                'd.libelle as direction_libelle',
            ]);

        if ($isServiceProfile && $profileServiceLabel === '-' && ($fallbackService?->service_code || $fallbackService?->service_libelle)) {
            $profileServiceLabel = $formatProfileServiceCode($fallbackService->service_code);
        }

        if ($isDirectionProfile && $profileDirectionLabel === '-' && ($fallbackService?->direction_code || $fallbackService?->direction_libelle)) {
            $profileDirectionLabel = $formatProfileCode($fallbackService->direction_code);
        }

        if ($isDirectionProfile && $profileDirectionLabel === '-') {
            $fallbackDirection = \Illuminate\Support\Facades\DB::table('perimetre_direction as pd')
                ->join('directions as d', 'd.id_direction', '=', 'pd.id_direction')
                ->where('pd.id_utilisateur', $actor->id_utilisateur)
                ->orderBy('d.code')
                ->first(['d.code', 'd.libelle']);

            if ($fallbackDirection?->code || $fallbackDirection?->libelle) {
                $profileDirectionLabel = $formatProfileCode($fallbackDirection->code);
            }
        }

        if ($isDirectionProfile && $profileDirectionLabel !== '-') {
            $profileOrganizationRows[] = [
                'label' => 'Direction',
                'value' => $profileDirectionLabel,
                'tone' => 'sky',
                'icon' => 'direction',
            ];
        }

        if ($isServiceProfile && $profileServiceLabel !== '-') {
            $profileOrganizationRows[] = [
                'label' => 'Service',
                'value' => $profileServiceLabel,
                'tone' => 'emerald',
                'icon' => 'service',
            ];
        }
    }
@endphp

<div class="relative" data-user-profile-menu>
    <button type="button" data-user-profile-toggle class="anbg-profile-trigger flex h-10 w-10 items-center justify-center rounded-full text-lg shadow-sm transition-all duration-150 focus:outline-none" aria-expanded="false" aria-label="Afficher le profil utilisateur">
        @if($profileHasAvatar)
            <img src="{{ $profileAvatarUrl }}" alt="Photo de profil" class="h-full w-full rounded-full object-cover">
        @else
        <svg class="anbg-profile-icon" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <circle cx="24" cy="24" r="21" stroke="currentColor" stroke-width="3"/>
            <circle cx="24" cy="18" r="6.5" stroke="currentColor" stroke-width="3"/>
            <path d="M10.5 39c2.6-8.2 7.8-12.3 13.5-12.3S34.9 30.8 37.5 39" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
        </svg>
        @endif
        <span class="anbg-profile-online-dot" aria-hidden="true"></span>
    </button>

    <div data-user-profile-card class="anbg-profile-card absolute right-0 top-full z-[90] mt-2 hidden overflow-hidden rounded-2xl border bg-white text-left shadow-2xl" style="width: min(20rem, calc(100vw - 1rem));">
        <div class="anbg-profile-head px-5 py-5 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full shadow-lg" style="background: rgba(255, 255, 255, 0.94); color: #071266;">
                @if($profileHasAvatar)
                    <img src="{{ $profileAvatarUrl }}" alt="Photo de profil" class="h-full w-full rounded-full object-cover">
                @else
                <svg class="anbg-profile-icon-large" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <circle cx="24" cy="24" r="21" stroke="currentColor" stroke-width="3"/>
                    <circle cx="24" cy="18" r="6.5" stroke="currentColor" stroke-width="3"/>
                    <path d="M10.5 39c2.6-8.2 7.8-12.3 13.5-12.3S34.9 30.8 37.5 39" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                @endif
            </span>
            <p class="mt-3 break-words text-base font-semibold leading-tight" style="color: #ffffff;">{{ $profileName }}</p>
            <p class="mt-1 break-all text-sm" style="color: rgba(255, 255, 255, 0.82);">{{ $profileEmail }}</p>
        </div>
        <div class="border-b border-neutral-100 bg-neutral-50 px-4 py-3">
            <form method="post" action="/profil/avatar" enctype="multipart/form-data" data-profile-avatar-form>
                @csrf
                <input type="file" name="avatar" accept="image/*" class="hidden" data-profile-avatar-input>
                <button type="button" data-profile-avatar-button class="anbg-profile-avatar-button inline-flex w-full items-center justify-center gap-2 rounded-xl border border-anbg-sky/30 bg-white px-3 py-2 text-xs font-semibold text-navy transition-all duration-150 hover:bg-anbg-sky/10">
                    <svg class="icon-svg text-xs text-anbg-sky" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 5h3l1.5-2h5L16 5h3a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Zm7 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z" fill="currentColor"/>
                    </svg>
                    <span>{{ $profileHasAvatar ? 'Changer la photo' : 'Ajouter une photo' }}</span>
                </button>
            </form>
            @if($profileHasAvatar)
            <form method="post" action="/profil/avatar/supprimer" class="mt-2">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-100 bg-white px-3 py-2 text-xs font-semibold text-red-600 transition-colors hover:bg-red-50">
                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h2v9H7V9Zm4 0h2v9h-2V9Zm4 0h2v9h-2V9ZM6 7h12l-1 13H7L6 7Z" fill="currentColor"/>
                    </svg>
                    <span>Supprimer la photo</span>
                </button>
            </form>
            @endif
        </div>
        @if($profileSpaces->count() > 1)
        <div class="border-b border-neutral-100 bg-white px-4 py-3">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">Changer d'espace</p>
            <label class="mt-2 block">
                <span class="sr-only">Choisir un espace</span>
                <select data-profile-space-select class="w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold text-navy outline-none transition-colors hover:border-anbg-sky/50 focus:border-anbg-sky focus:ring-2 focus:ring-anbg-sky/20">
                @foreach($profileSpaces as $space)
                    @php $isCurrentSpace = ($space['code'] ?? null) === $profileCurrentSpaceCode; @endphp
                    <option value="{{ $space['href'] }}" @selected($isCurrentSpace)>{{ $space['title'] }}{{ $isCurrentSpace ? ' - actuel' : '' }}</option>
                @endforeach
                </select>
            </label>
        </div>
        @endif
        <div class="divide-y divide-neutral-100 bg-white text-sm">
            <div class="anbg-profile-row flex items-start gap-3 px-4 py-3 transition-colors">
                <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                    <svg class="icon-svg text-sm anbg-profile-line-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="4" y="3" width="16" height="18" rx="3"/>
                        <circle cx="12" cy="10" r="3"/>
                        <path d="M8.5 17c.8-1.6 2-2.4 3.5-2.4s2.7.8 3.5 2.4"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">Rôle</p>
                    <p class="mt-0.5 break-words font-medium text-navy">{{ $profileRoleLabel }}</p>
                </div>
            </div>
            @foreach($profileOrganizationRows as $profileRow)
            <div class="anbg-profile-row flex items-start gap-3 px-4 py-3 transition-colors">
                <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ ($profileRow['tone'] ?? '') === 'sky' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700' }}">
                    @if(($profileRow['icon'] ?? '') === 'direction')
                    <svg class="icon-svg text-sm anbg-profile-line-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 21h16"/>
                        <path d="M6 21V5.5L12 3l6 2.5V21"/>
                        <path d="M9 9h.01M12 9h.01M15 9h.01M9 13h.01M12 13h.01M15 13h.01"/>
                        <path d="M10 21v-4h4v4"/>
                    </svg>
                    @else
                    <svg class="icon-svg text-sm anbg-profile-line-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="7" width="18" height="13" rx="2"/>
                        <path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7"/>
                        <path d="M3 12h18"/>
                        <path d="M10 12v1.5h4V12"/>
                    </svg>
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">{{ $profileRow['label'] }}</p>
                    <p class="mt-0.5 break-words font-medium text-navy">{{ $profileRow['value'] }}</p>
                </div>
            </div>
            @endforeach
            <div class="anbg-profile-row anbg-profile-row-status flex items-center gap-3 px-4 py-3 transition-colors">
                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-700">
                    <svg class="icon-svg text-sm anbg-profile-line-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.5"/>
                        <path d="m8.5 12.3 2.2 2.2 4.8-5"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">Statut</p>
                    <p class="anbg-profile-status-value mt-0.5 font-medium">{{ $profileStatus }}</p>
                </div>
            </div>
        </div>
        @if($profileShowLogout)
        <form method="post" action="/logout" class="border-t border-neutral-100 bg-neutral-50 px-4 py-3">
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center gap-3 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-red-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-300">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-white/15">
                    <svg class="icon-svg text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z" fill="currentColor"/>
                    </svg>
                </span>
                <span>Se déconnecter</span>
            </button>
        </form>
        @endif
    </div>
</div>

@once
    <style nonce="{{ $cspNonce ?? '' }}">
        .anbg-profile-trigger {
            position: relative;
            border: 1px solid rgba(57, 150, 211, 0.45);
            background: #1c203d;
            color: #ffffff;
        }

        .anbg-profile-online-dot {
            position: absolute;
            right: 0.05rem;
            bottom: 0.05rem;
            width: 0.72rem;
            height: 0.72rem;
            border-radius: 9999px;
            background: #16a34a;
            box-shadow: 0 0 0 1px rgba(22, 163, 74, 0.20);
        }

        .anbg-profile-icon {
            width: 1.75rem;
            height: 1.75rem;
            display: block;
        }

        .anbg-profile-icon-large {
            width: 3.25rem;
            height: 3.25rem;
            display: block;
        }

        .anbg-profile-line-icon {
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .anbg-profile-trigger:hover,
        .anbg-profile-trigger[aria-expanded="true"] {
            border-color: #f8e932;
            background: #3996d3;
            box-shadow: 0 10px 24px rgba(28, 32, 61, 0.22);
            transform: translateY(-1px);
        }

        .anbg-profile-card {
            border-color: rgba(28, 32, 61, 0.10);
        }

        .anbg-profile-head {
            background: #1c203d;
            color: #ffffff;
        }

        .anbg-profile-card > div:not(.anbg-profile-head) p,
        .anbg-profile-row p,
        .anbg-profile-row-status p {
            color: #000000;
        }

        .anbg-profile-row-status p:last-child {
            color: #047857 !important;
        }

        .anbg-profile-status-value {
            color: #047857 !important;
        }

        .anbg-profile-avatar-button:hover {
            border-color: #3996d3;
            background: rgba(57, 150, 211, 0.12);
            box-shadow: 0 8px 18px rgba(57, 150, 211, 0.18);
            transform: translateY(-1px);
        }

        .anbg-profile-row:hover {
            background: rgba(57, 150, 211, 0.10);
        }

        .anbg-profile-row-status:hover {
            background: rgba(143, 192, 67, 0.12);
        }
    </style>

    <script nonce="{{ $cspNonce ?? '' }}">
        (() => {
            const closeAllProfileMenus = () => {
                document.querySelectorAll('[data-user-profile-card]').forEach((card) => {
                    card.classList.add('hidden');
                });
                document.querySelectorAll('[data-user-profile-toggle]').forEach((toggle) => {
                    toggle.setAttribute('aria-expanded', 'false');
                });
            };

            document.querySelectorAll('[data-user-profile-menu]').forEach((menu) => {
                const toggle = menu.querySelector('[data-user-profile-toggle]');
                const card = menu.querySelector('[data-user-profile-card]');
                const avatarButton = menu.querySelector('[data-profile-avatar-button]');
                const avatarInput = menu.querySelector('[data-profile-avatar-input]');
                const avatarForm = menu.querySelector('[data-profile-avatar-form]');
                const spaceSelect = menu.querySelector('[data-profile-space-select]');

                toggle?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const wasHidden = card?.classList.contains('hidden');
                    closeAllProfileMenus();
                    if (wasHidden) {
                        card?.classList.remove('hidden');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                });

                card?.addEventListener('click', (event) => {
                    event.stopPropagation();
                });

                avatarButton?.addEventListener('click', () => {
                    avatarInput?.click();
                });

                avatarInput?.addEventListener('change', () => {
                    if (avatarInput.files?.length) {
                        avatarForm?.requestSubmit();
                    }
                });

                spaceSelect?.addEventListener('change', () => {
                    const target = spaceSelect.value;
                    if (target && target !== window.location.pathname) {
                        window.location.assign(target);
                    }
                });
            });

            document.addEventListener('click', closeAllProfileMenus);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAllProfileMenus();
                }
            });
        })();
    </script>
@endonce
