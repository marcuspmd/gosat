<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditOfferModel extends Model
{
    use HasUuids;

    protected $table = 'credit_offers';

    protected $fillable = [
        'request_id',
        'cpf',
        'institution_id',
        'modality_id',
        'min_amount_cents',
        'max_amount_cents',
        'approved_amount_cents',
        'monthly_interest_rate',
        'min_installments',
        'max_installments',
        'installments',
        'status',
        'error_message',
    ];

    protected $casts = [
        'min_amount_cents' => 'integer',
        'max_amount_cents' => 'integer',
        'approved_amount_cents' => 'integer',
        'monthly_interest_rate' => 'decimal:6',
        'min_installments' => 'integer',
        'max_installments' => 'integer',
        'installments' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(InstitutionModel::class, 'institution_id');
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(CreditModalityModel::class, 'modality_id');
    }

    public function scopeByRequestId($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    public function scopeByCpf($query, string $cpf)
    {
        return $query->where('cpf', $cpf);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
