<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FreelanceController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\ActivityController;

/*
|--------------------------------------------------------------------------
| API Routes BisoJob v1
|--------------------------------------------------------------------------
|
| Toutes les routes sont préfixées par 'v1'.
|
*/

// Route de test d'authentification de base
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// --- GROUPE V1 : Routes Publiques (sans authentification) ---
Route::prefix('v1')->group(function () {
    
    //  AUTHENTIFICATION (Limitation de Taux CRITIQUE)
    // Limite à 5 tentatives par minute par IP pour éviter la force brute et le spam.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
    });
    
    // 💡 LECTURE PUBLIQUE (Ne nécessite PAS d'authentification)
    
    // Freelances
    Route::get('/freelances', [FreelanceController::class, 'index']);
    Route::get('/freelances/{id}', [FreelanceController::class, 'show']);
    Route::get('/freelances/top/mieux-notes', [FreelanceController::class, 'topFreelances']);
    
    // Projets
    Route::get('/projets', [ProjetController::class, 'index']);
    Route::get('/projets/{id}', [ProjetController::class, 'show']);
    
    // Catégories (Lecture et Création pour l'Admin, mais si 'store' est ici, elle est publique !)
    // J'ai déplacé la création vers le groupe authentifié (voir ci-dessous)
    Route::get('/categories', [CategorieController::class, 'index']);
    Route::get('/categories/{id}', [CategorieController::class, 'show']);
    // J'ai corrigé cette route si elle est publique :
    Route::get('/categories/statistiques/all', [CategorieController::class, 'statistiques']);
    
    // Statistiques publiques
    Route::get('/statistiques/globales', [StatistiqueController::class, 'globales']);
    Route::get('/statistiques/categories', [StatistiqueController::class, 'parCategorie']);
    Route::get('/statistiques/villes', [StatistiqueController::class, 'parVille']);
    Route::get('/statistiques/top-freelances', [StatistiqueController::class, 'topFreelances']);
    
    // Webhook paiements (Doit être public pour que la plateforme de paiement puisse l'appeler)
    // Route::post('/paiements/webhook', [PaiementController::class, 'webhook']);
});


// 🔒 GROUPE V1 : Routes Protégées (Nécessitent auth:sanctum) 
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // AUTHENTIFICATION ET PROFIL
    Route::post('/logout', [AuthController::class, 'logout']);
    // Profil de l'utilisateur connecté (utilisation de /user pour le profil complet)
    Route::get('/user/profile/{id}', [AuthController::class, 'me']); 
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    
    // DASHBOARD & NOTIFICATIONS
    Route::get('/dashboard', [StatistiqueController::class, 'getDashboard']);
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities/{id}/mark-read', [ActivityController::class, 'markAsRead']);
    Route::post('/activities/mark-all-read', [ActivityController::class, 'markAllAsRead']);
    
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/non-lues', [NotificationController::class, 'nonLues']);
        Route::get('/count-unread', [NotificationController::class, 'countUnread']);
        Route::put('/{id}/lire', [NotificationController::class, 'marquerCommeLu']);
        Route::put('/lire-tout', [NotificationController::class, 'marquerToutCommeLu']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::post('/delete-multiple', [NotificationController::class, 'destroyMultiple']);
    });

    // GESTION DES FREELANCES (Freelance Profile Management)
    Route::prefix('freelance')->group(function () {
        Route::put('/profile', [FreelanceController::class, 'update']);
        
        // Compétences
        Route::post('/competences', [FreelanceController::class, 'addCompetence']);
        Route::delete('/competences/{id}', [FreelanceController::class, 'deleteCompetence']);
        
        // Portofolio
        Route::post('/Portofolios', [FreelanceController::class, 'addPortofolio']);
        Route::delete('/Portofolios/{id}', [FreelanceController::class, 'deletePortofolio']);
    });
    
    // GESTION DES CLIENTS (Projets)
    Route::prefix('clients')->group(function () {
        Route::post('/projets', [ProjetController::class, 'store']);
        Route::put('/projets/{id}', [ProjetController::class, 'update']);
        Route::delete('/projets/{id}', [ProjetController::class, 'destroy']);
        Route::get('/mes-projets', [ProjetController::class, 'mesProjets']);
        Route::put('/projets/{id}/cloturer', [ProjetController::class, 'cloturer']);
    });

    // GESTION DES AVIS
    Route::prefix('avis')->group(function () {
        Route::post('/', [AvisController::class, 'store']); // Créer un avis
        Route::put('/{id}', [AvisController::class, 'update']);
        Route::delete('/{id}', [AvisController::class, 'destroy']);
        Route::get('/mes-avis', [AvisController::class, 'mesAvis']);
        Route::post('/{id}/signaler', [AvisController::class, 'signaler']);
    });
    
    // GESTION DES FAVORIS
    // Route::prefix('favoris')->group(function () {
    //     Route::get('/', [FavoriController::class, 'index']);
    //     Route::post('/{freelance_id}', [FavoriController::class, 'store']);
    //     Route::delete('/{freelance_id}', [FavoriController::class, 'destroy']);
    //     Route::get('/{freelance_id}/check', [FavoriController::class, 'check']);
    // });
    
    // GESTION DES CATÉGORIES (Administrateur/Gestionnaire)
    // J'ai déplacé ici la route de création de catégorie pour la protéger.
    Route::post('/categories', [CategorieController::class, 'store']);
    
    // ROUTES D'ADMINISTRATION
    // Ces routes nécessitent d'être protégées par un middleware supplémentaire 
    // qui vérifie que l'utilisateur a le rôle 'admin' (ex: ->middleware('can:manage-users'))
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getUsers']);
        Route::patch('/users/{id}/status', [AuthController::class, 'updateUserStatus']);
    });

    // TODO: Décommenter et sécuriser les routes restantes (Contacts, Paiements, etc.)
    // ...
});
