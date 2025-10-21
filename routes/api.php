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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::prefix('v1')->group(function () {
    
    // Authentification
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
    
    // Freelances (lecture publique)
    Route::get('/freelances', [FreelanceController::class, 'index']);
    Route::get('/freelances/{id}', [FreelanceController::class, 'show']);
    Route::get('/freelances/top/mieux-notes', [FreelanceController::class, 'topFreelances']);
    
    // Projets (lecture publique)
    Route::get('/projets', [ProjetController::class, 'index']);
    Route::get('/projets/{id}', [ProjetController::class, 'show']);
    
    // Avis (lecture publique)
    // Route::get('/freelances/{freelance_id}/avis', [AvisController::class, 'index']);
    
    // Catégories
    Route::post('/categories', [CategorieController::class, 'store']);
    Route::get('/categories', [CategorieController::class, 'index']);
    Route::get('/categories/{id}', [CategorieController::class, 'show']);
    Route::get('/categories/statistiques/all', [CategorieController::class, 'statistiques']);
    
    // Statistiques publiques
    Route::get('/statistiques/globales', [StatistiqueController::class, 'globales']);
    Route::get('/statistiques/categories', [StatistiqueController::class, 'parCategorie']);
    Route::get('/statistiques/villes', [StatistiqueController::class, 'parVille']);
    Route::get('/statistiques/top-freelances', [StatistiqueController::class, 'topFreelances']);
    Route::middleware('auth:sanctum')->get('/dashboard', [StatistiqueController::class, 'getDashboard']);
    
    // Webhook paiements
    // Route::post('/paiements/webhook', [PaiementController::class, 'webhook']);
});

// Routes protégées (nécessitent authentification)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    

     Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities/{id}/mark-read', [ActivityController::class, 'markAsRead']);
    Route::post('/activities/mark-all-read', [ActivityController::class, 'markAllAsRead']);
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
   Route::get('/user/profile/{id}', [AuthController::class, 'me']);

    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    
    // Gestion du profil freelance
    Route::prefix('freelance')->group(function () {
        Route::put('/profile', [FreelanceController::class, 'update']);
        // Route::get('/statistiques', [FreelanceController::class, 'statistiques']);
        
        // Compétences
        Route::post('/competences', [FreelanceController::class, 'addCompetence']);
        Route::delete('/competences/{id}', [FreelanceController::class, 'deleteCompetence']);
        
        // Portofolio
        Route::post('/Portofolios', [FreelanceController::class, 'addPortofolio']);
        Route::delete('/Portofolios/{id}', [FreelanceController::class, 'deletePortofolio']);
        
        // Contacts reçus
        // Route::get('/contacts', [ContactController::class, 'mesContacts']);
    });
     Route::middleware('auth:sanctum')->group(function () {
    // Gestion des projets (clients)
    Route::prefix('clients')->group(function () {
        Route::post('/projets', [ProjetController::class, 'store']);
        Route::put('/projets/{id}', [ProjetController::class, 'update']);
        Route::delete('/projets/{id}', [ProjetController::class, 'destroy']);
        Route::get('/mes-projets', [ProjetController::class, 'mesProjets']);
        Route::put('/projets/{id}/cloturer', [ProjetController::class, 'cloturer']);
    });
});

// Gestion des avis
Route::prefix('avis')->group(function () {
    Route::post('/', [AvisController::class, 'store']);
    Route::put('/{id}', [AvisController::class, 'update']);
    Route::delete('/{id}', [AvisController::class, 'destroy']);
        Route::get('/mes-avis', [AvisController::class, 'mesAvis']);
        Route::post('/{id}/signaler', [AvisController::class, 'signaler']);
    });
    
    // Gestion des contacts
    // Route::prefix('contacts')->group(function () {
    //     Route::post('/', [ContactController::class, 'store']);
    //     Route::get('/historique', [ContactController::class, 'historiqueContacts']);
    // });
    
    // Gestion des paiements
    // Route::prefix('paiements')->group(function () {
    //     Route::post('/', [PaiementController::class, 'store']);
    //     Route::get('/', [PaiementController::class, 'index']);
    //     Route::get('/{id}', [PaiementController::class, 'show']);
    //     Route::get('/{id}/verifier-statut', [PaiementController::class, 'verifierStatut']);
    // });
     // Gestion des notifications
   Route::middleware('auth:sanctum')->group(function () {
    
    Route::prefix('notifications')->group(function () {
        // Lister toutes les notifications
        Route::get('/', [NotificationController::class, 'index']);
        
        // Lister les notifications non lues
        Route::get('/non-lues', [NotificationController::class, 'nonLues']);
        
        // Compter les notifications non lues
        Route::get('/count-unread', [NotificationController::class, 'countUnread']);
        
        // Marquer une notification comme lue
        Route::put('/{id}/lire', [NotificationController::class, 'marquerCommeLu']);
        
        // Marquer toutes les notifications comme lues
        Route::put('/lire-tout', [NotificationController::class, 'marquerToutCommeLu']);
        
        // Supprimer une notification
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        
        // Supprimer plusieurs notifications
        Route::post('/delete-multiple', [NotificationController::class, 'destroyMultiple']);
    });
    
});
    
    // Gestion des favoris (clients)
    // Route::prefix('favoris')->group(function () {
    //     Route::get('/', [FavoriController::class, 'index']);
    //     Route::post('/{freelance_id}', [FavoriController::class, 'store']);
    //     Route::delete('/{freelance_id}', [FavoriController::class, 'destroy']);
    //     Route::get('/{freelance_id}/check', [FavoriController::class, 'check']);
    // });

    Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/users', [AuthController::class, 'getUsers']);
    Route::patch('/admin/users/{id}/status', [AuthController::class, 'updateUserStatus']);
});
});






// ALTER TABLE competences
// ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
// ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
