<?php

namespace App\Http\Requests;

use App\Models\Agendamento;
use App\Models\OrdemServico;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AbrirOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', OrdemServico::class);
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            // Nulo = OS avulsa (sem agendamento prévio) — decisão confirmada
            // com o usuário. Ver docs/checklist-os.md.
            'agendamento_id' => ['nullable', Rule::exists('agendamentos', 'id')->where('empresa_id', $empresaId)],
            'unidade_id' => ['required', Rule::exists('unidades', 'id')->where('empresa_id', $empresaId)],
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)],
            'veiculo_id' => [
                'required',
                Rule::exists('veiculos', 'id')
                    ->where('empresa_id', $empresaId)
                    ->where('cliente_id', $this->input('cliente_id')),
            ],
            'reclamacao_cliente' => ['nullable', 'string'],
            'checklist_entrada' => ['nullable', 'array'],
            'checklist_entrada.km_entrada' => ['nullable', 'integer', 'min:0'],
            'checklist_entrada.nivel_combustivel' => ['nullable', 'string', 'max:20'],
            'checklist_entrada.avarias' => ['nullable', 'array'],
            'checklist_entrada.avarias.*' => ['string', 'max:255'],
            'checklist_entrada.objetos_deixados' => ['nullable', 'string', 'max:500'],
            'checklist_entrada.cliente_confirmou' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $agendamentoId = $this->input('agendamento_id');
            if (! $agendamentoId) {
                return;
            }

            $agendamento = Agendamento::find($agendamentoId);
            if ($agendamento === null) {
                return;
            }

            if ((int) $agendamento->cliente_id !== (int) $this->input('cliente_id')
                || (int) $agendamento->veiculo_id !== (int) $this->input('veiculo_id')) {
                $validator->errors()->add('agendamento_id', 'O cliente/veículo não bate com o agendamento selecionado.');
            }

            if (OrdemServico::query()->where('agendamento_id', $agendamentoId)->exists()) {
                $validator->errors()->add('agendamento_id', 'Esse agendamento já tem uma OS aberta.');
            }
        });
    }
}
