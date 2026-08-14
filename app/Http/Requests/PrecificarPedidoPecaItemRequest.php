<?php

namespace App\Http\Requests;

use App\Models\PedidoPecaItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrecificarPedidoPecaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item')->pedidoPeca);
    }

    public function rules(): array
    {
        return [
            'disponibilidade' => ['required', Rule::in([
                PedidoPecaItem::DISPONIBILIDADE_EM_ESTOQUE,
                PedidoPecaItem::DISPONIBILIDADE_SOB_ENCOMENDA,
                PedidoPecaItem::DISPONIBILIDADE_INDISPONIVEL,
            ])],
            // §12 — "precificação item a item"; peça indisponível não tem preço.
            'preco_unitario' => ['required_unless:disponibilidade,indisponivel', 'nullable', 'numeric', 'min:0'],
            'prazo_entrega_dias' => ['required_if:disponibilidade,sob_encomenda', 'nullable', 'integer', 'min:1'],
        ];
    }
}
