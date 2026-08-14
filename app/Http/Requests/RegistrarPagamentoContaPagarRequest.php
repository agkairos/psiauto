<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagamentoContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financeiro.gerenciar');
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
        ];
    }
}
