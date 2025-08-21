<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditOfferModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'credit_offers';

    protected $fillable = [
        'customer_id',
        'institution_id',
        'modality_id',
        'min_amount_cents',
        'max_amount_cents',
        'monthly_interest_rate',
        'min_installments',
        'max_installments',
        'error_message',
    ];

    protected $casts = [
        'min_amount_cents' => 'integer',
        'max_amount_cents' => 'integer',
        'monthly_interest_rate' => 'decimal:6',
        'min_installments' => 'integer',
        'max_installments' => 'integer',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function scopeByCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

}
