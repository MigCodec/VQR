<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    public const PLANS = [
        'normal' => [
            'label' => 'Normal',
            'amount' => 4990,
            'vehicle_limit' => 1,
        ],
        'premium' => [
            'label' => 'Premium',
            'amount' => 9990,
            'vehicle_limit' => 3,
        ],
    ];

    protected $fillable = [
        'user_id',
        'status',
        'plan',
        'vehicle_limit',
        'amount',
        'currency',
        'starts_at',
        'expires_at',
        'last_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lastPayment()
    {
        return $this->belongsTo(Payment::class, 'last_payment_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public static function plan(string $plan): array
    {
        return self::PLANS[$plan] ?? self::PLANS['normal'];
    }
}
