<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecusarItemOrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_recusa' => ['nullable', 'string', 'max:255'],
        ];
    }
}
