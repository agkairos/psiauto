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

    protected function prepareForValidation(): void
    {
        // Campo vazio vira null pelo ConvertEmptyStringsToNull, mas a coluna
        // não aceita null (só tem default 0) — normaliza antes de validar.
        $this->merge([
            'taxa_percentual' => $this->input('taxa_percentual') ?? 0,
            'prazo_recebimento_dias' => $this->input('prazo_recebimento_dias') ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'taxa_percentual' => ['required', 'numeric', 'min:0', 'max:100'],
            'prazo_recebimento_dias' => ['required', 'integer', 'min:0', 'max:365'],
            'ativa' => ['boolean'],
        ];
    }
}
