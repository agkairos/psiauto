<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use App\Support\ApenasNumeros;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SalvarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente === null
            ? $this->user()->can('create', Cliente::class)
            : $this->user()->can('update', $cliente);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telefone' => ApenasNumeros::de($this->string('telefone')) ?: null,
            'cpf_cnpj' => ApenasNumeros::de($this->string('cpf_cnpj')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'digits_between:10,11'],
            'email' => ['nullable', 'email', 'max:255'],
            // CPF (11 dígitos) ou CNPJ (14) — nunca outro tamanho.
            'cpf_cnpj' => [
                'nullable',
                'digits_between:11,14',
                function (string $atributo, mixed $valor, Closure $falha) {
                    if ($valor !== null && ! in_array(strlen((string) $valor), [11, 14], true)) {
                        $falha('O CPF deve ter 11 dígitos ou o CNPJ 14 dígitos.');
                    }
                },
            ],
            'observacoes_internas' => ['nullable', 'string'],
            'ativo' => ['boolean'],
        ];
    }
}
