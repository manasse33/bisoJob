<?php
// app/Http/Controllers/NotificationController.php

namespace App\Http\Controllers;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Liste de toutes les notifications de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = Notification::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des notifications non lues
     */
    // public function nonLues(Request $request): JsonResponse
    // {
    //     try {
    //         $notifications = Notification::where('user_id', $request->user()->id)
    //             ->scopeNonLu()  // Utiliser le scope correctement
    //             ->orderBy('created_at', 'desc')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $notifications,
    //             'count' => $notifications->count()
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors du chargement des notifications non lues: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Nombre de notifications non lues (pour badge)
     */
    public function countUnread(Request $request): JsonResponse
    {
        try {
            $count = Notification::where('user_id', $request->user()->id)
                ->scopeNonLu()
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerCommeLu($id, Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $notification->marquerCommeLu();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue',
                'data' => $notification
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutCommeLu(Request $request): JsonResponse
    {
        try {
            $updated = Notification::where('user_id', $request->user()->id)
                ->scopeNonLu()
                ->update(['est_lue' => true]);

            return response()->json([
                'success' => true,
                'message' => "Toutes les notifications ont été marquées comme lues ($updated)",
                'data' => [
                    'updated' => $updated
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy($id, Request $request): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer plusieurs notifications
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune notification sélectionnée'
                ], 400);
            }

            $deleted = Notification::where('user_id', $request->user()->id)
                ->whereIn('id', $ids)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "$deleted notification(s) supprimée(s)",
                'data' => [
                    'deleted' => $deleted
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}