<?php

namespace App\services;
use App\Models\Utilisateur as User;
use App\Models\Notification;

class NotificationService
{
    /**
     * Créer une notification pour un ou plusieurs utilisateurs
     * 
     * @param int|array $userIds - ID(s) de l'utilisateur(s) destinataire(s)
     * @param string $titre - Titre de la notification
     * @param string $message - Message de la notification
     * @param string $type - Type (projet, message, etc.)
     * @param array $data - Données additionnelles (lien, ID de projet, etc.)
     */
    public static function create($userIds, $titre, $message, $type = 'info', $data = [])
    {
        $userIds = is_array($userIds) ? $userIds : [$userIds];

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'titre' => $titre,
                'message' => $message,
                'type' => $type,
                'data' => json_encode($data),
                'est_lue' => false,
                'created_at' => now()
            ]);
        }
    }

    /**
     * Notifier tous les utilisateurs d'un projet
     */
    public static function notifyProjectUsers($project, $excludeUserId = null, $titre, $message, $type = 'projet')
    {
        $userIds = $project->users()->pluck('id')->toArray();
        
        if ($excludeUserId) {
            $userIds = array_filter($userIds, fn($id) => $id != $excludeUserId);
        }

        self::create($userIds, $titre, $message, $type, [
            'projet_id' => $project->id,
            'url' => "/projects/{$project->id}"
        ]);
    }

    /**
     * Notifier un utilisateur d'un nouveau message
     */
    public static function notifyNewMessage($senderId, $recipientId, $message)
    {
        $sender = User::find($senderId);
        
        self::create($recipientId, 
            "Nouveau message de {$sender->prenom} {$sender->nom}",
            $message->contenu,
            'message',
            [
                'message_id' => $message->id,
                'sender_id' => $senderId,
                'url' => "/messages/{$message->id}"
            ]
        );
    }

    /**
     * Notifier d'un changement de statut de projet
     */
    public static function notifyProjectStatusChange($project, $oldStatus, $newStatus, $changedBy)
    {
        $statusTexts = [
            'en_attente' => 'en attente',
            'en_cours' => 'en cours',
            'termine' => 'terminé',
            'annule' => 'annulé'
        ];

        $titre = "Le projet \"{$project->titre}\" est maintenant {$statusTexts[$newStatus]}";
        $message = "Changement de statut effectué par {$changedBy->prenom} {$changedBy->nom}";

        self::notifyProjectUsers($project, $changedBy->id, $titre, $message, 'projet');
    }

    /**
     * Notifier d'une nouvelle offre sur un projet
     */
    public static function notifyNewOffer($offer, $project)
    {
        $freelancer = $offer->freelancer;
        
        self::create($project->client_id,
            "Nouvelle offre de {$freelancer->prenom} {$freelancer->nom}",
            "Un freelance a soumis une offre pour votre projet \"{$project->titre}\"",
            'projet',
            [
                'offer_id' => $offer->id,
                'project_id' => $project->id,
                'url' => "/projects/{$project->id}/offers"
            ]
        );
    }

    /**
     * Notifier d'une offre acceptée
     */
    public static function notifyOfferAccepted($offer, $project)
    {
        $freelancer = $offer->freelancer;
        
        self::create($freelancer->id,
            "Votre offre a été acceptée !",
            "Félicitations ! Votre offre pour le projet \"{$project->titre}\" a été acceptée.",
            'projet',
            [
                'offer_id' => $offer->id,
                'project_id' => $project->id,
                'url' => "/projects/{$project->id}"
            ]
        );
    }

    /**
     * Notifier d'une offre rejetée
     */
    public static function notifyOfferRejected($offer, $project)
    {
        $freelancer = $offer->freelancer;
        
        self::create($freelancer->id,
            "Votre offre a été rejetée",
            "Malheureusement, votre offre pour le projet \"{$project->titre}\" n'a pas été retenue.",
            'projet',
            [
                'offer_id' => $offer->id,
                'project_id' => $project->id
            ]
        );
    }

    /**
     * Notifier d'une revue/notation
     */
    public static function notifyNewReview($review, $user)
    {
        $reviewer = $review->reviewer;
        
        self::create($user->id,
            "{$reviewer->prenom} {$reviewer->nom} vous a noté",
            "{$reviewer->prenom} a laissé une revue de {$review->note} étoiles",
            'info',
            [
                'review_id' => $review->id,
                'reviewer_id' => $reviewer->id,
                'url' => "/profile/{$user->id}"
            ]
        );
    }
}
