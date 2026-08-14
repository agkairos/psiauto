<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegistrarRecebimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financeiro.gerenciar');
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
            'forma_pagamento_id' => ['nullable', Rule::exists('formas_pagamento', 'id')->where('empresa_id', $empresaId)],
        ];
    }
}
