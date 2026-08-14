<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * §13 — "gerada automaticamente pela ordem de serviço". Decisão de
 * implementação: dispara quando a OS vira "entregue" (ver
 * docs/financeiro.md — a spec não define o gatilho exato).
 */
class GerarContaReceberDaOS
{
    public function __invoke(OrdemServico $ordemServico, ?int $formaPagamentoId, int $numeroParcelas): ContaReceber
    {
        $valorTotal = $ordemServico->valorAprovado();

        return DB::transaction(function () use ($ordemServico, $formaPagamentoId, $numeroParcelas, $valorTotal) {
            $prazoDias = $formaPagamentoId
                ? FormaPagamento::find($formaPagamentoId)?->prazo_recebimento_dias ?? 0
                : 0;

            $conta = ContaReceber::create([
                'empresa_id' => $ordemServico->empresa_id,
                'unidade_id' => $ordemServico->unidade_id,
                'ordem_servico_id' => $ordemServico->id,
                'cliente_id' => $ordemServico->cliente_id,
                'forma_pagamento_id' => $formaPagamentoId,
                'valor_total' => $valorTotal,
                'criado_por' => Auth::id(),
            ]);

            $numeroParcelas = max(1, $numeroParcelas);
            $valorParcela = bcdiv($valorTotal, (string) $numeroParcelas, 2);
            $somaParcelas = bcmul($valorParcela, (string) ($numeroParcelas - 1), 2);
            $ultimaParcela = bcsub($valorTotal, $somaParcelas, 2); // ajusta arredondamento na última

            for ($numero = 1; $numero <= $numeroParcelas; $numero++) {
                $conta->parcelas()->create([
                    'empresa_id' => $ordemServico->empresa_id,
                    'numero' => $numero,
                    'valor' => $numero === $numeroParcelas ? $ultimaParcela : $valorParcela,
                    'data_vencimento' => now()->addDays($prazoDias)->addMonthsNoOverflow($numero - 1),
                ]);
            }

            return $conta;
        });
    }
}
