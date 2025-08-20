<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditModalityModel extends Model
{
    use HasUuids;

    protected $table = 'credit_modalities';

    protected $fillable = [
        'standard_code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function creditOffers(): HasMany
    {
        return $this->hasMany(CreditOfferModel::class, 'modality_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStandardCode($query, string $standardCode)
    {
        return $query->where('standard_code', $standardCode);
    }
}
