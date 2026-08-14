<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarBloqueioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // O recurso pai já vem autorizado pelo controller (Gate::authorize
        // 'update' nele) — aqui só validamos o formato dos dados do bloqueio.
        return true;
    }

    public function rules(): array
    {
        return [
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
