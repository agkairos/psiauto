<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ordemServico'));
    }

    public function rules(): array
    {
        return [
            'reclamacao_cliente' => ['nullable', 'string'],
            'diagnostico_tecnico' => ['nullable', 'string'],
            'checklist_entrada' => ['nullable', 'array'],
            'checklist_entrada.km_entrada' => ['nullable', 'integer', 'min:0'],
            'checklist_entrada.nivel_combustivel' => ['nullable', 'string', 'max:20'],
            'checklist_entrada.avarias' => ['nullable', 'array'],
            'checklist_entrada.avarias.*' => ['string', 'max:255'],
            'checklist_entrada.objetos_deixados' => ['nullable', 'string', 'max:500'],
            'checklist_entrada.cliente_confirmou' => ['boolean'],
        ];
    }
}
