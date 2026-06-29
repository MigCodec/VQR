<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'avatar_url',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function activeCards()
    {
        return $this->cards()->where('status', 'active');
    }

    public function accountCard(): Card
    {
        $card = $this->activeCards()
            ->where('type', Card::TYPE_QR_LINK)
            ->oldest()
            ->first();

        if ($card) {
            return $card;
        }

        return $this->cards()->create([
            'nfc_identifier' => 'pending-user-'.$this->id.'-'.Str::lower(Str::random(6)),
            'short_code' => Str::lower(Str::random(8)),
            'type' => Card::TYPE_QR_LINK,
            'label' => 'Tarjeta cuenta VQR',
            'status' => 'active',
        ]);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class)
            ->withPivot(['role', 'starts_at', 'ends_at', 'is_primary'])
            ->withTimestamps();
    }

    public function activeVehicles()
    {
        return $this->vehicles()
            ->wherePivotNull('ends_at')
            ->where('vehicles.status', 'active');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function vehicleLimit(): int
    {
        return (int) ($this->activeSubscription?->vehicle_limit ?? 0);
    }

    public function activeVehicleCount(): int
    {
        return $this->activeVehicles()->count();
    }

    public function canAddVehicle(): bool
    {
        return $this->hasActiveSubscription()
            && $this->activeVehicleCount() < $this->vehicleLimit();
    }
}
