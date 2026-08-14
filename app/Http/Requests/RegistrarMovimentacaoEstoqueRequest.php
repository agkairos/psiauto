<?php

namespace App\Http\Requests;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarMovimentacaoEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('produto'));
    }

    public function rules(): array
    {
        return [
            'unidade_id' => ['required', 'exists:unidades,id'],
            'tipo' => ['required', Rule::in([Produto::TIPO_ENTRADA, Produto::TIPO_SAIDA, Produto::TIPO_AJUSTE, Produto::TIPO_PERDA])],
            'quantidade' => ['required', 'integer', 'min:1'],
            'custo_unitario' => ['nullable', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
