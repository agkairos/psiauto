<?php

namespace App\Http\Requests;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $produto = $this->route('produto');

        return $produto === null
            ? $this->user()->can('create', Produto::class)
            : $this->user()->can('update', $produto);
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'codigo' => [
                'nullable', 'string', 'max:255',
                Rule::unique('produtos', 'codigo')->where('empresa_id', $empresaId)->ignore($this->route('produto')),
            ],
            'codigo_barras' => ['nullable', 'string', 'max:255'],
            'nome' => ['required', 'string', 'max:255'],
            'categoria_id' => ['nullable', Rule::exists('produto_categorias', 'id')->where('empresa_id', $empresaId)],
            'marca' => ['nullable', 'string', 'max:255'],
            'unidade_medida' => ['nullable', 'string', 'max:10'],
            'custo' => ['nullable', 'numeric', 'min:0'],
            'preco_venda' => ['nullable', 'numeric', 'min:0'],
            'estoque_minimo' => ['nullable', 'integer', 'min:0'],
            'visivel_para_cliente' => ['boolean'],
            'ativo' => ['boolean'],
        ];
    }
}
