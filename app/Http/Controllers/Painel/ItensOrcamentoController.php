<?php

namespace App\Http\Controllers\Painel;

use App\Actions\Estoque\DarBaixaEstoqueDoItem;
use App\Actions\Financeiro\GerarComissaoDoItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecusarItemOrcamentoRequest;
use App\Http\Requests\SalvarItemOrcamentoRequest;
use App\Models\OrcamentoItem;
use App\Models\OrdemServico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ItensOrcamentoController extends Controller
{
    public function store(SalvarItemOrcamentoRequest $request, OrdemServico $ordemServico): RedirectResponse
    {
        Gate::authorize('update', $ordemServico);

        $ordemServico->itens()->create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
            'status' => OrcamentoItem::STATUS_PENDENTE,
            'criado_por' => Auth::id(),
        ]);

        // Primeiro item enviado: a OS sai de "aberta" (só diagnóstico) e
        // passa a aguardar decisão do cliente sobre o orçamento.
        if ($ordemServico->status === OrdemServico::STATUS_ABERTA) {
            $ordemServico->update(['status' => OrdemServico::STATUS_AGUARDANDO_APROVACAO]);
        }

        return back()->with('sucesso', 'Item adicionado ao orçamento.');
    }

    public function update(SalvarItemOrcamentoRequest $request, OrcamentoItem $item): RedirectResponse
    {
        Gate::authorize('update', $item->ordemServico);

        if ($item->status !== OrcamentoItem::STATUS_PENDENTE) {
            return back()->withErrors(['item' => 'Só dá pra editar item ainda pendente de aprovação.']);
        }

        $dados = $request->validated();

        // Auditoria de preço (autor, data, valor anterior) — regra do
        // CLAUDE.md. Só registra quando o valor de fato muda.
        if (bccomp((string) $dados['valor_unitario'], (string) $item->valor_unitario, 2) !== 0) {
            $historico = $item->historico_precos ?? [];
            $historico[] = [
                'valor_anterior' => (string) $item->valor_unitario,
                'alterado_por' => Auth::id(),
                'alterado_em' => now()->toIso8601String(),
            ];
            $dados['historico_precos'] = $historico;
        }

        $item->update($dados);

        return back()->with('sucesso', 'Item atualizado.');
    }

    public function destroy(OrcamentoItem $item): RedirectResponse
    {
        Gate::authorize('update', $item->ordemServico);

        if ($item->status !== OrcamentoItem::STATUS_PENDENTE) {
            return back()->withErrors(['item' => 'Só dá pra remover item ainda pendente de aprovação.']);
        }

        $item->delete();

        return back()->with('sucesso', 'Item removido.');
    }

    public function aprovar(
        OrcamentoItem $item,
        DarBaixaEstoqueDoItem $darBaixaEstoque,
        GerarComissaoDoItem $gerarComissao,
    ): RedirectResponse {
        Gate::authorize('update', $item->ordemServico);

        // Confere e dá baixa no estoque ANTES de marcar aprovado — se não
        // tiver saldo, a aprovação nem acontece (§11).
        $erroEstoque = $darBaixaEstoque($item);
        if ($erroEstoque !== null) {
            return back()->withErrors(['item' => $erroEstoque]);
        }

        DB::transaction(function () use ($item, $gerarComissao) {
            $item->update([
                'status' => OrcamentoItem::STATUS_APROVADO,
                'aprovado_por' => Auth::id(),
                'aprovado_em' => now(),
            ]);

            $gerarComissao($item);

            $this->avancarOsSeNecessario($item->ordemServico);
        });

        return back()->with('sucesso', 'Item aprovado.');
    }

    public function recusar(RecusarItemOrcamentoRequest $request, OrcamentoItem $item): RedirectResponse
    {
        Gate::authorize('update', $item->ordemServico);

        $item->update([
            'status' => OrcamentoItem::STATUS_RECUSADO,
            'aprovado_por' => Auth::id(),
            'aprovado_em' => now(),
            'motivo_recusa' => $request->validated('motivo_recusa'),
        ]);

        return back()->with('sucesso', 'Item recusado.');
    }

    /**
     * Assim que o primeiro item é aprovado, a OS sai de "aguardando
     * aprovação" e entra em execução — é esse aprovado que autoriza a
     * equipe a começar a mexer no carro.
     */
    private function avancarOsSeNecessario(OrdemServico $ordemServico): void
    {
        if ($ordemServico->status !== OrdemServico::STATUS_AGUARDANDO_APROVACAO) {
            return;
        }

        $ordemServico->refresh();

        if ($ordemServico->itens()->where('status', OrcamentoItem::STATUS_APROVADO)->exists()) {
            $ordemServico->update(['status' => OrdemServico::STATUS_EM_EXECUCAO]);
        }
    }
}
