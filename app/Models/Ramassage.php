<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ramassage extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id','adresse','description_domicile','frequence','prix',
        'phone_paiement','statut_paiement','reference_paiement',
        'latitude','longitude','statut'
    ];

    protected function casts(): array {
        return ['latitude' => 'float','longitude' => 'float','prix' => 'float'];
    }

    public static array $tarifs = ['1_semaine' => 2000,'2_semaine' => 3000];

    public function user() { return $this->belongsTo(User::class); }
}
