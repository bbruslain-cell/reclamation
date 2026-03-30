<?php

use App\Http\Controllers\Api\DemandWorkflowController;
use App\Http\Controllers\Api\OverviewController;
use Illuminate\Support\Facades\Route;

Route::get('/overview', [OverviewController::class, 'index']);

Route::get('/demandes', [DemandWorkflowController::class, 'index']);
Route::get('/demandes/{id}', [DemandWorkflowController::class, 'show']);
Route::put('/demandes/{id}/affecter', [DemandWorkflowController::class, 'affecter']);
Route::put('/demandes/{id}/affecter-agent', [DemandWorkflowController::class, 'affecterAgent']);
Route::put('/demandes/{id}/annuler-affectation-agent', [DemandWorkflowController::class, 'annulerAffectationAgent']);
Route::put('/demandes/{id}/rediger-reponse', [DemandWorkflowController::class, 'redigerReponse']);
Route::put('/demandes/{id}/envoyer-reponse', [DemandWorkflowController::class, 'envoyerReponse']);
Route::put('/demandes/{id}/reouvrir', [DemandWorkflowController::class, 'reouvrir']);
