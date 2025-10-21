<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NouveauProjetCree
{
   use InteractsWithSockets, SerializesModels;

    public $projet;

    public function __construct(Projet $projet)
    {
        $this->projet = $projet;
    }

    public function broadcastOn()
    {
        // Remplace freelance_id par l'ID du freelance concerné
        return new PrivateChannel('freelance.' . $this->projet->freelance_id);
    }
}
