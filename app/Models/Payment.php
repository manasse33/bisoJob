<?php
// app/Models/Paiement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected  $table = 'paiements';
    protected $fillable = [
        'freelance_id',
        'montant',
        'devise',
        'type_paiement',
        'methode_paiement',
        'reference_transaction',
        'statut',
        'date_validation',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_validation' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relations
    public function freelance()
    {
        return $this->belongsTo(Freelance::class);
    }

    // Scopes
    public function scopeValide($query)
    {
        return $query->where('statut', 'valide');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    // Methods
    public function valider()
    {
        $this->update([
            'statut' => 'valide',
            'date_validation' => now(),
        ]);

        // Activer la vedette selon le type
        $durees = [
            'vedette_7j' => 7,
            'vedette_15j' => 15,
            'vedette_30j' => 30,
        ];

        $duree = $durees[$this->type_paiement] ?? 30;
        $this->freelance->activerVedette($duree);
    }

    public function refuser()
    {
        $this->update([
            'statut' => 'echoue',
        ]);
    }
}