<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'reference',
        'phone_number',
        'status',
        'payment_method',
        'provider',
        'transaction_data',
        'error_message',
        'completed_at'
    ];

    protected function casts(): array {
        return [
            'amount' => 'float',
            'transaction_data' => 'json',
            'completed_at' => 'datetime'
        ];
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function abonnement() {
        return $this->belongsTo(Abonnement::class);
    }

    // Scopes
    public function scopeSuccessful($query) {
        return $query->where('status', 'completed');
    }

    public function scopePending($query) {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query) {
        return $query->where('status', 'failed');
    }

    public function isPending(): bool {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool {
        return $this->status === 'completed';
    }

    public function isFailed(): bool {
        return $this->status === 'failed';
    }
}
