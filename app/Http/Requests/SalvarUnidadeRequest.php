<?php

namespace App\Http\Requests;

use App\Models\Unidade;
use App\Support\ApenasNumeros;
use Illuminate\Foundation\Http\FormRequest;

class SalvarUnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unidade = $this->route('unidade');

        return $unidade === null
            ? $this->user()->can('create', Unidade::class)
            : $this->user()->can('update', $unidade);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep' => ApenasNumeros::de($this->string('cep')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'size:2'],
            'ativa' => ['boolean'],
        ];
    }
}
