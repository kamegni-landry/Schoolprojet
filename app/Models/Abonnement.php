<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model {
    use HasFactory;

    protected $fillable = ['user_id','plan','prix','statut','date_debut','date_fin'];

    protected function casts(): array {
        return ['date_debut' => 'datetime','date_fin' => 'datetime','prix' => 'float'];
    }

    public static array $tarifs = ['basique' => 0,'standard' => 2000,'premium' => 5000];

    public function user() { return $this->belongsTo(User::class); }
    public function isActif(): bool { return $this->statut === 'actif'; }
}
