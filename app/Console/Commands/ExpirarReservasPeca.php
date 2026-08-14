<?php

namespace App\Console\Commands;

use App\Actions\PedidosPeca\EstornarReservaDoPedido;
use App\Models\PedidoPeca;
use Illuminate\Console\Command;

class ExpirarReservasPeca extends Command
{
    /**
     * §12 — "a peça fica separada por um prazo determinado". Sem worker
     * cron configurado neste ambiente (ver CLAUDE.md, Horizon pendente);
     * rodar manualmente até o agendador estar disponível.
     */
    protected $signature = 'pecas:expirar-reservas';

    protected $description = 'Estorna e expira pedidos de peça com reserva vencida (reservado_ate no passado)';

    public function handle(EstornarReservaDoPedido $estornarReserva): int
    {
        $pedidos = PedidoPeca::query()
            ->where('status', PedidoPeca::STATUS_RESERVADO)
            ->whereDate('reservado_ate', '<', now()->toDateString())
            ->get();

        foreach ($pedidos as $pedido) {
            $estornarReserva($pedido);
            $pedido->update(['status' => PedidoPeca::STATUS_EXPIRADO]);
            $this->info("Pedido #{$pedido->id} expirado e estoque liberado.");
        }

        $this->info("{$pedidos->count()} pedido(s) expirado(s).");

        return self::SUCCESS;
    }
}
