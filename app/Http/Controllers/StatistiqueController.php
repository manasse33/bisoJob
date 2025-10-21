<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Utilisateur;
use App\Models\Freelance;
use App\Models\Payment;
use App\Models\Category as Categorie;

use Illuminate\Support\Facades\Auth;
use Exception;

class StatistiqueController extends Controller
{
    public function getDashboard(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié.'
                ], 401);
            }

            $role = $user->type_utilisateur;

            // Statistiques communes
            $commonStats = [
                'messages' => 0,
                'events' => 0,
            ];

            $data = [];

            // === ROLE FREELANCE ===
            if ($role === 'freelance') {
                $freelance = Freelance::where('utilisateur_id', $user->id)->first();

                if (!$freelance) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Freelance non trouvé.'
                    ], 404);
                }

                $categories = $freelance->categorie ? explode(',', $freelance->categorie) : [];

                $revenue = Payment::where('freelance_id', $user->id)->sum('montant');

                $projects = Project::whereIn('categorie', $categories)
                    ->latest()
                    ->take(5)
                    ->get();

                $activities = $projects->map(function ($project) {
                    return [
                        'title' => 'Projet disponible',
                        'description' => $project->titre ?? '—',
                        'time' => $project->created_at?->diffForHumans() ?? '',
                        'projet_id' => $project->id,
                        'icon' => 'briefcase',
                        'color' => 'blue',
                    ];
                });

                $data = [
                    'stats' => array_merge($commonStats, [
                        'projects' => $projects->count(),
                        'profileViews' => $freelance->vues_profil,
                        'revenue' => number_format($revenue, 0, ',', ' ') . ' FCFA',
                    ]),
                    'activities' => $activities,
                ];
            }

            // === ROLE CLIENT ===
            elseif ($role === 'client') {
                $projects = Project::where('client_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get();

                $activities = $projects->map(function ($project) {
                    return [
                        'title' => 'Projet publié',
                        'description' => $project->titre ?? '—',
                        'time' => $project->created_at?->diffForHumans() ?? '',
                        'icon' => 'folder',
                        'color' => 'purple',
                        'projet_id' => $project->id,
                    ];
                });

                $data = [
                    'stats' => array_merge($commonStats, [
                        'projects' => $projects->count(),
                        'revenue' => '—',
                    ]),
                    'activities' => $activities,
                ];
            }

            // === ROLE ADMIN ===
          // === ROLE ADMIN ===
else {
    $categories = Categorie::count();
    $totalProjects = Project::count();
    $totalFreelances = Freelance::count();
    $totalClients = Utilisateur::where('type_utilisateur', 'client')->count();
    $totalRevenu = Payment::sum('montant');

    // Activités récentes des utilisateurs (inscriptions)
    $activities = [
        [
            'title' => 'Nouvel utilisateur',
            'description' => 'Client inscrit : ' . $user->nom,
            'time' => 'il y a quelques instants',
            'icon' => 'user',
            'color' => 'purple',
        ],
    ];

    // Ajouter les 5 derniers projets publiés
    $recentProjects = Project::with('client')
        ->latest()
        ->take(5)
        ->get();

    foreach ($recentProjects as $project) {
        $activities[] = [
            'title' => 'Projet publié',
            'description' => 'Projet "' . $project->titre . '" publié par ' . ($project->client->nom ?? '—'),
            'time' => $project->created_at?->diffForHumans() ?? '',
            'projet_id' => $project->id,
            'icon' => 'folder',
            'color' => 'blue',
        ];
    }

    $data = [
        'stats' => array_merge($commonStats, [
            'projects' => $totalProjects,
            'freelances' => $totalFreelances,
            'clients' => $totalClients,
            'categories' => $categories,
            'revenue' => number_format($totalRevenu, 0, ',', ' ') . ' FCFA',
        ]),
        'activities' => $activities,
    ];
}

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage(),
            ], 500);
        }
    }
}
