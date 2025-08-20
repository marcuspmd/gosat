<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionModel extends Model
{
    use HasUuids;

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'slug',
        'website',
        'logo_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function modalityMappings(): HasMany
    {
        return $this->hasMany(ModalityMappingModel::class, 'institution_id');
    }

    public function creditOffers(): HasMany
    {
        return $this->hasMany(CreditOfferModel::class, 'institution_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
