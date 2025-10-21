<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'type',
        'title',
        'description',
        'icon',
        'color',
        'is_unread',
        'read_at',
        'projet_id',
        'metadata'
    ];

    protected $casts = [
        'is_unread' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected $appends = ['time'];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }

    public function projet()
    {
        return $this->belongsTo(Project::class, 'projet_id');
    }

    /**
     * Accesseur pour le temps relatif
     */
    public function getTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
