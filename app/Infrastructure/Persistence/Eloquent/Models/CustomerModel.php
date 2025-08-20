<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    use HasUuids;

    protected $table = 'customers';

    protected $fillable = [
        'cpf',
        'name',
        'email',
        'phone',
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

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}
