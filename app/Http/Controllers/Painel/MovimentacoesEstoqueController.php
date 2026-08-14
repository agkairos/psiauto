<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarMovimentacaoEstoqueRequest;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovimentacoesEstoqueController extends Controller
{
    public function store(RegistrarMovimentacaoEstoqueRequest $request, Produto $produto): RedirectResponse
    {
        $dados = $request->validated();

        if (in_array($dados['tipo'], [Produto::TIPO_SAIDA, Produto::TIPO_PERDA], true)) {
            $saldo = $produto->saldoNaUnidade($dados['unidade_id']);
            if ($saldo < $dados['quantidade']) {
                return back()->withErrors(['quantidade' => "Saldo insuficiente nessa unidade (atual: {$saldo})."]);
            }
        }

        DB::transaction(function () use ($produto, $dados) {
            $produto->movimentacoes()->create([
                'empresa_id' => $produto->empresa_id,
                'unidade_id' => $dados['unidade_id'],
                'tipo' => $dados['tipo'],
                'quantidade' => $dados['quantidade'],
                'custo_unitario' => $dados['custo_unitario'] ?? null,
                'motivo' => $dados['motivo'] ?? null,
                'criado_por' => Auth::id(),
            ]);

            // §11 — "entrada por nota de compra [...] com atualização do
            // custo". Custeio adotado: último custo de entrada.
            if ($dados['tipo'] === Produto::TIPO_ENTRADA && ! empty($dados['custo_unitario'])) {
                $produto->update(['custo' => $dados['custo_unitario']]);
            }
        });

        return back()->with('sucesso', 'Movimentação registrada.');
    }
}
