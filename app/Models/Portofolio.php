<?php
// app/Models/Portfolio.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portofolio extends Model
{
    use HasFactory;
protected $appends = ['image_url_full'];
    protected $fillable = [
        'freelance_id',
        'titre',
        'description',
        'image_url',
        'lien_externe',
        'date_realisation',
    ];

    protected $casts = [
        'date_realisation' => 'date',
    ];

    public function freelance()
    {
        return $this->belongsTo(Freelance::class);
    }

    public function getImageUrlFullAttribute()
{
    return $this->image_url 
        ? asset('storage/' . $this->image_url) 
        : null;
}
}