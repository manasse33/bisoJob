<?php
// app/Models/Freelance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Freelance extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'titre_professionnel',
        'biographie',
        'categorie',
        'sous_categorie',
        'annees_experience',
        'tarif_minimum',
        'tarif_maximum',
        'devise',
        'disponibilite',
        'note_moyenne',
        'nombre_avis',
        'nombre_projets_realises',
        'vues_profil',
        'contacts_recus',
        'est_verifie',
        'est_en_vedette',
        'date_debut_vedette',
        'date_fin_vedette',
    ];

    protected $casts = [
        'tarif_minimum' => 'decimal:2',
        'tarif_maximum' => 'decimal:2',
        'note_moyenne' => 'decimal:2',
        'est_verifie' => 'boolean',
        'est_en_vedette' => 'boolean',
        'date_debut_vedette' => 'datetime',
        'date_fin_vedette' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(Utilisateur::class,'utilisateur_id');
    }

    public function competences()
    {
        return $this->hasMany(Competence::class);
    }

    public function Portofolios()
    {
        return $this->hasMany(Portofolio::class);
    }

    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function favorisParClients()
    {
        return $this->belongsToMany(User::class, 'favoris', 'freelance_id', 'client_id')
            ->withTimestamps();
    }

    // Scopes
    public function scopeDisponible($query)
    {
        return $query->where('disponibilite', 'disponible');
    }

    public function scopeEnVedette($query)
    {
        return $query->where('est_en_vedette', true)
            ->where(function($q) {
                $q->whereNull('date_fin_vedette')
                  ->orWhere('date_fin_vedette', '>', now());
            });
    }

    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeParVille($query, $ville)
    {
        return $query->whereHas('user', function($q) use ($ville) {
            $q->where('ville', $ville);
        });
    }

    public function scopeMieuxNotes($query)
    {
        return $query->where('nombre_avis', '>', 0)
            ->orderBy('note_moyenne', 'desc');
    }

    // Methods
    public function incrementerVuesProfil()
    {
        $this->increment('vues_profil');
    }

    public function incrementerContactsRecus()
    {
        $this->increment('contacts_recus');
    }

    public function mettreAJourNote()
    {
        $stats = $this->avis()->where('statut', 'publie')->selectRaw('AVG(note) as moyenne, COUNT(*) as total')->first();
        
        $this->update([
            'note_moyenne' => $stats->moyenne ?? 0,
            'nombre_avis' => $stats->total ?? 0,
        ]);
    }

    public function activerVedette($duree_jours = 30)
    {
        $this->update([
            'est_en_vedette' => true,
            'date_debut_vedette' => now(),
            'date_fin_vedette' => now()->addDays($duree_jours),
        ]);
    }

    public function desactiverVedette()
    {
        $this->update([
            'est_en_vedette' => false,
        ]);
    }
}