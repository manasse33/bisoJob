<?php
// app/Http/Controllers/Api/CategorieController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category as Categorie;
use App\Models\Project as Projet;
use Illuminate\Http\Request;

class CategorieController extends Controller
{

    /**
 * Ajouter une nouvelle catégorie
 */
public function store(Request $request)
{
    // Validation des données
    $validated = $request->validate([
        'nom' => 'required|string|max:100|unique:categories,nom',
        'description' => 'nullable|string',
        'icone' => 'nullable|string|max:50',
        'ordre_affichage' => 'nullable|integer',
        'est_active' => 'nullable|boolean',
    ]);

    // Création de la catégorie
    $categorie = Categorie::create([
        'nom' => $validated['nom'],
        'description' => $validated['description'] ?? null,
        'icone' => $validated['icone'] ?? null,
        'ordre_affichage' => $validated['ordre_affichage'] ?? 0,
        'est_active' => $validated['est_active'] ?? 1,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Catégorie créée avec succès',
        'data' => $categorie
    ], 201);
}

    /**
     * Liste des catégories actives
     */
    public function index()
    {
        $categories = Categorie::active()
            ->ordonnee()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Détails d'une catégorie avec ses freelances
     */
    public function show($id)
    {
        $categorie = Categorie::findOrFail($id);

        $freelances = \App\Models\Freelance::with(['user', 'competences'])
            ->whereHas('user', function($q) {
                $q->where('statut', 'actif');
            })
            ->where('categorie', $categorie->nom)
            ->orderBy('est_en_vedette', 'desc')
            ->orderBy('note_moyenne', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'categorie' => $categorie,
                'freelances' => $freelances,
            ]
        ]);
    }

    /**
     * Statistiques par catégorie
     */
    public function statistiques()
    {
        $stats = Categorie::active()
            ->get()
            ->map(function($categorie) {
                $nombre_freelances = \App\Models\Freelance::where('categorie', $categorie->nom)
                    ->whereHas('user', function($q) {
                        $q->where('statut', 'actif');
                    })
                    ->count();

                $nombre_projets = Projet::where('categorie', $categorie->nom)
                    ->where('statut', 'ouvert')
                    ->count();

                return [
                    'categorie' => $categorie,
                    'nombre_freelances' => $nombre_freelances,
                    'nombre_projets' => $nombre_projets,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}