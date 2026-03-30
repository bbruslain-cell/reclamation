@extends('admin.layout')

@section('title', 'Tableau de bord')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-sky-100 bg-[linear-gradient(135deg,_rgba(57,150,211,0.10),_rgba(255,255,255,0.95))] px-5 py-4 text-center shadow-soft">
        <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Utilisateurs</p>
        <p class="mt-2 text-3xl font-semibold text-navy" data-countup="{{ (int) ($counts['utilisateurs'] ?? 0) }}">0</p>
    </div>
    <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-leaf/30 bg-[linear-gradient(135deg,_rgba(143,192,67,0.12),_rgba(255,255,255,0.95))] px-5 py-4 text-center shadow-soft">
        <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Directions</p>
        <p class="mt-2 text-3xl font-semibold text-navy" data-countup="{{ (int) ($counts['directions'] ?? 0) }}">0</p>
    </div>
    <div class="flex min-h-[124px] flex-col items-center justify-center rounded-2xl border border-gold/30 bg-[linear-gradient(135deg,_rgba(249,177,60,0.12),_rgba(255,255,255,0.95))] px-5 py-4 text-center shadow-soft">
        <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Services</p>
        <p class="mt-2 text-3xl font-semibold text-navy" data-countup="{{ (int) ($counts['services'] ?? 0) }}">0</p>
    </div>
</div>

<section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-soft">
    <div class="border-b border-neutral-200 px-5 py-4">
        <p class="text-[11px] uppercase tracking-[0.18em] text-neutral-400">Audit</p>
        <h2 class="mt-1 text-lg font-semibold text-navy">Historique des actions admin</h2>
        <p class="mt-1 text-sm text-neutral-500">Créations, modifications, suppressions et changements faits dans l’espace d’administration.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-200 bg-neutral-50">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Date et heure</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Admin responsable</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Action</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Détails</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200">
            @forelse($recentLogs as $log)
                <tr class="trow bg-white transition-colors">
                    <td class="px-5 py-4 font-mono text-xs text-neutral-600">{{ \Carbon\Carbon::parse($log->date_action)->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-4">
                        <div>
                            <p class="text-sm font-medium text-navy">{{ $log->admin_display_name }}</p>
                            <p class="text-xs text-neutral-400">
                                U-{{ str_pad((string) $log->id_utilisateur, 2, '0', STR_PAD_LEFT) }}
                                @if($log->admin_email)
                                    · {{ $log->admin_email }}
                                @endif
                            </p>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] font-medium text-sky">
                            {{ $log->action_libelle }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-neutral-500">{{ $log->commentaire ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center text-sm text-neutral-400">Aucune action admin enregistrée pour le moment.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const counters = Array.from(document.querySelectorAll('[data-countup]'));
        if (counters.length === 0) {
            return;
        }

        const animateCounter = (element, index) => {
            const target = Number.parseInt(element.dataset.countup || '0', 10);
            if (!Number.isFinite(target) || target <= 0) {
                element.textContent = '0';
                return;
            }

            const duration = 850 + (index * 120);
            const startTime = performance.now();

            const step = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const value = Math.round(target * eased);

                element.textContent = new Intl.NumberFormat('fr-FR').format(value);

                if (progress < 1) {
                    requestAnimationFrame(step);
                    return;
                }

                element.textContent = new Intl.NumberFormat('fr-FR').format(target);
            };

            requestAnimationFrame(step);
        };

        window.addEventListener('load', () => {
            counters.forEach((element, index) => animateCounter(element, index));
        }, { once: true });
    })();
</script>
@endpush
