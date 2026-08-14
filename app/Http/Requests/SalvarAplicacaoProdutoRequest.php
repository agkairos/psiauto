<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarAplicacaoProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('produto'));
    }

    public function rules(): array
    {
        return [
            'marca_id' => ['required', 'exists:marcas,id'],
            'modelo_id' => ['nullable', Rule::exists('modelos', 'id')->where('marca_id', $this->input('marca_id'))],
            'ano_inicio' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'ano_fim' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 2), 'gte:ano_inicio'],
        ];
    }
}
