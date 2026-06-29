<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    public const REQUIRED_TYPES = [
        [
            'name' => 'Revision tecnica',
            'slug' => 'revision-tecnica',
            'sort_order' => 10,
        ],
        [
            'name' => 'SOAP',
            'slug' => 'soap',
            'sort_order' => 20,
        ],
        [
            'name' => 'Permiso de circulacion',
            'slug' => 'permiso-circulacion',
            'sort_order' => 30,
        ],
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function documents()
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public static function ensureRequiredTypes(): void
    {
        foreach (self::REQUIRED_TYPES as $attributes) {
            self::query()->updateOrCreate([
                'slug' => $attributes['slug'],
            ], $attributes + ['is_required' => true]);
        }
    }
}
