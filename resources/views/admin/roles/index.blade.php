@extends('admin.layout')

@section('title', 'Gestion des rôles')

@section('content')
<div class="overflow-hidden rounded-2xl border border-neutral-100 bg-white shadow-card">
    <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
        <div class="flex items-center gap-2">
            <i class="fas fa-key text-sm text-sky"></i>
            <h2 class="text-sm font-medium text-navy">Rôles système</h2>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 border-b border-neutral-100 px-5 py-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($roles as $role)
        <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-sky-50">
                <i class="fas fa-key text-xs text-sky"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-navy">{{ $role->libelle }}</p>
                <p class="font-mono text-[10px] text-neutral-400">{{ $role->code }}</p>
                <p class="mt-0.5 text-[10px] text-neutral-500"><span class="font-medium text-navy">{{ $rolesCount[$role->id_role] ?? 0 }}</span> utilisateur(s)</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="border-b border-neutral-100 px-5 py-5">
        <h3 class="mb-3 text-xs font-medium uppercase tracking-widest text-neutral-400">Modifier un rôle existant</h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($roles as $role)
            <form method="post" action="/admin/roles/{{ $role->id_role }}" class="space-y-2.5 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                @csrf
                @method('put')
                <div>
                    <label class="mb-1 block text-xs font-medium text-navy">Code (fixe)</label>
                    <input value="{{ $role->code }}" disabled class="w-full rounded-lg border border-neutral-200 bg-neutral-100 px-3 py-2 text-xs font-mono text-neutral-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-navy">Libellé</label>
                    <input name="libelle" value="{{ $role->libelle }}" required class="field w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-xs text-navy transition-all duration-150">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-navy">Statut</label>
                    <select name="actif" class="field w-full appearance-none rounded-lg border border-neutral-200 bg-white px-3 py-2 text-xs text-navy transition-all duration-150">
                        <option value="1" {{ $role->actif ? 'selected' : '' }}>Actif</option>
                        <option value="0" {{ !$role->actif ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
                <button type="submit" class="mt-1 w-full rounded-lg bg-navy py-2 text-xs font-medium text-white transition-colors duration-150 hover:bg-navy-600">
                    <i class="fas fa-save mr-1.5 text-[10px]"></i> Sauvegarder
                </button>
            </form>
            @endforeach
        </div>
    </div>
</div>
@endsection
