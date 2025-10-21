<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'whatsapp',
        'password',
        'type_utilisateur',
        'photo_profil',
        'ville',
        'adresse',
        'statut',
        'email_verifie',
        'telephone_verifie',
        'email_verified_at',
        'verification_token',
        'derniere_connexion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verifie' => 'boolean',
        'telephone_verifie' => 'boolean',
        'derniere_connexion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => now(),
            'email_verifie' => true,
            'verification_token' => null,
        ])->save();
    }
    // Relations
    public function freelance()
    {
        return $this->hasOne(Freelance::class,'utilisateur_id');
    }

    public function projets()
    {
        return $this->hasMany(Projet::class, 'client_id');
    }

    public function avisClient()
    {
        return $this->hasMany(Avis::class, 'client_id');
    }

    // public function contactsClient()
    // {
    //     return $this->hasMany(Contact::class, 'client_id');
    // }

    public function favoris()
    {
        return $this->belongsToMany(Freelance::class, 'favoris', 'client_id', 'freelance_id')
            ->withTimestamps();
    }

    // public function notifications()
    // {
    //     return $this->hasMany(Notification::class);
    // }

    // Accessors
    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeFreelances($query)
    {
        return $query->where('type_utilisateur', 'freelance');
    }

    public function scopeClients($query)
    {
        return $query->where('type_utilisateur', 'client');
    }
}