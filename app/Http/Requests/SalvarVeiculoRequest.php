<?php

namespace App\Http\Requests;

use App\Models\Veiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalvarVeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $veiculo = $this->route('veiculo');

        return $veiculo === null
            ? $this->user()->can('create', Veiculo::class)
            : $this->user()->can('update', $veiculo);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'placa' => Str::of((string) $this->string('placa'))->upper()->replaceMatches('/[^A-Z0-9]/', '')->value(),
        ]);
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'marca_id' => ['required', 'exists:marcas,id'],
            'modelo_id' => [
                'required',
                Rule::exists('modelos', 'id')->where('marca_id', $this->input('marca_id')),
            ],
            // Padrão antigo (ABC1234) ou Mercosul (ABC1D23) — 7 caracteres.
            'placa' => [
                'required',
                'regex:/^[A-Z]{3}[0-9]{4}$|^[A-Z]{3}[0-9][A-Z][0-9]{2}$/',
                Rule::unique('veiculos', 'placa')->where('empresa_id', $empresaId)->ignore($this->route('veiculo')),
            ],
            'chassi' => ['nullable', 'string', 'max:30'],
            'ano_fabricacao' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'ano_modelo' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 2)],
            'versao' => ['nullable', 'string', 'max:255'],
            'cor' => ['nullable', 'string', 'max:100'],
            'quilometragem_atual' => ['nullable', 'integer', 'min:0'],
            'observacoes_internas' => ['nullable', 'string'],
        ];
    }
}
