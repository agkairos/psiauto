<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConvidarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('usuarios.gerenciar');
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Proprietário não é atribuível por convite — só existe um, criado
            // no cadastro da empresa. Ver docs/login-social.md.
            'role' => ['required', Rule::in(['gerente', 'atendente', 'tecnico', 'financeiro'])],
            'unidade_id' => [
                'nullable',
                Rule::exists('unidades', 'id')->where('empresa_id', $empresaId),
            ],
        ];
    }
}
