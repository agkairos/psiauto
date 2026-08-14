<?php

namespace App\Actions\Estoque;

use App\Models\MovimentacaoEstoque;
use App\Models\OrcamentoItem;
use App\Models\Produto;
use Illuminate\Support\Facades\Auth;

/**
 * §11 — "baixa automática quando a peça é usada em uma ordem de serviço".
 * Decisão de implementação: o gatilho é a aprovação do item de peça (ver
 * docs/estoque.md) — spec não define o momento exato.
 */
class DarBaixaEstoqueDoItem
{
    /**
     * @return string|null mensagem de erro (saldo insuficiente) — null quando ok.
     */
    public function __invoke(OrcamentoItem $item): ?string
    {
        if ($item->tipo !== 'peca' || $item->produto_id === null) {
            return null;
        }

        $produto = Produto::findOrFail($item->produto_id);
        $unidadeId = $item->ordemServico->unidade_id;
        $saldo = $produto->saldoNaUnidade($unidadeId);

        if ($saldo < $item->quantidade) {
            return "Estoque insuficiente de \"{$produto->nome}\" nessa unidade (saldo: {$saldo}, necessário: {$item->quantidade}).";
        }

        MovimentacaoEstoque::create([
            'empresa_id' => $item->empresa_id,
            'unidade_id' => $unidadeId,
            'produto_id' => $produto->id,
            'tipo' => Produto::TIPO_SAIDA,
            'quantidade' => $item->quantidade,
            'ordem_servico_id' => $item->ordem_servico_id,
            'motivo' => "Item aprovado na OS #{$item->ordem_servico_id}",
            'criado_por' => Auth::id(),
        ]);

        return null;
    }
}
