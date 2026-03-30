@extends('admin.layout')

@section('title', 'Utilisateurs')

@section('content')
<style>
    [v-cloak] { display: none !important; }
</style>
@php
    $initialEditingUser = $editingUser ?? null;
    $initialEditingRoles = $editingRoles ?? [];
    $pageUsers = $utilisateurs->getCollection()
        ->map(fn ($user) => [
            'search' => strtolower(trim($user->nom.' '.$user->prenom.' '.$user->email)),
            'service_code' => $user->service_code,
            'roles' => array_values($rolesByUser[$user->id_utilisateur] ?? []),
            'actif' => (bool) $user->actif,
        ])
        ->values();
@endphp

<div id="users-app" v-cloak class="space-y-5">
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-sky-100 bg-[linear-gradient(135deg,_rgba(57,150,211,0.10),_rgba(255,255,255,0.95))] px-5 py-4 text-center shadow-soft">
            <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Utilisateurs globaux</p>
            <p class="mt-2 text-3xl font-semibold text-navy" data-countup="{{ (int) ($counts['utilisateurs'] ?? 0) }}">0</p>
        </div>
        <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-leaf/30 bg-[linear-gradient(135deg,_rgba(143,192,67,0.12),_rgba(255,255,255,0.95))] px-5 py-4 text-center shadow-soft">
            <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Utilisateurs actifs</p>
            <p class="mt-2 text-3xl font-semibold text-navy" data-countup="{{ (int) ($counts['utilisateurs_actifs'] ?? 0) }}">0</p>
        </div>
        <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-gold/30 bg-[linear-gradient(135deg,_rgba(249,177,60,0.12),_rgba(255,255,255,0.95))] px-5 py-4 text-center shadow-soft">
            <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Utilisateurs inactifs</p>
            <p class="mt-2 text-3xl font-semibold text-navy" data-countup="{{ (int) ($counts['utilisateurs_inactifs'] ?? 0) }}">0</p>
        </div>
        <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-neutral-200 bg-white px-5 py-4 text-center shadow-soft">
            <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Visibles sur la page</p>
            <p class="mt-2 text-3xl font-semibold text-navy">@{{ animatedVisibleUsersCount }}</p>
            <p class="mt-1 max-w-[18rem] text-xs text-neutral-400">Suivi automatique selon la recherche, le service et le role.</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-sky-100 bg-white/95 shadow-soft">
        <div class="flex flex-col gap-4 border-b border-sky-100 bg-[linear-gradient(180deg,_rgba(57,150,211,0.08),_rgba(255,255,255,0.96))] px-5 py-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Administration</p>
                <h2 class="mt-1 text-lg font-semibold text-navy">Utilisateurs</h2>
                <p class="mt-1 text-sm text-neutral-500">Modification des profils, services et rôles sans changer l’identifiant.</p>
            </div>
            <button @click="openCreateModal" class="inline-flex items-center justify-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-navy-700">
                <i class="fas fa-plus text-xs"></i>
                <span>Nouvel utilisateur</span>
            </button>
        </div>

        <div class="grid gap-3 border-b border-neutral-200 bg-neutral-50/80 px-5 py-4 md:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
            <div class="relative">
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-neutral-400"></i>
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Rechercher par nom, prénom ou email"
                    class="field w-full rounded-xl border border-neutral-200 bg-white py-2.5 pl-9 pr-3 text-sm text-navy placeholder-neutral-400"
                >
            </div>
            <select v-model="searchService" class="field w-full appearance-none rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                <option value="">Tous les services</option>
                @foreach($services as $service)
                    <option value="{{ $service->code }}">{{ $service->code }}</option>
                @endforeach
            </select>
            <select v-model="searchRole" class="field w-full appearance-none rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                <option value="">Tous les roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->code }}">{{ $role->code }}</option>
                @endforeach
            </select>
            <button @click="resetFilters" type="button" class="rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100">
                Réinitialiser le filtre
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-200 bg-sky-50/40">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">ID</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Utilisateur</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Email</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Service</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Rôles</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Statut</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                @foreach($utilisateurs as $user)
                    @php
                        $userRoles = $rolesByUser[$user->id_utilisateur] ?? [];
                        $userRoleIds = $roleIdsByUser[$user->id_utilisateur] ?? [];
                    @endphp
                    <tr
                        class="trow bg-white transition-colors"
                        v-show="isRowVisible({{ json_encode(strtolower($user->nom.' '.$user->prenom.' '.$user->email)) }}, {{ json_encode($user->service_code) }}, {{ json_encode(array_values($userRoles)) }})"
                    >
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 font-mono text-xs text-neutral-600">
                                U-{{ str_pad((string) $user->id_utilisateur, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div>
                                <p class="text-sm font-medium text-navy">{{ $user->prenom }} {{ $user->nom }}</p>
                                <p class="text-xs text-neutral-400">Identifiant stable</p>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-neutral-600">{{ $user->email }}</td>
                        <td class="px-5 py-4">
                            @if($user->service_code)
                                <span class="inline-flex rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs text-neutral-600">{{ $user->service_code }}</span>
                            @else
                                <span class="text-sm text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($userRoles as $role)
                                    <span class="rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] text-sky">{{ $role }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @if($user->actif)
                                <span class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>
                                    Actif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-nowrap items-center gap-2 whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="openEditModal({{ json_encode([
                                        'id_utilisateur' => $user->id_utilisateur,
                                        'nom' => $user->nom,
                                        'prenom' => $user->prenom,
                                        'email' => $user->email,
                                        'id_service' => $user->id_service,
                                        'id_direction' => $user->id_direction ?? null,
                                        'roles' => $userRoleIds,
                                    ]) }})"
                                    class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-xs font-medium text-neutral-600 transition-colors hover:border-sky-100 hover:bg-sky-50 hover:text-navy"
                                >
                                    Modifier
                                </button>
                                <form method="post" action="/admin/utilisateurs/{{ $user->id_utilisateur }}/toggle">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-xs font-medium text-neutral-600 transition-colors hover:border-gold/40 hover:bg-gold-50">
                                        {{ $user->actif ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </form>
                                <form method="post" action="/admin/utilisateurs/{{ $user->id_utilisateur }}/reset-password">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-xs font-medium text-neutral-600 transition-colors hover:border-sky-100 hover:bg-sky-50">
                                        Réinitialiser
                                    </button>
                                </form>
                                <form method="post" action="/admin/utilisateurs/{{ $user->id_utilisateur }}/delete" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-medium text-red-600 transition-colors hover:bg-red-50">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($utilisateurs->hasPages())
        <div class="flex flex-col gap-3 border-t border-neutral-200 bg-neutral-50/80 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-neutral-500">
                Affichage de {{ $utilisateurs->firstItem() }} à {{ $utilisateurs->lastItem() }} sur {{ $utilisateurs->total() }} utilisateurs
            </p>
            <div>
                {{ $utilisateurs->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </section>

    <template v-if="booted">
        <teleport to="body">
            <div v-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 p-4" @click.self="closeModal">
                <div class="w-full max-w-4xl overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-shell">
                    <div class="flex items-center justify-between border-b border-sky-100 bg-[linear-gradient(180deg,_rgba(57,150,211,0.09),_rgba(255,255,255,0.98))] px-6 py-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Formulaire</p>
                            <h3 class="mt-1 text-lg font-semibold text-navy">@{{ formData.id_utilisateur ? 'Modifier un utilisateur' : 'Créer un utilisateur' }}</h3>
                        </div>
                        <button type="button" @click="closeModal" class="flex h-9 w-9 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-navy">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
                        <form id="user-vue-form" method="post" action="/admin/utilisateurs" class="space-y-6">
                            @csrf
                            <input type="hidden" name="user_id" :value="formData.id_utilisateur">

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-navy">Nom</label>
                                    <input name="nom" v-model="formData.nom" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-navy">Prénom</label>
                                    <input name="prenom" v-model="formData.prenom" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-navy">Email</label>
                                    <input name="email" type="email" v-model="formData.email" required class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-navy">Mot de passe</label>
                                    <input name="mot_de_passe" type="password" placeholder="Laisser vide pour conserver ou générer" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                </div>
                            </div>

                            <div class="rounded-2xl border border-sky-100 bg-sky-50/55 px-4 py-4">
                                <p class="mb-3 text-sm font-medium text-navy">Rôles d’accès</p>
                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach($roles as $role)
                                        <label class="flex items-start gap-3 rounded-xl border border-neutral-200 bg-white px-3 py-3">
                                            <input type="checkbox" name="id_roles[]" :value="{{ $role->id_role }}" v-model="formData.roles" class="mt-1 rounded border-neutral-300 text-sky focus:ring-sky">
                                            <span>
                                                <span class="block text-sm font-medium text-navy">{{ $role->code }}</span>
                                                <span class="block text-xs text-neutral-400">{{ $role->libelle }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div v-if="requiresDirection || requiresService" class="grid gap-4 rounded-2xl border border-gold/25 bg-gold-50/45 px-4 py-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-navy">
                                        Direction
                                        <span v-if="requiresDirection" class="text-red-500">*</span>
                                    </label>
                                    <select name="id_direction" v-model="formData.id_direction" @change="onDirectionChange" class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy">
                                        <option value="">Choisir une direction</option>
                                        @foreach($directions as $direction)
                                            <option value="{{ $direction->id_direction }}">{{ $direction->code }} - {{ $direction->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div v-if="requiresService">
                                    <label class="mb-1.5 block text-sm font-medium text-navy">
                                        Service principal
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        name="id_service"
                                        v-model="formData.id_service"
                                        :disabled="!formData.id_direction"
                                        class="field w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm text-navy disabled:cursor-not-allowed disabled:bg-neutral-100"
                                    >
                                        <option value="">Choisir un service</option>
                                        <option v-for="service in filteredServices" :key="service.id_service" :value="service.id_service">
                                            @{{ service.code }} - @{{ service.libelle }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 bg-neutral-50 px-6 py-4">
                        <button type="button" @click="closeModal" class="rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-medium text-neutral-600 transition-colors hover:bg-neutral-100">
                            Annuler
                        </button>
                        <button type="submit" form="user-vue-form" class="rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-navy-700">
                            @{{ formData.id_utilisateur ? 'Enregistrer les modifications' : 'Créer l’utilisateur' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </template>
</div>
@endsection

@push('scripts')
<script>
    const { createApp, ref, computed, onMounted, nextTick, watch } = Vue;

    createApp({
        setup() {
            const booted = ref(false);
            const searchQuery = ref('');
            const searchService = ref('');
            const searchRole = ref('');
            const isModalOpen = ref(false);
            const animatedVisibleUsersCount = ref(0);
            const rolesList = @json($roles);
            const allServices = @json($services);
            const pageUsers = @json($pageUsers);
            const formData = ref({
                id_utilisateur: null,
                nom: '',
                prenom: '',
                email: '',
                id_service: '',
                id_direction: '',
                roles: [],
            });

            const requiresDirection = computed(() => {
                const selectedRoleIds = formData.value.roles.map((id) => parseInt(id, 10));
                return rolesList
                    .filter((role) => selectedRoleIds.includes(role.id_role))
                    .some((role) => role.code === 'chef_direction');
            });

            const requiresService = computed(() => {
                const selectedRoleIds = formData.value.roles.map((id) => parseInt(id, 10));
                return rolesList
                    .filter((role) => selectedRoleIds.includes(role.id_role))
                    .some((role) => ['accueil', 'chef_service', 'agent'].includes(role.code));
            });

            const filteredServices = computed(() => {
                if (!formData.value.id_direction) {
                    return [];
                }

                return allServices.filter((service) => service.id_direction == formData.value.id_direction);
            });

            const visibleUsers = computed(() => {
                const query = searchQuery.value.toLowerCase().trim();

                return pageUsers.filter((user) => {
                    const matchesQuery = query === '' || user.search.includes(query);
                    const matchesService = searchService.value === '' || (user.service_code && user.service_code === searchService.value);
                    const matchesRole = searchRole.value === '' || user.roles.includes(searchRole.value);
                    return matchesQuery && matchesService && matchesRole;
                });
            });

            const visibleUsersCount = computed(() => visibleUsers.value.length);

            const animateValue = (from, to, setter, duration = 550) => {
                const start = Number.isFinite(from) ? from : 0;
                const end = Number.isFinite(to) ? to : 0;
                const startTime = performance.now();

                const frame = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    setter(Math.round(start + ((end - start) * eased)));

                    if (progress < 1) {
                        requestAnimationFrame(frame);
                    }
                };

                requestAnimationFrame(frame);
            };

            const resetFilters = () => {
                searchQuery.value = '';
                searchService.value = '';
                searchRole.value = '';
            };

            const isRowVisible = (searchString, serviceCode, roles) => {
                const query = searchQuery.value.toLowerCase().trim();
                const matchesQuery = query === '' || searchString.includes(query);
                const matchesService = searchService.value === '' || (serviceCode && serviceCode === searchService.value);
                const matchesRole = searchRole.value === '' || (Array.isArray(roles) && roles.includes(searchRole.value));
                return matchesQuery && matchesService && matchesRole;
            };

            const lockBody = () => {
                document.body.style.overflow = 'hidden';
            };

            const unlockBody = () => {
                document.body.style.overflow = '';
            };

            const openCreateModal = () => {
                formData.value = {
                    id_utilisateur: null,
                    nom: '',
                    prenom: '',
                    email: '',
                    id_service: '',
                    id_direction: '',
                    roles: [],
                };
                isModalOpen.value = true;
                lockBody();
            };

            const openEditModal = (user) => {
                let inferredDirectionId = user.id_direction || '';
                if (!inferredDirectionId && user.id_service) {
                    const currentService = allServices.find((service) => service.id_service == user.id_service);
                    if (currentService) {
                        inferredDirectionId = currentService.id_direction;
                    }
                }

                formData.value = {
                    id_utilisateur: user.id_utilisateur,
                    nom: user.nom,
                    prenom: user.prenom,
                    email: user.email,
                    id_service: user.id_service || '',
                    id_direction: inferredDirectionId,
                    roles: user.roles || [],
                };
                isModalOpen.value = true;
                lockBody();
            };

            const closeModal = () => {
                isModalOpen.value = false;
                unlockBody();
            };

            const onDirectionChange = () => {
                const stillValid = filteredServices.value.some((service) => service.id_service == formData.value.id_service);
                if (!stillValid) {
                    formData.value.id_service = '';
                }
            };

            onMounted(async () => {
                booted.value = true;
                await nextTick();

                document.querySelectorAll('[data-countup]').forEach((element, index) => {
                    const target = Number.parseInt(element.dataset.countup || '0', 10);
                    const startTime = performance.now();
                    const duration = 850 + (index * 120);

                    const frame = (currentTime) => {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const value = Math.round(target * eased);

                        element.textContent = new Intl.NumberFormat('fr-FR').format(value);

                        if (progress < 1) {
                            requestAnimationFrame(frame);
                        }
                    };

                    requestAnimationFrame(frame);
                });

                animateValue(0, visibleUsersCount.value, (value) => {
                    animatedVisibleUsersCount.value = value;
                }, 700);

                const initialEditingUser = @json($initialEditingUser);
                const initialEditingRoles = @json($initialEditingRoles);

                if (initialEditingUser) {
                    formData.value = {
                        id_utilisateur: initialEditingUser.id_utilisateur,
                        nom: initialEditingUser.nom,
                        prenom: initialEditingUser.prenom,
                        email: initialEditingUser.email,
                        id_service: initialEditingUser.id_service || '',
                        id_direction: initialEditingUser.id_direction || '',
                        roles: initialEditingRoles,
                    };
                    isModalOpen.value = true;
                    lockBody();
                }
            });

            watch(visibleUsersCount, (newValue, oldValue) => {
                animateValue(oldValue ?? 0, newValue, (value) => {
                    animatedVisibleUsersCount.value = value;
                }, 420);
            });

            return {
                animatedVisibleUsersCount,
                booted,
                closeModal,
                filteredServices,
                formData,
                isModalOpen,
                isRowVisible,
                onDirectionChange,
                openCreateModal,
                openEditModal,
                requiresDirection,
                requiresService,
                resetFilters,
                searchQuery,
                searchRole,
                searchService,
                visibleUsersCount,
            };
        }
    }).mount('#users-app');
</script>
@endpush
