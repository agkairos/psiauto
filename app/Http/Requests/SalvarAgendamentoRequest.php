<?php

namespace App\Http\Requests;

use App\Actions\Agendamentos\VerificarDisponibilidadeRecurso;
use App\Models\Agendamento;
use App\Models\Recurso;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agendamento = $this->route('agendamento');

        return $agendamento === null
            ? $this->user()->can('create', Agendamento::class)
            : $this->user()->can('update', $agendamento);
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'recurso_id' => ['required', Rule::exists('recursos', 'id')->where('empresa_id', $empresaId)],
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)],
            'veiculo_id' => [
                'required',
                Rule::exists('veiculos', 'id')
                    ->where('empresa_id', $empresaId)
                    ->where('cliente_id', $this->input('cliente_id')),
            ],
            'servico_id' => ['required', Rule::exists('servicos', 'id')->where('empresa_id', $empresaId)],
            'data' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'observacoes_cliente' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $recurso = Recurso::find($this->input('recurso_id'));
            $servico = Servico::find($this->input('servico_id'));

            if ($recurso === null || $servico === null) {
                return;
            }

            $horaFim = $this->calcularHoraFim($this->input('hora_inicio'), $servico->tempo_execucao_minutos);

            $erros = app(VerificarDisponibilidadeRecurso::class)(
                $recurso,
                Carbon::parse($this->input('data')),
                $this->input('hora_inicio'),
                $horaFim,
                (int) $this->input('servico_id'),
                $this->route('agendamento')?->id,
            );

            foreach ($erros as $erro) {
                $validator->errors()->add('hora_inicio', $erro);
            }
        });
    }

    public function calcularHoraFim(string $horaInicio, int $duracaoMinutos): string
    {
        return Carbon::createFromFormat('H:i', $horaInicio)
            ->addMinutes($duracaoMinutos)
            ->format('H:i');
    }
}
