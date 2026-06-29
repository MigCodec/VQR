<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VehicleDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'document_type_id',
        'public_token',
        'folio',
        'issued_at',
        'expires_at',
        'status',
        'file_path',
        'source_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    protected $appends = [
        'computed_status',
    ];

    protected static function booted(): void
    {
        static::creating(function (VehicleDocument $document): void {
            if (! $document->public_token) {
                $document->public_token = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status !== 'valid' || $this->expires_at === null) {
            return $this->status;
        }

        $today = now()->startOfDay();
        $expiresAt = $this->expires_at->copy()->startOfDay();

        if ($expiresAt->lt($today)) {
            return 'expired';
        }

        if ($today->diffInDays($expiresAt) <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'valid' => 'Vigente',
            'expired' => 'Vencido',
            'expiring_soon' => 'Por vencer',
            'pending' => 'Pendiente',
            'missing' => 'No disponible',
            'rejected' => 'Rechazado',
            default => 'Sin estado',
        };
    }
}
