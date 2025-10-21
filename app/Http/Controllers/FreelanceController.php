<?php
// app/Http/Controllers/Api/FreelanceController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Freelance;
use App\Models\Competence;
use App\Models\Portofolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FreelanceController extends Controller
{
    /**
     * Liste des freelances avec filtres
     */
    public function index(Request $request)
    {
        $query = Freelance::with(['user', 'competences','Portofolios'])
            ->whereHas('user', function($q) {
                $q->where('statut', 'actif');
            });

        // Filtres
        if ($request->has('categorie') && $request->categorie !== 'Tous') {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('ville')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('ville', $request->ville);
            });
        }

        if ($request->has('disponibilite')) {
            $query->where('disponibilite', $request->disponibilite);
        }

        if ($request->has('recherche')) {
            $recherche = $request->recherche;
            $query->where(function($q) use ($recherche) {
                $q->where('titre_professionnel', 'like', "%{$recherche}%")
                  ->orWhere('biographie', 'like', "%{$recherche}%");
            });
        }

        // Tri
        $query->orderBy('est_en_vedette', 'desc')
              ->orderBy('note_moyenne', 'desc')
              ->orderBy('nombre_avis', 'desc');

        $freelances = $query->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $freelances
        ]);
    }

    /**
     * Détails d'un freelance
     */
    public function show($id)
    {
        $freelance = Freelance::with([
            'user',
            'competences',
            'Portofolios',
            'avis.client'
        ])->findOrFail($id);

        // Incrémenter les vues
        $freelance->incrementerVuesProfil();

        return response()->json([
            'success' => true,
            'data' => $freelance
        ]);
    }

    /**
     * Mise à jour du profil freelance
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'freelance') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $freelance = $user->freelance;

        $validator = Validator::make($request->all(), [
            'titre_professionnel' => 'sometimes|string|max:150',
            'biographie' => 'nullable|string',
            'categorie' => 'sometimes|string|max:100',
            'sous_categorie' => 'nullable|string|max:100',
            'annees_experience' => 'nullable|integer|min:0',
            'tarif_minimum' => 'nullable|numeric|min:0',
            'tarif_maximum' => 'nullable|numeric|min:0',
            'disponibilite' => 'sometimes|in:disponible,occupe,indisponible',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $freelance->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Profil freelance mis à jour avec succès',
            'data' => $freelance->fresh()
        ]);
    }

    /**
     * Ajouter une compétence
     */
    public function addCompetence(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'freelance') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom_competence' => 'required|string|max:100',
            'niveau' => 'nullable|in:debutant,intermediaire,avance,expert',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $competence = Competence::create([
            'freelance_id' => $user->freelance->id,
            'nom_competence' => $request->nom_competence,
            'niveau' => $request->niveau?? 'debutant',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compétence ajoutée avec succès',
            'data' => $competence
        ], 201);
    }

    /**
     * Supprimer une compétence
     */
    public function deleteCompetence($id, Request $request)
    {
        $user = $request->user();
        
        $competence = Competence::where('freelance_id', $user->freelance->id)
            ->findOrFail($id);

        $competence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compétence supprimée avec succès'
        ]);
    }

    /**
     * Ajouter un élément au Portofolio
     */
    public function addPortofolio(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'freelance') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:200',
            'description' => 'nullable|string',
            'image' => 'nullable|file|max:5120',
            'lien_externe' => 'nullable|url',
            'date_realisation' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['titre', 'description', 'lien_externe', 'date_realisation']);
        $data['freelance_id'] = $user->freelance->id;

        // Gérer l'upload de l'image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('Portofolios', 'public');
            $data['image_url'] = $path;
        }

        $Portofolio = Portofolio::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Élément ajouté au Portofolio',
            'data' => $Portofolio
        ], 201);
    }

    /**
     * Supprimer un élément du Portofolio
     */
    public function deletePortofolio($id, Request $request)
    {
        $user = $request->user();
        
        $Portofolio = Portofolio::where('freelance_id', $user->freelance->id)
            ->findOrFail($id);

        // Supprimer l'image si elle existe
        if ($Portofolio->image_url) {
            \Storage::disk('public')->delete($Portofolio->image_url);
        }

        $Portofolio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Élément supprimé du Portofolio'
        ]);
    }

    /**
     * Statistiques du freelance
     */
    public function statistiques(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'freelance') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $freelance = $user->freelance;

        $stats = [
            'vues_profil' => $freelance->vues_profil,
            'contacts_recus' => $freelance->contacts_recus,
            'note_moyenne' => $freelance->note_moyenne,
            'nombre_avis' => $freelance->nombre_avis,
            'nombre_projets_realises' => $freelance->nombre_projets_realises,
            'nombre_competences' => $freelance->competences()->count(),
            'nombre_portfolios' => $freelance->Portofolios()->count(),
            'est_en_vedette' => $freelance->est_en_vedette,
            'date_fin_vedette' => $freelance->date_fin_vedette,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Top freelances
     */
    public function topFreelances()
    {
        $freelances = Freelance::with(['user', 'competences'])
            ->whereHas('user', function($q) {
                $q->where('statut', 'actif');
            })
            ->where('nombre_avis', '>=', 5)
            ->orderBy('note_moyenne', 'desc')
            ->orderBy('nombre_avis', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $freelances
        ]);
    }
}