<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signalement extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id','lieu','description','type_dechet','quartier',
        'photo','latitude','longitude','statut','agent_id','traite_at'
    ];

    protected function casts(): array {
        return ['latitude' => 'float','longitude' => 'float','traite_at' => 'datetime'];
    }

    public function user()  { return $this->belongsTo(User::class); }
    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }

    public function getPhotoUrlAttribute(): ?string {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }
}
