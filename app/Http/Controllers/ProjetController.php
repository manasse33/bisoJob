<?php
// app/Http/Controllers/Api/ProjetController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project as Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjetController extends Controller
{
    /**
     * Liste des projets avec filtres
     */
    public function index(Request $request)
    {
        $query = Projet::with(['client'])
            ->where('statut', 'ouvert');

        // Filtres
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('ville')) {
            $query->where('ville', $request->ville);
        }

        if ($request->has('recherche')) {
            $recherche = $request->recherche;
            $query->where(function($q) use ($recherche) {
                $q->where('titre', 'like', "%{$recherche}%")
                  ->orWhere('description', 'like', "%{$recherche}%");
            });
        }

        $projets = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $projets
        ]);
    }

    /**
     * Détails d'un projet
     */
    public function show($id)
    {
        $projet = Projet::with(['client'])->findOrFail($id);

        // Incrémenter les vues
        $projet->incrementerVues();

        return response()->json([
            'success' => true,
            'data' => $projet
        ]);
    }

    /**
     * Créer un projet
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les clients peuvent publier des projets'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'categorie' => 'required|string|max:100',
            'budget_minimum' => 'nullable|numeric|min:0',
            'budget_maximum' => 'nullable|numeric|min:0',
            'ville' => 'nullable|string|max:100',
            'delai_souhaite' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

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

        

        return response()->json([
            'success' => true,
            'message' => 'Projet publié avec succès',
            'data' => $projet
        ], 201);
    }

    /**
     * Mettre à jour un projet
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $projet = Projet::where('client_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|string|max:200',
            'description' => 'sometimes|string',
            'categorie' => 'sometimes|string|max:100',
            'budget_minimum' => 'nullable|numeric|min:0',
            'budget_maximum' => 'nullable|numeric|min:0',
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

        $projet->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Projet mis à jour avec succès',
            'data' => $projet->fresh()
        ]);
    }

    /**
     * Supprimer un projet
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        
        $projet = Projet::where('client_id', $user->id)->findOrFail($id);

        $projet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }

    /**
     * Mes projets (pour les clients)
     */
    public function mesProjets(Request $request)
    {
        $user = $request->user();
        
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
        ]);
    }

    /**
     * Clôturer un projet
     */
    public function cloturer($id, Request $request)
    {
        $user = $request->user();
        
        $projet = Projet::where('client_id', $user->id)->findOrFail($id);

        $projet->update([
            'statut' => 'termine',
            'date_cloture' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Projet clôturé avec succès',
            'data' => $projet
        ]);
    }
}