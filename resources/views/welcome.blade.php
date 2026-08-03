@extends('layouts.app')

@section('title', config('app.name', 'ANBG'))
@section('body_class', 'min-h-screen bg-neutral-50 font-sans text-navy-500 antialiased')

@section('content')
<main class="min-h-screen flex items-center justify-center px-4 py-12">
    <section class="w-full max-w-2xl rounded-2xl border border-neutral-200 bg-white p-8 text-center shadow-card">
        <img src="{{ asset('Logo_anbg.png') }}" alt="ANBG" class="mx-auto h-16 w-auto object-contain">
        <h1 class="mt-6 text-2xl font-semibold text-navy-500">Plateforme Réclamation ANBG</h1>
        <p class="mt-3 text-sm leading-6 text-neutral-500">
            Accédez au formulaire public de réclamation ou à l'espace interne selon votre profil.
        </p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ url('/reclamations/nouvelle') }}" class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-5 py-3 text-sm font-medium text-white shadow-btn transition-colors hover:bg-navy-600">
                Formulaire public
            </a>
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-200 bg-white px-5 py-3 text-sm font-medium text-navy-500 transition-colors hover:border-sky-300 hover:text-sky-500">
                Espace agents
            </a>
        </div>
    </section>
</main>
@endsection
