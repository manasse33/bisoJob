<?php
// app/Http/Controllers/Api/PaiementController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaiementController extends Controller
{
    /**
     * Créer un paiement pour mise en vedette
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'freelance') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les freelances peuvent effectuer cette action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type_paiement' => 'required|in:vedette_7j,vedette_15j,vedette_30j',
            'methode_paiement' => 'required|in:airtel_money,mtn_money,virement,autre',
            'numero_telephone' => 'required_if:methode_paiement,airtel_money,mtn_money|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Tarifs selon le type
        $tarifs = [
            'vedette_7j' => 2500,
            'vedette_15j' => 4000,
            'vedette_30j' => 5000,
        ];

        $montant = $tarifs[$request->type_paiement];

        // Créer le paiement
        $paiement = Paiement::create([
            'freelance_id' => $user->freelance->id,
            'montant' => $montant,
            'devise' => 'FCFA',
            'type_paiement' => $request->type_paiement,
            'methode_paiement' => $request->methode_paiement,
            'reference_transaction' => 'BJ-' . strtoupper(Str::random(10)),
            'statut' => 'en_attente',
        ]);

        // TODO: Intégrer avec les API de paiement mobile (Airtel Money, MTN Money)
        // Pour l'instant, on simule une validation automatique en développement
        if (config('app.env') === 'local') {
            $paiement->valider();
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié avec succès',
            'data' => $paiement
        ], 201);
    }

    /**
     * Liste des paiements d'un freelance
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->type_utilisateur !== 'freelance') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $paiements = Paiement::where('freelance_id', $user->freelance->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $paiements
        ]);
    }

    /**
     * Détails d'un paiement
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        
        $paiement = Paiement::where('freelance_id', $user->freelance->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $paiement
        ]);
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function verifierStatut($id, Request $request)
    {
        $user = $request->user();
        
        $paiement = Paiement::where('freelance_id', $user->freelance->id)
            ->findOrFail($id);

        // TODO: Vérifier le statut auprès de l'API de paiement

        return response()->json([
            'success' => true,
            'data' => [
                'statut' => $paiement->statut,
                'reference' => $paiement->reference_transaction,
            ]
        ]);
    }

    /**
     * Webhook pour les notifications de paiement (Airtel, MTN, etc.)
     */
    public function webhook(Request $request)
    {
        // TODO: Valider la signature du webhook
        // TODO: Traiter la notification de paiement
        
        $reference = $request->reference_transaction;
        $statut = $request->statut; // 'success' ou 'failed'

        $paiement = Paiement::where('reference_transaction', $reference)->first();

        if (!$paiement) {
            return response()->json(['error' => 'Paiement non trouvé'], 404);
        }

        if ($statut === 'success') {
            $paiement->valider();
        } else {
            $paiement->refuser();
        }

        return response()->json(['success' => true]);
    }
}