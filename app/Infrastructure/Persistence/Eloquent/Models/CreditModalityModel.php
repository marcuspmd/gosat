<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\CreditModalityModelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TFactory of CreditModalityModelFactory
 */
class CreditModalityModel extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'credit_modalities';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CreditModalityModelFactory
    {
        return CreditModalityModelFactory::new();
    }

    protected $fillable = [
        'id',
        'standard_code',
        'name',
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
