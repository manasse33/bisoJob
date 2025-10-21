<?
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// app/Models/Notification.php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'message',
        'type',
        'data',
        'est_lue'
    ];

    protected $casts = [
        'data' => 'array',
        'est_lue' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeNonLu($query)
    {
        return $query->where('est_lue', false);
    }

    /**
     * Scope pour les notifications lues
     */
    public function scopeLu($query)
    {
        return $query->where('est_lue', true);
    }

    /**
     * Marquer comme lue
     */
    public function marquerCommeLu()
    {
        $this->update(['est_lue' => true]);
    }

    /**
     * Marquer comme non lue
     */
    public function marquerCommeNonLue()
    {
        $this->update(['est_lue' => false]);
    }
}
