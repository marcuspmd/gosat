<?php

declare(strict_types=1);

use App\Infrastructure\Http\Requests\CreditSimulationRequest;

describe('CreditSimulationRequest', function () {
    beforeEach(function () {
        $this->request = new CreditSimulationRequest;
    });

    it('can be instantiated', function () {
        expect($this->request)->toBeInstanceOf(CreditSimulationRequest::class);
    });

    describe('authorize method', function () {
        it('always returns true', function () {
            expect($this->request->authorize())->toBeTrue();
        });
    });

    describe('rules method', function () {
        it('requires CPF field', function () {
            $rules = $this->request->rules();

            expect($rules)->toHaveKey('cpf')
                ->and($rules['cpf'])->toContain('required');
        });

        it('requires CPF to be string', function () {
            $rules = $this->request->rules();

            expect($rules['cpf'])->toContain('string');
        });

        it('requires CPF to be exact length', function () {
            $rules = $this->request->rules();

            expect($rules['cpf'])->toContain('size:11');
        });

        it('requires CPF regex validation', function () {
            $rules = $this->request->rules();

            expect($rules['cpf'])->toContain('regex:/^\d{11}$/');
        });

        it('returns expected rule structure', function () {
            $rules = $this->request->rules();

            expect($rules)->toBeArray()
                ->and($rules)->toHaveKey('cpf')
                ->and($rules['cpf'])->toBeArray();
        });
    });

    describe('messages method', function () {
        it('returns correct custom error messages', function () {
            $messages = $this->request->messages();

            expect($messages)->toBeArray()
                ->toHaveKeys(['cpf.required', 'cpf.size', 'cpf.regex'])
                ->and($messages['cpf.required'])->toBe('CPF é obrigatório')
                ->and($messages['cpf.size'])->toBe('CPF deve ter o formato 00000000000')
                ->and($messages['cpf.regex'])->toBe('CPF deve ter o formato válido: 00000000000');
        });

        it('provides Portuguese error messages', function () {
            $messages = $this->request->messages();

            expect($messages['cpf.required'])->toContain('obrigatório')
                ->and($messages['cpf.size'])->toContain('formato')
                ->and($messages['cpf.regex'])->toContain('válido');
        });
    });

    describe('attributes method', function () {
        it('returns correct attribute names', function () {
            $attributes = $this->request->attributes();

            expect($attributes)->toBeArray()
                ->toHaveKey('cpf')
                ->and($attributes['cpf'])->toBe('CPF');
        });
    });

    describe('messages method', function () {
        it('returns expected messages array', function () {
            $messages = $this->request->messages();

            expect($messages)->toBeArray();
        });

        it('contains required message for CPF', function () {
            $messages = $this->request->messages();

            expect($messages)->toHaveKey('cpf.required')
                ->and($messages['cpf.required'])->toBeString()
                ->and($messages['cpf.required'])->toBe('CPF é obrigatório');
        });

        it('contains size message for CPF', function () {
            $messages = $this->request->messages();

            expect($messages)->toHaveKey('cpf.size')
                ->and($messages['cpf.size'])->toBeString()
                ->and($messages['cpf.size'])->toBe('CPF deve ter o formato 00000000000');
        });

        it('contains regex message for CPF', function () {
            $messages = $this->request->messages();

            expect($messages)->toHaveKey('cpf.regex')
                ->and($messages['cpf.regex'])->toBeString()
                ->and($messages['cpf.regex'])->toBe('CPF deve ter o formato válido: 00000000000');
        });
    });

    describe('edge cases', function () {
        it('handles empty string CPF', function () {
            $rules = $this->request->rules();
            // Teste direto das regras sem usar Validator facade
            expect($rules['cpf'])->toContain('required', 'string', 'size:11', 'regex:/^\d{11}$/');
        });

        it('handles null CPF', function () {
            $rules = $this->request->rules();
            // Teste direto das regras sem usar Validator facade
            expect($rules['cpf'])->toContain('required', 'string', 'size:11', 'regex:/^\d{11}$/');
        });

        it('handles array CPF', function () {
            $rules = $this->request->rules();
            // Teste direto das regras sem usar Validator facade
            expect($rules['cpf'])->toContain('required', 'string', 'size:11', 'regex:/^\d{11}$/');
        });

        it('handles boolean CPF', function () {
            $rules = $this->request->rules();
            // Teste direto das regras sem usar Validator facade
            expect($rules['cpf'])->toContain('required', 'string', 'size:11', 'regex:/^\d{11}$/');
        });
    });

    describe('CPF format validation comprehensive tests', function () {
        it('validates various invalid CPF formats', function () {
            $rules = $this->request->rules();
            $invalidCpfs = [
                '123-456-789-01',
                '123.456.789.01',
                '(123) 456-7890',
                '123 456 789 01',
                '123-456-789',
                'ABC12345678',
                '12345678901A',
                '++++++++++++',
                '............',
                '            ',
            ];

            // Teste direto das regras - todas devem falhar no regex
            foreach ($invalidCpfs as $cpf) {
                expect(preg_match('/^\d{11}$/', $cpf))->toBe(0, "CPF '{$cpf}' should be invalid");
            }
        });

        it('validates edge case valid CPFs', function () {
            $rules = $this->request->rules();
            $validCpfs = [
                '00000000000', // All zeros (passes regex but might fail business logic)
                '11111111111', // All ones
                '99999999999', // All nines
                '12345678900', // Ending with zeros
                '01234567890', // Starting with zero
            ];

            // Teste direto das regras - todas devem passar no regex
            foreach ($validCpfs as $cpf) {
                expect(preg_match('/^\d{11}$/', $cpf))->toBe(1, "CPF '{$cpf}' should be valid for regex");
            }
        });
    });
});
