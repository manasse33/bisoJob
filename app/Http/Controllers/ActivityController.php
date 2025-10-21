<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Marquer une activité comme lue
     * Route: POST /activities/{id}/mark-read
     */
    public function markAsRead($id)
    {
        try {
            $activity = Activity::findOrFail($id);
            
            // Vérifier que l'activité appartient à l'utilisateur connecté
            if ($activity->utilisateur_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            $activity->update([
                'is_unread' => false, 
                'read_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activité marquée comme lue',
                'data' => $activity
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer toutes les activités
     * Route: GET /activities
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $onlyUnread = $request->input('unread', false);

            $query = Activity::where('utilisateur_id', Auth::id())
                ->orderBy('created_at', 'desc');

            if ($onlyUnread) {
                $query->where('is_unread', true);
            }

            $activities = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer toutes les activités comme lues
     * Route: POST /activities/mark-all-read
     */
    public function markAllAsRead()
    {
        try {
            Activity::where('utilisateur_id', Auth::id())
                ->where('is_unread', true)
                ->update([
                    'is_unread' => false, 
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Toutes les activités marquées comme lues'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}