<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    public const TYPE_QR_LINK = 'qr_link';

    public const TYPE_NFC_TAG_424_DNA = 'nfc_tag_424_dna';

    public const TYPES = [
        self::TYPE_QR_LINK => 'QR con link unico',
        self::TYPE_NFC_TAG_424_DNA => 'NFC TAG 424 DNA',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'nfc_identifier',
        'short_code',
        'label',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class)
            ->withPivot(['starts_at', 'ends_at'])
            ->withTimestamps();
    }

    public function activeVehicles()
    {
        return $this->vehicles()
            ->wherePivotNull('ends_at')
            ->where('vehicles.status', 'active');
    }

    public function getPublicUrlAttribute(): string
    {
        return rtrim((string) config('app.url'), '/')."/t/{$this->short_code}";
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
