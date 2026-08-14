<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AtualizarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('usuario'));
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'role' => ['required', Rule::in(['gerente', 'atendente', 'tecnico', 'financeiro'])],
            'unidade_id' => [
                'nullable',
                Rule::exists('unidades', 'id')->where('empresa_id', $empresaId),
            ],
        ];
    }
}
