<?php

namespace App\Http\Requests;

use App\Models\FormaPagamento;
use Illuminate\Foundation\Http\FormRequest;

class SalvarFormaPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $formaPagamento = $this->route('formaPagamento');

        return $formaPagamento === null
            ? $this->user()->can('create', FormaPagamento::class)
            : $this->user()->can('update', $formaPagamento);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'taxa_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prazo_recebimento_dias' => ['nullable', 'integer', 'min:0', 'max:365'],
            'ativa' => ['boolean'],
        ];
    }
}
