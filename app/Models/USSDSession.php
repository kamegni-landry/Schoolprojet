<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class USSDSession extends Model {
    use HasFactory;

    protected $fillable = [
        'session_id',
        'phone_number',
        'user_id',
        'current_step',
        'data',
        'status',
        'expires_at'
    ];

    protected function casts(): array {
        return [
            'data' => 'json',
            'expires_at' => 'datetime'
        ];
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function signalement() {
        return $this->hasOne(Signalement::class, 'ussd_session_id');
    }

    public function isExpired(): bool {
        return now()->isAfter($this->expires_at);
    }
}
