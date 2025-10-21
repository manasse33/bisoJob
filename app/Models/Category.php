<?php
// app/Models/Categorie.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'icone',
        'ordre_affichage',
        'est_active',
    ];

    protected $casts = [
        'est_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeOrdonnee($query)
    {
        return $query->orderBy('ordre_affichage');
    }
}