<?php

use App\Http\Controllers\Api\OverviewController as ApiOverviewController;
use App\Http\Controllers\Web\AccueilInboxController;
use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Admin\AdminParamsController;
use App\Http\Controllers\Web\Admin\AdminReferentielController;
use App\Http\Controllers\Web\Admin\AdminRolesController;
use App\Http\Controllers\Web\Admin\AdminUsersController;
use App\Http\Controllers\Web\AgentInboxController;
use App\Http\Controllers\Web\AgentPortalController;
use App\Http\Controllers\Web\AttachmentController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChefInboxController;
use App\Http\Controllers\Web\DirectionInboxController;
use App\Http\Controllers\Web\PasswordController;
use App\Http\Controllers\Web\PublicDemandController;
use App\Services\AccessControlService;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/reclamations/nouvelle'));

// ---------------------------------------------------------------
// Routes PUBLIQUES (sans authentification)
// ---------------------------------------------------------------

// Formulaire public de réclamation (rate-limited)
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/reclamations/nouvelle', [PublicDemandController::class, 'create']);
    Route::post('/reclamations', [PublicDemandController::class, 'store']);
});

// Authentification
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout']);

// Mot de passe (oubli / réinitialisation)
Route::get('/mot-de-passe/oubli', [PasswordController::class, 'showForgot']);
Route::post('/mot-de-passe/oubli', [PasswordController::class, 'submitForgot'])->middleware('throttle:5,1');
Route::get('/mot-de-passe/reinitialiser', [PasswordController::class, 'showReset']);
Route::post('/mot-de-passe/reinitialiser', [PasswordController::class, 'submitReset']);

// ---------------------------------------------------------------
// Routes PROTÉGÉES (authentification obligatoire via agent.auth)
// ---------------------------------------------------------------

Route::middleware('agent.auth')->group(function () {

    // Pièces jointes
    Route::get('/pieces-jointes/{id}', [AttachmentController::class, 'show']);

    // Changement de mot de passe (première connexion)
    Route::get('/mot-de-passe/nouveau', [PasswordController::class, 'showChange']);
    Route::post('/mot-de-passe/nouveau', [PasswordController::class, 'submitChange']);

    // Portail agent (redirige selon rôle)
    Route::get('/espace', [AgentPortalController::class, 'index']);

    // Accueil
    Route::get('/accueil/inbox', [AccueilInboxController::class, 'index']);
    Route::put('/accueil/demandes/{id}/affecter', [AccueilInboxController::class, 'affecter']);
    Route::put('/accueil/demandes/{id}/reponse-directe', [AccueilInboxController::class, 'reponseDirecte']);

    // Chef de service
    Route::get('/chef/inbox', [ChefInboxController::class, 'index']);
    Route::put('/chef/demandes/{id}/affecter-agent', [ChefInboxController::class, 'affecterAgent']);
    Route::put('/chef/demandes/{id}/reponse-directe', [ChefInboxController::class, 'reponseDirecte']);
    Route::put('/chef/demandes/{id}/annuler-affectation-agent', [ChefInboxController::class, 'annulerAffectationAgent']);

    // Agent
    Route::get('/agent/inbox', [AgentInboxController::class, 'index']);
    Route::put('/agent/demandes/{id}/envoyer', [AgentInboxController::class, 'envoyer']);

    // Chef de direction
    Route::get('/chef-direction/inbox', [DirectionInboxController::class, 'index']);
    Route::redirect('/direction/inbox', '/chef-direction/inbox');
    Route::put('/chef-direction/demandes/{id}/rediger', [DirectionInboxController::class, 'rediger']);
    Route::put('/direction/demandes/{id}/rediger', [DirectionInboxController::class, 'rediger']);

    // Pilotage (CIQ / DG)
    Route::get('/pilotage', function (AccessControlService $access, \Illuminate\Http\Request $request) {
        $actor = $access->resolveActor($request);
        if (!$actor) {
            return redirect('/login');
        }
        $access->assertPermission((int) $actor->id_utilisateur, 'dashboard.view');
        $roleCodes = $access->roleCodes((int) $actor->id_utilisateur);
        $overviewResponse = app(ApiOverviewController::class)->index($request, $access);

        return view('pilotage-ciq', [
            'actor'        => $actor,
            'roleCodes'    => $roleCodes,
            'overviewData' => $overviewResponse->getData(true),
        ]);
    });
    Route::get('/pilotage/data', function (\Illuminate\Http\Request $request, AccessControlService $access) {
        $actor = $access->resolveActor($request);
        if (!$actor) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $access->assertPermission((int) $actor->id_utilisateur, 'dashboard.view');
        return app(ApiOverviewController::class)->index($request);
    });

    // Administration
    Route::get('/admin', [AdminDashboardController::class, 'index']);
    
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::post('/avatar', [AdminDashboardController::class, 'uploadAvatar']);
        Route::post('/avatar/remove', [AdminDashboardController::class, 'removeAvatar']);

        Route::get('/utilisateurs', [AdminUsersController::class, 'index']);
        Route::post('/utilisateurs', [AdminUsersController::class, 'storeUser']);
        Route::post('/utilisateurs/{id}/toggle', [AdminUsersController::class, 'toggleUser']);
        Route::post('/utilisateurs/{id}/reset-password', [AdminUsersController::class, 'resetUserPassword']);
        Route::post('/utilisateurs/{id}/delete', [AdminUsersController::class, 'deleteUser']);

        Route::get('/roles', [AdminRolesController::class, 'index']);
        Route::put('/roles/{id}', [AdminRolesController::class, 'updateRole']);
        Route::post('/roles/{id}/permissions', [AdminRolesController::class, 'syncRolePermissions']);

        Route::get('/directions', [AdminReferentielController::class, 'indexDirections']);
        Route::post('/directions', [AdminReferentielController::class, 'storeDirection']);
        Route::put('/directions/{id}', [AdminReferentielController::class, 'updateDirection']);

        Route::get('/services', [AdminReferentielController::class, 'indexServices']);
        Route::post('/services', [AdminReferentielController::class, 'storeService']);
        Route::put('/services/{id}', [AdminReferentielController::class, 'updateService']);

        Route::get('/parametres', [AdminParamsController::class, 'index']);
        Route::post('/parametres', [AdminParamsController::class, 'storeParametre']);
        Route::put('/parametres/{id}', [AdminParamsController::class, 'updateParametre']);
        Route::post('/jours-feries', [AdminParamsController::class, 'storeHoliday']);
        Route::put('/jours-feries/{id}', [AdminParamsController::class, 'updateHoliday']);
        Route::delete('/jours-feries/{id}', [AdminParamsController::class, 'deleteHoliday']);
    });

}); // fin middleware agent.auth
