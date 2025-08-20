<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModalityMappingModel extends Model
{
    use HasUuids;

    protected $table = 'modality_mappings';

    protected $fillable = [
        'institution_id',
        'external_code',
        'standard_modality_code',
        'modality_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(InstitutionModel::class, 'institution_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByInstitution($query, string $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeByExternalCode($query, string $externalCode)
    {
        return $query->where('external_code', $externalCode);
    }

    public function scopeByStandardCode($query, string $standardCode)
    {
        return $query->where('standard_modality_code', $standardCode);
    }
}
