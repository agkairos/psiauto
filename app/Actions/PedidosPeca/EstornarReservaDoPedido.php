<?php

namespace App\Actions\PedidosPeca;

use App\Models\PedidoPeca;
use App\Models\PedidoPecaItem;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

class EstornarReservaDoPedido
{
    public function __invoke(PedidoPeca $pedidoPeca, ?int $estornadoPor = null): void
    {
        if ($pedidoPeca->status !== PedidoPeca::STATUS_RESERVADO) {
            return;
        }

        $itensReservados = $pedidoPeca->itens()
            ->where('disponibilidade', PedidoPecaItem::DISPONIBILIDADE_EM_ESTOQUE)
            ->whereNotNull('produto_id')
            ->with('produto')
            ->get();

        DB::transaction(function () use ($pedidoPeca, $itensReservados, $estornadoPor) {
            foreach ($itensReservados as $item) {
                $item->produto->movimentacoes()->create([
                    'empresa_id' => $pedidoPeca->empresa_id,
                    'unidade_id' => $pedidoPeca->unidade_id,
                    'tipo' => Produto::TIPO_ESTORNO,
                    'quantidade' => $item->quantidade,
                    'motivo' => "Estorno de reserva - pedido de peça #{$pedidoPeca->id}",
                    'criado_por' => $estornadoPor,
                ]);
            }
        });
    }
}
