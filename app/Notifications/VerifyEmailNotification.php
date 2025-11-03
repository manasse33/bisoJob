<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // <<-- AJOUTÉ: Interface pour la queue
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue // <<-- AJOUTÉ: Implémente l'interface pour la mise en file d'attente
{
    use Queueable;

    protected $verificationUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($verificationUrl)
    {
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Envoie l'e-mail via le système de queue (ShouldQueue)
        return ['mail']; 
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Vérifiez votre adresse email - BisoJob')
            ->greeting('Bonjour ' . $notifiable->prenom . ' ! 🎉')
            ->line('Bienvenue sur BisoJob !')
            ->line('Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse email.')
            ->action('Vérifier mon email', $this->verificationUrl)
            ->line('Ce lien expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas créé de compte, ignorez cet email.')
            ->salutation('Cordialement, L\'équipe BisoJob');
    }
}
