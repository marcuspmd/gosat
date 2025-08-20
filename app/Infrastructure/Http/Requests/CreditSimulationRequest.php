<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cpf' => [
                'required',
                'string',
                'size:11',
                'regex:/^\d{11}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.required' => 'CPF é obrigatório',
            'cpf.size' => 'CPF deve ter o formato 00000000000',
            'cpf.regex' => 'CPF deve ter o formato válido: 00000000000',
        ];
    }

    public function attributes(): array
    {
        return [
            'cpf' => 'CPF',
        ];
    }
}
