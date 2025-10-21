<?php
// app/Models/Competence.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelance_id',
        'nom_competence',
        'niveau',
    ];

    public function freelance()
    {
        return $this->belongsTo(Freelance::class);
    }
}