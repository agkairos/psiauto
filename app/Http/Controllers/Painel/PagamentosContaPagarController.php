<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagamentoContaPagarRequest;
use App\Models\ContaPagar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PagamentosContaPagarController extends Controller
{
    public function store(RegistrarPagamentoContaPagarRequest $request, ContaPagar $contaPagar): RedirectResponse
    {
        Gate::authorize('update', $contaPagar);

        $dados = $request->validated();

        $saldoDevedor = bcsub((string) $contaPagar->valor, (string) $contaPagar->valor_pago, 2);
        if (bccomp((string) $dados['valor'], $saldoDevedor, 2) > 0) {
            return back()->withErrors(['valor' => 'O valor não pode passar do saldo da conta ('.$saldoDevedor.').']);
        }

        DB::transaction(function () use ($contaPagar, $dados) {
            $contaPagar->pagamentos()->create([
                'empresa_id' => $contaPagar->empresa_id,
                'valor' => $dados['valor'],
                'data' => $dados['data'],
                'registrado_por' => Auth::id(),
            ]);

            $contaPagar->refresh();

            // Quitou uma conta recorrente: gera a próxima ocorrência
            // automaticamente (decisão de implementação — spec não detalha
            // o mecanismo, ver docs/financeiro.md).
            if ($contaPagar->recorrente && $contaPagar->status === 'paga') {
                ContaPagar::create([
                    'empresa_id' => $contaPagar->empresa_id,
                    'unidade_id' => $contaPagar->unidade_id,
                    'fornecedor' => $contaPagar->fornecedor,
                    'descricao' => $contaPagar->descricao,
                    'categoria' => $contaPagar->categoria,
                    'valor' => $contaPagar->valor,
                    'data_vencimento' => $contaPagar->data_vencimento->copy()->addMonthNoOverflow(),
                    'recorrente' => true,
                    'periodicidade' => $contaPagar->periodicidade,
                    'criado_por' => $contaPagar->criado_por,
                ]);
            }
        });

        return back()->with('sucesso', 'Pagamento registrado.');
    }
}
