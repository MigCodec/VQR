<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_token',
        'plate',
        'brand',
        'model',
        'year',
        'vin',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'starts_at', 'ends_at', 'is_primary'])
            ->withTimestamps();
    }

    public function activeUsers()
    {
        return $this->users()->wherePivotNull('ends_at');
    }

    public function activeOwners()
    {
        return $this->activeUsers()->wherePivot('role', 'owner');
    }

    public function primaryOwner()
    {
        return $this->activeOwners()->wherePivot('is_primary', true);
    }

    public function documents()
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function cards()
    {
        return $this->belongsToMany(Card::class)
            ->withPivot(['starts_at', 'ends_at'])
            ->withTimestamps();
    }

    public function activeCards()
    {
        return $this->cards()
            ->wherePivotNull('ends_at')
            ->where('cards.status', 'active');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->brand} {$this->model}") ?: 'Vehículo';
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/v/{$this->public_token}");
    }

    public function documentSummary(): array
    {
        $documents = $this->documents;

        return [
            'total' => $documents->count(),
            'valid' => $documents->where('computed_status', 'valid')->count(),
            'attention' => $documents->filter(fn (VehicleDocument $document) => $document->computed_status !== 'valid')->count(),
        ];
    }
}
