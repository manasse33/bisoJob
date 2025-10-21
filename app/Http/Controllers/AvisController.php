<?php
// app/Http/Controllers/Api/AvisController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\Freelance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvisController extends Controller
{
    /**
     * Liste des avis d'un freelance
     */
    public function index($freelance_id)
    {
        $avis = Avis::with(['client'])
            ->where('freelance_id', $freelance_id)
            ->where('statut', 'publie')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $avis
        ]);
    }

    /**
     * Créer un avis
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les clients peuvent laisser des avis'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'freelance_id' => 'required|exists:freelances,id',
            'projet_id' => 'nullable|exists:projets,id',
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur n'a pas déjà laissé un avis pour ce freelance
        $existingAvis = Avis::where('client_id', $user->id)
            ->where('freelance_id', $request->freelance_id)
            ->where('projet_id', $request->projet_id)
            ->first();

        if ($existingAvis) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà laissé un avis pour ce freelance sur ce projet'
            ], 422);
        }

        $avis = Avis::create([
            'client_id' => $user->id,
            'freelance_id' => $request->freelance_id,
            'projet_id' => $request->projet_id,
            'note' => $request->note,
            'commentaire' => $request->commentaire,
            'statut' => 'publie',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avis publié avec succès',
            'data' => $avis->load('client')
        ], 201);
    }

    /**
     * Mettre à jour un avis
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $avis = Avis::where('client_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'note' => 'sometimes|integer|min:1|max:5',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $avis->update($request->only(['note', 'commentaire']));
        return response()->json([
            'success' => true,
            'message' => 'Avis mis à jour avec succès',
            'data' => $avis->fresh()->load('client')
        ]);
    }

    /**
     * Supprimer un avis
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        
        $avis = Avis::where('client_id', $user->id)->findOrFail($id);

        $avis->delete();

        return response()->json([
            'success' => true,
            'message' => 'Avis supprimé avec succès'
        ]);
    }

    /**
     * Mes avis (pour les clients)
     */
    public function mesAvis(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $avis = Avis::with(['freelance.user'])
            ->where('client_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $avis
        ]);
    }

    /**
     * Signaler un avis
     */
    public function signaler($id, Request $request)
    {
        $avis = Avis::findOrFail($id);

        $avis->update(['statut' => 'signale']);

        return response()->json([
            'success' => true,
            'message' => 'Avis signalé avec succès'
        ]);
    }
}