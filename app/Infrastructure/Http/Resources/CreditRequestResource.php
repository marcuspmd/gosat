<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\DTOs\CreditRequestDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditRequestResource extends JsonResource
{
    public function __construct(CreditRequestDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'request_id' => $this->resource->requestId,
            'cpf' => $this->resource->cpf,
            'status' => $this->resource->status,
            'message' => $this->resource->message,
            'created_at' => now()->toISOString(),
            'links' => [
                'status' => route('api.credit.status', ['requestId' => $this->resource->requestId]),
                'self' => route('api.credit.search'),
            ],
        ];
    }
}
