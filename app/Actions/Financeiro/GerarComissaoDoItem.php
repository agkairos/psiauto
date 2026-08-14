<?php

namespace App\Actions\Financeiro;

use App\Models\Comissao;
use App\Models\OrcamentoItem;

/**
 * §13 — "comissões calculadas por serviço ou por peça". Só gera comissão se
 * o item tiver responsável (§07) e o serviço/produto tiver percentual de
 * comissão configurado (> 0). Base de cálculo: valor do item (não a
 * margem) — ver docs/financeiro.md.
 */
class GerarComissaoDoItem
{
    public function __invoke(OrcamentoItem $item): void
    {
        if ($item->responsavel_id === null) {
            return;
        }

        $percentual = $item->tipo === 'servico'
            ? $item->servico?->comissao_percentual
            : $item->produto?->comissao_percentual;

        if ($percentual === null || (float) $percentual <= 0) {
            return;
        }

        $valorBase = bcmul((string) $item->valor_unitario, (string) $item->quantidade, 2);
        $valorComissao = bcmul($valorBase, bcdiv((string) $percentual, '100', 4), 2);

        Comissao::create([
            'empresa_id' => $item->empresa_id,
            'ordem_servico_id' => $item->ordem_servico_id,
            'orcamento_item_id' => $item->id,
            'responsavel_id' => $item->responsavel_id,
            'valor_base' => $valorBase,
            'percentual' => $percentual,
            'valor_comissao' => $valorComissao,
            'status' => Comissao::STATUS_PENDENTE,
        ]);
    }
}
