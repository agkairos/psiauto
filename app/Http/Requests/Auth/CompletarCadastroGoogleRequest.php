<?php

namespace App\Http\Requests\Auth;

use App\Support\ApenasNumeros;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletarCadastroGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => ApenasNumeros::de($this->string('cnpj')),
        ]);
    }

    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', 'digits:14', 'unique:empresas,cnpj'],
            'segmentos' => ['required', 'array', 'min:1'],
            'segmentos.*' => [Rule::in(['mecanica', 'eletrica', 'funilaria', 'estetica', 'pecas'])],
        ];
    }
}
