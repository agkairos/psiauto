<?php

namespace App\Actions\Agendamentos;

use App\Models\Agendamento;
use App\Models\Recurso;
use Carbon\Carbon;

/**
 * Cruza grade semanal + bloqueios + agendamentos já existentes do recurso +
 * antecedência mínima da unidade para decidir se um horário está disponível.
 * Usado tanto no cadastro quanto na remarcação — e será reaproveitado pela
 * API do app do cliente (§19) quando existir, por isso vive numa Action e
 * não dentro do FormRequest.
 */
class VerificarDisponibilidadeRecurso
{
    private const DIAS_SEMANA = [
        1 => 'segunda', 2 => 'terca', 3 => 'quarta', 4 => 'quinta',
        5 => 'sexta', 6 => 'sabado', 7 => 'domingo',
    ];

    /**
     * @return list<string> erros encontrados — vazio significa disponível.
     */
    public function __invoke(
        Recurso $recurso,
        Carbon $data,
        string $horaInicio,
        string $horaFim,
        ?int $servicoId = null,
        ?int $ignorarAgendamentoId = null,
    ): array {
        $erros = [];

        if (! $recurso->ativo) {
            $erros[] = 'Esse recurso está inativo.';

            return $erros;
        }

        if ($servicoId !== null && ! $recurso->servicos()->where('servicos.id', $servicoId)->exists()) {
            $erros[] = 'Esse recurso não atende o serviço selecionado.';
        }

        if (! $this->dentroDaGrade($recurso, $data, $horaInicio, $horaFim)) {
            $erros[] = 'Fora do horário de funcionamento desse recurso nesse dia.';
        }

        if ($this->temBloqueio($recurso, $data)) {
            $erros[] = 'Esse recurso está bloqueado nessa data (feriado, férias ou manutenção).';
        }

        if ($this->temConflito($recurso, $data, $horaInicio, $horaFim, $ignorarAgendamentoId)) {
            $erros[] = 'Já existe outro agendamento nesse recurso nesse horário.';
        }

        $erroAntecedencia = $this->antecedenciaInsuficiente($recurso, $data, $horaInicio);
        if ($erroAntecedencia !== null) {
            $erros[] = $erroAntecedencia;
        }

        return $erros;
    }

    private function dentroDaGrade(Recurso $recurso, Carbon $data, string $horaInicio, string $horaFim): bool
    {
        $diaChave = self::DIAS_SEMANA[$data->dayOfWeekIso];
        $intervalos = $recurso->grade_semanal[$diaChave] ?? [];

        foreach ($intervalos as $intervalo) {
            if ($horaInicio >= $intervalo['inicio'] && $horaFim <= $intervalo['fim']) {
                return true;
            }
        }

        return false;
    }

    private function temBloqueio(Recurso $recurso, Carbon $data): bool
    {
        return $recurso->bloqueios()
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->exists();
    }

    private function temConflito(
        Recurso $recurso,
        Carbon $data,
        string $horaInicio,
        string $horaFim,
        ?int $ignorarAgendamentoId,
    ): bool {
        return Agendamento::query()
            ->where('recurso_id', $recurso->id)
            ->whereDate('data', $data)
            ->whereNotIn('status', Agendamento::STATUS_QUE_LIBERAM_HORARIO)
            ->when($ignorarAgendamentoId, fn ($q) => $q->whereKeyNot($ignorarAgendamentoId))
            // Dois intervalos [a,b) e [c,d) se sobrepõem quando a < d e c < b.
            ->where('hora_inicio', '<', $horaFim)
            ->where('hora_fim', '>', $horaInicio)
            ->exists();
    }

    private function antecedenciaInsuficiente(Recurso $recurso, Carbon $data, string $horaInicio): ?string
    {
        $antecedenciaMinutos = $recurso->unidade->antecedencia_minima_minutos ?? 0;

        if ($antecedenciaMinutos <= 0) {
            return null;
        }

        $inicioAgendamento = $data->copy()->setTimeFromTimeString($horaInicio);
        $limiteMinimo = now()->addMinutes($antecedenciaMinutos);

        if ($inicioAgendamento->lt($limiteMinimo)) {
            return "Esse horário exige pelo menos {$antecedenciaMinutos} minutos de antecedência.";
        }

        return null;
    }
}
