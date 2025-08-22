<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\CustomerModelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'customers';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CustomerModelFactory
    {
        return CustomerModelFactory::new();
    }

    protected $fillable = [
        'id',
        'cpf',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCpf($query, string $cpf)
    {
        return $query->where('cpf', $cpf);
    }
}
