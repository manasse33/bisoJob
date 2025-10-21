<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Avis extends Model
{
    use HasFactory;

    // Nom de la table (si différent du pluriel par défaut)
    protected $table = 'avis';

    // Clé primaire si autre que "id"
    protected $primaryKey = 'id';

    // Si tu utilises des UUID ou autre type
    // public $incrementing = false;
    // protected $keyType = 'string';

    // Colonnes qui peuvent être remplies en masse
    protected $fillable = [
        'freelance_id',
        'client_id',
        'projet_id',
        'note',
        'commentaire',
        'date_creation',
        'statut',
    ];

    // Si tu veux gérer automatiquement les timestamps created_at et updated_at
    public $timestamps = false; // ou true si tu veux Laravel gère created_at et updated_at

    /**
     * Relations
     */

    // Un avis appartient à un freelance
    public function freelance()
    {
        return $this->belongsTo(Freelance::class);
    }

    // Un avis appartient à un client
     public function client()
    {
        return $this->belongsTo(Utilisateur::class, 'client_id');
    }


    // Un avis appartient à un projet
    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }
}
