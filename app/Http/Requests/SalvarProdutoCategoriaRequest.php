<?php

namespace App\Http\Requests;

use App\Models\ProdutoCategoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarProdutoCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $categoria = $this->route('categoria');

        return $categoria === null
            ? $this->user()->can('create', ProdutoCategoria::class)
            : $this->user()->can('update', $categoria);
    }

    public function rules(): array
    {
        $categoria = $this->route('categoria');

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('produto_categorias', 'nome')
                    ->where('empresa_id', Auth::user()->empresa_id)
                    ->ignore($categoria?->id),
            ],
            'ativa' => ['boolean'],
        ];
    }
}
