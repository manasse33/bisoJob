<?php
// app/Http/Controllers/Api/ProjetController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project as Projet;
use App\Models\Utilisateur as User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Ajout pour le logging
use App\Services\NotificationService;

// NOTE IMPORTANTE : Ce contrôleur suppose que toutes les routes
// sont protégées par le middleware 'auth:sanctum' ou équivalent.

class ProjetController extends Controller
{
    /**
     * Liste des projets publics (ouverts) avec filtres
     */
    public function index(Request $request)
    {
        // Les requêtes sont sécurisées contre l'injection SQL grâce à Eloquent (requêtes préparées)
        $query = Projet::with(['client'])
            ->where('statut', 'ouvert');

        // Filtres
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('ville')) {
            $query->where('ville', $request->ville);
        }

        // 💡 Amélioration: utilisation de where('titre', 'like', $recherche) est sécurisé par Eloquent
        if ($request->has('recherche')) {
            $recherche = $request->recherche;
            $query->where(function($q) use ($recherche) {
                // Utilisation sécurisée des wildcards % dans Eloquent
                $q->where('titre', 'like', "%{$recherche}%")
                  ->orWhere('description', 'like', "%{$recherche}%");
            });
        }

        $projets = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $projets
        ], 200);
    }

    /**
     * Détails d'un projet
     */
    public function show($id)
    {
        try {
            // findOrFail renverra automatiquement une réponse 404 si non trouvé
            $projet = Projet::with(['client'])->findOrFail($id);

            // Incrémenter les vues (Assurez-vous que la méthode existe sur le modèle Projet)
            if (method_exists($projet, 'incrementerVues')) {
                $projet->incrementerVues();
            }

            return response()->json([
                'success' => true,
                'data' => $projet
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Projet introuvable.'
            ], 404);
        }
    }

    /**
     * Créer un projet
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        //  Protection (Policy): Vérification du type d'utilisateur
        if ($user->type_utilisateur !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les clients peuvent publier des projets'
            ], 403); // 403 Forbidden
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'categorie' => 'required|string|max:100',
            'budget_minimum' => 'nullable|numeric|min:0',
            // 💡 Ajout : Assurer que le maximum est supérieur ou égal au minimum si les deux sont présents
            'budget_maximum' => 'nullable|numeric|min:0|gte:budget_minimum',
            'ville' => 'nullable|string|max:100',
            'delai_souhaite' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $projet = Projet::create([
                'client_id' => $user->id,
                'titre' => $request->titre,
                'description' => $request->description,
                'categorie' => $request->categorie,
                'budget_minimum' => $request->budget_minimum,
                'budget_maximum' => $request->budget_maximum,
                'ville' => $request->ville,
                'delai_souhaite' => $request->delai_souhaite,
                'statut' => 'ouvert',
            ]);

            // ... Logique de notification commentée ...

            return response()->json([
                'success' => true,
                'message' => 'Projet publié avec succès',
                'data' => $projet
            ], 201); // 201 Created

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création du projet par le client {$user->id}: " . $e->getMessage());
            
            //  Sécurité : Ne pas exposer les erreurs internes
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue lors de la publication du projet.'
            ], 500);
        }
    }

    /**
     * Mettre à jour un projet
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        //  IDOR Protection : Vérifie si le projet appartient bien au client connecté
        try {
            $projet = Projet::where('client_id', $user->id)->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Renvoie 403 si l'utilisateur essaie de modifier un projet qui ne lui appartient pas (ou 404 si l'ID n'existe pas)
            return response()->json([
                'success' => false,
                'message' => 'Projet introuvable ou vous n\'avez pas les permissions pour le modifier.'
            ], 404);
        }
        
        // 💡 Validation améliorée pour permettre les mises à jour partielles
        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|string|max:200',
            'description' => 'sometimes|string',
            'categorie' => 'sometimes|string|max:100',
            'budget_minimum' => 'nullable|numeric|min:0',
            'budget_maximum' => 'nullable|numeric|min:0|gte:budget_minimum', // Validation gte:budget_minimum s'applique
            'ville' => 'nullable|string|max:100',
            'delai_souhaite' => 'nullable|string|max:100',
            'statut' => 'sometimes|in:ouvert,en_cours,termine,annule',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Utilisez $request->validated() pour n'appliquer que les champs validés
            $projet->update($validator->validated()); 

            return response()->json([
                'success' => true,
                'message' => 'Projet mis à jour avec succès',
                'data' => $projet->fresh()
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du projet {$id} par le client {$user->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue lors de la mise à jour.'
            ], 500);
        }
    }

    /**
     * Supprimer un projet
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        
        //  IDOR Protection : Vérifie si le projet appartient bien au client connecté
        try {
            $projet = Projet::where('client_id', $user->id)->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Projet introuvable ou vous n\'avez pas les permissions pour le supprimer.'
            ], 404);
        }

        $projet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ], 200);
    }

    /**
     * Mes projets (pour les clients)
     */
    public function mesProjets(Request $request)
    {
        $user = $request->user();
        
        //  Protection : seul le client peut voir "ses" projets
        if ($user->type_utilisateur !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $projets = Projet::where('client_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $projets
        ], 200);
    }

    /**
     * Clôturer un projet
     */
    public function cloturer($id, Request $request)
    {
        $user = $request->user();
        
        // IDOR Protection : Vérifie si le projet appartient bien au client connecté
        try {
            $projet = Projet::where('client_id', $user->id)->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Projet introuvable ou vous n\'avez pas les permissions pour le clôturer.'
            ], 404);
        }

        // Vérification de l'état actuel pour plus de robustesse
        if ($projet->statut === 'termine' || $projet->statut === 'annule') {
             return response()->json([
                'success' => false,
                'message' => "Le projet est déjà {$projet->statut}."
            ], 400); // 400 Bad Request
        }

        $projet->update([
            'statut' => 'termine',
            'date_cloture' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Projet clôturé avec succès',
            'data' => $projet
        ], 200);
    }
}

