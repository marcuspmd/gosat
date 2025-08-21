<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditRequestModel extends Model
{
    use HasUuids;

    protected $table = 'credit_requests';

    protected $fillable = [
        'customer_id',
        'amount_cents',
        'installments',
        'valid_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'installments' => 'integer',
        'valid_at' => 'date',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function scopeByCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByAmount($query, int $amountCents)
    {
        return $query->where('amount_cents', $amountCents);
    }

    public function scopeByInstallments($query, int $installments)
    {
        return $query->where('installments', $installments);
    }

    public function scopeValid($query)
    {
        return $query->where('valid_at', '>=', now()->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_at', '<', now()->toDateString());
    }
}