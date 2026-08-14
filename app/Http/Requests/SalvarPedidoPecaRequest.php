<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarPedidoPecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'unidade_id' => ['required', Rule::exists('unidades', 'id')],
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)],
            'veiculo_id' => ['nullable', Rule::exists('veiculos', 'id')->where('empresa_id', $empresaId)],
            'observacoes' => ['nullable', 'string'],

            'itens' => ['required', 'array', 'min:1'],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.produto_id' => ['nullable', Rule::exists('produtos', 'id')->where('empresa_id', $empresaId)],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
        ];
    }
}
