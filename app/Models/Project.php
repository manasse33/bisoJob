<?php
// app/Models/Projet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $table = 'projets';

    protected $fillable = [
        'client_id',
        'titre',
        'description',
        'categorie',
        'budget_minimum',
        'budget_maximum',
        'devise',
        'ville',
        'delai_souhaite',
        'statut',
        'nombre_vues',
        'nombre_contacts',
        'date_cloture',
    ];

    protected $casts = [
        'budget_minimum' => 'decimal:2',
        'budget_maximum' => 'decimal:2',
        'date_cloture' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function client()
    {
        return $this->belongsTo(Utilisateur::class, 'client_id');
    }

    // public function contacts()
    // {
    //     return $this->hasMany(Contact::class);
    // }

    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    // Scopes
    public function scopeOuvert($query)
    {
        return $query->where('statut', 'ouvert');
    }

    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    public function incrementerVues()
    {
        $this->increment('nombre_vues');
    }

    public function incrementerContacts()
    {
        $this->increment('nombre_contacts');
    }
}