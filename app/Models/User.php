<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['nom','email','password','role','abonnement','phone','is_active'];
    protected $hidden   = ['password','remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function signalements()     { return $this->hasMany(Signalement::class); }
    public function abonnements()      { return $this->hasMany(Abonnement::class); }
    public function ramassages()       { return $this->hasMany(Ramassage::class); }
    public function abonnementActif()  { return $this->hasOne(Abonnement::class)->where('statut','actif')->latest(); }

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isAgent(): bool    { return $this->role === 'agent'; }
    public function isCitoyen(): bool  { return $this->role === 'citoyen'; }
}
