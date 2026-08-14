<?php

namespace App\Events;

use App\Models\Agendamento;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * §06 — painel do dia/fila. Fila (Horizon) via ShouldBroadcast, nunca
 * ShouldBroadcastNow, pra não travar a request no controller. Payload
 * mínimo e sem nenhum campo sensível de preço/custo/margem — ver skill
 * realtime-status.
 */
class AgendamentoStatusAlterado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $agendamentoId;

    public int $empresaId;

    public int $unidadeId;

    public int $recursoId;

    public string $status;

    public string $data;

    public string $horaInicio;

    public string $horaFim;

    public string $clienteNome;

    public string $veiculoPlaca;

    public string $servicoNome;

    public function __construct(Agendamento $agendamento)
    {
        $agendamento->loadMissing(['cliente:id,nome', 'veiculo:id,placa', 'servico:id,nome']);

        $this->agendamentoId = $agendamento->id;
        $this->empresaId = $agendamento->empresa_id;
        $this->unidadeId = $agendamento->unidade_id;
        $this->recursoId = $agendamento->recurso_id;
        $this->status = $agendamento->status;
        $this->data = $agendamento->data->toDateString();
        $this->horaInicio = substr($agendamento->hora_inicio, 0, 5);
        $this->horaFim = substr($agendamento->hora_fim, 0, 5);
        $this->clienteNome = $agendamento->cliente->nome;
        $this->veiculoPlaca = $agendamento->veiculo->placa;
        $this->servicoNome = $agendamento->servico->nome;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("empresa.{$this->empresaId}.painel-dia"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agendamento.status-alterado';
    }
}
