<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\InstitutionModelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TFactory of InstitutionModelFactory
 */
class InstitutionModel extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'institutions';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InstitutionModelFactory
    {
        return InstitutionModelFactory::new();
    }

    protected $fillable = [
        'id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function creditOffers(): HasMany
    {
        return $this->hasMany(CreditOfferModel::class, 'institution_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
